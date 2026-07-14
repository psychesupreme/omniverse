import 'package:isar/isar.dart';
import 'package:omniroute_mobile/collections/outlet.dart';
import 'package:omniroute_mobile/collections/tracking_log.dart';

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
}
