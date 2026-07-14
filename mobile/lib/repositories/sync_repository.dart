import 'package:isar/isar.dart';
import 'package:omniroute_mobile/collections/outlet.dart';
import 'package:omniroute_mobile/collections/tracking_log.dart';
import 'package:omniroute_mobile/services/api_service.dart';

class SyncRepository {
  final Isar isar;

  SyncRepository(this.isar);

  /// Saves pulled data inside a single write transaction, checking for conflicts by fastId
  Future<void> savePulledData(Map<String, dynamic> payload) async {
    await isar.writeTxn(() async {
      // 1. Process Outlets
      if (payload.containsKey('outlets') && payload['outlets'] != null) {
        final List<dynamic> outletsJson = payload['outlets'];
        final List<Outlet> outlets = [];

        for (var json in outletsJson) {
          final String fastId = json['id'].toString();
          final existing = await isar.outlets.filter().fastIdEqualTo(fastId).findFirst();

          final outlet = Outlet()
            ..fastId = fastId
            ..name = json['name'] as String
            ..latitude = (json['location']['latitude'] as num).toDouble()
            ..longitude = (json['location']['longitude'] as num).toDouble()
            ..version = json['version'] as int
            ..lastUpdatedAt = DateTime.parse(json['last_updated_at'] as String);

          if (existing != null) {
            outlet.id = existing.id;
          }
          outlets.add(outlet);
        }

        if (outlets.isNotEmpty) {
          await isar.outlets.putAll(outlets);
        }
      }

      // 2. Process Tracking Logs
      if (payload.containsKey('tracking_logs') && payload['tracking_logs'] != null) {
        final List<dynamic> logsJson = payload['tracking_logs'];
        final List<TrackingLog> logs = [];

        for (var json in logsJson) {
          final String fastId = json['id'].toString();
          final existing = await isar.trackingLogs.filter().fastIdEqualTo(fastId).findFirst();

          final log = TrackingLog()
            ..fastId = fastId
            ..userId = json['user_id'] as int
            ..latitude = (json['location']['latitude'] as num).toDouble()
            ..longitude = (json['location']['longitude'] as num).toDouble()
            ..version = json['version'] as int
            ..lastUpdatedAt = DateTime.parse(json['last_updated_at'] as String)
            ..isSynced = true; // Mark incoming server logs as synced locally

          if (existing != null) {
            log.id = existing.id;
          }
          logs.add(log);
        }

        if (logs.isNotEmpty) {
          await isar.trackingLogs.putAll(logs);
        }
      }
    });
  }

  /// Queries all local TrackingLogs where isSynced is false
  Future<List<TrackingLog>> getUnsyncedLogs() async {
    return await isar.trackingLogs.filter().isSyncedEqualTo(false).findAll();
  }

  /// Marks a batch of local logs as synced
  Future<void> markLogsAsSynced(List<Id> localIds) async {
    if (localIds.isEmpty) return;
    await isar.writeTxn(() async {
      final logs = await isar.trackingLogs.getAll(localIds);
      final List<TrackingLog> updatedLogs = [];

      for (var log in logs) {
        if (log != null) {
          log.isSynced = true;
          updatedLogs.add(log);
        }
      }

      if (updatedLogs.isNotEmpty) {
        await isar.trackingLogs.putAll(updatedLogs);
      }
    });
  }

  /// Push all unsynced tracking logs to the server in chunks (default: 50 logs per batch)
  Future<int> pushUnsyncedLogs(ApiService apiService, String tenantId, String token, {int batchSize = 50}) async {
    final unsyncedLogs = await getUnsyncedLogs();
    if (unsyncedLogs.isEmpty) return 0;

    int totalSynced = 0;

    for (int i = 0; i < unsyncedLogs.length; i += batchSize) {
      final chunk = unsyncedLogs.sublist(
        i,
        i + batchSize > unsyncedLogs.length ? unsyncedLogs.length : i + batchSize,
      );

      final List<Map<String, dynamic>> serializedLogs = [];
      for (var log in chunk) {
        double speedVal = 0.0;
        DateTime recordedAt = log.lastUpdatedAt;

        try {
          speedVal = log.speed;
        } catch (_) {}

        try {
          recordedAt = log.recordedAtMobile;
        } catch (_) {}

        serializedLogs.add({
          'id': log.fastId,
          'user_id': log.userId,
          'location': {
            'latitude': log.latitude,
            'longitude': log.longitude,
          },
          'speed': speedVal,
          'recorded_at_mobile': recordedAt.toUtc().toIso8601String(),
          'version': log.version,
          'last_updated_at': log.lastUpdatedAt.toUtc().toIso8601String(),
          'deleted_at': null,
        });
      }

      final payload = {
        'client_timestamp': DateTime.now().toUtc().toIso8601String(),
        'data': {
          'tracking_logs': serializedLogs,
        },
      };

      // Push this batch chunk to the API
      final success = await apiService.pushSync(tenantId, token, payload);
      
      if (success) {
        // Collect local Isar IDs to mark as synced
        final localIds = chunk.map((log) => log.id).toList();
        await markLogsAsSynced(localIds);
        totalSynced += chunk.length;
      } else {
        // If one batch fails, we break early to prevent subsequent timeouts
        break;
      }
    }

    return totalSynced;
  }
}
