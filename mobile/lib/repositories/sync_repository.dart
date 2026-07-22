import 'dart:convert';
import 'dart:io';
import 'package:isar/isar.dart';
import 'package:omniroute_mobile/collections/order_collection.dart';
import 'package:omniroute_mobile/collections/outlet.dart';
import 'package:omniroute_mobile/collections/product_collection.dart';
import 'package:omniroute_mobile/collections/task_collection.dart';
import 'package:omniroute_mobile/collections/tracking_log.dart';
import 'package:omniroute_mobile/services/api_service.dart';

class SyncRepository {
  final Isar isar;

  SyncRepository(this.isar);

  /// Saves pulled data inside a single write transaction, checking for conflicts by fastId/id
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
            ..speed = double.tryParse(json['speed']?.toString() ?? '0.0') ?? 0.0
            ..recordedAtMobile = json['recorded_at_mobile'] != null
                ? DateTime.parse(json['recorded_at_mobile'] as String)
                : DateTime.fromMillisecondsSinceEpoch(0)
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

      // 3. Process Product Catalogue
      if (payload.containsKey('products') && payload['products'] != null) {
        final List<dynamic> productsJson = payload['products'];
        final List<ProductCollection> products = [];

        for (var json in productsJson) {
          final String remoteId = json['id'].toString();
          final existing = await isar.productCollections.filter().idEqualTo(remoteId).findFirst();

          final product = existing ?? ProductCollection();
          product.id = remoteId;
          product.sku = json['sku'] as String;
          product.name = json['name'] as String;
          product.description = json['description'] as String?;
          product.unitPrice = (json['unit_price'] as num).toDouble();
          product.stockQuantity = (json['stock_quantity'] as num).toInt();
          product.isActive = (json['is_active'] as bool?) ?? true;

          products.add(product);
        }

        if (products.isNotEmpty) {
          await isar.productCollections.putAll(products);
        }
      }
    });
  }

  /// Queries all local TrackingLogs where isSynced is false
  Future<List<TrackingLog>> getUnsyncedLogs() async {
    return await isar.trackingLogs.filter().isSyncedEqualTo(false).findAll();
  }

  /// Queries all local TaskCollections where isSynced is false
  Future<List<TaskCollection>> getUnsyncedTasks() async {
    return await isar.taskCollections.filter().isSyncedEqualTo(false).findAll();
  }

  /// Queries all local OrderCollections where isSynced is false
  Future<List<OrderCollection>> getUnsyncedOrders() async {
    return await isar.orderCollections.filter().isSyncedEqualTo(false).findAll();
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

  /// Marks a batch of local tasks as synced
  Future<void> markTasksAsSynced(List<Id> localIsarIds) async {
    if (localIsarIds.isEmpty) return;
    await isar.writeTxn(() async {
      final tasks = await isar.taskCollections.getAll(localIsarIds);
      final List<TaskCollection> updatedTasks = [];

      for (var task in tasks) {
        if (task != null) {
          task.isSynced = true;
          updatedTasks.add(task);
        }
      }

      if (updatedTasks.isNotEmpty) {
        await isar.taskCollections.putAll(updatedTasks);
      }
    });
  }

  /// Marks a batch of local orders as synced
  Future<void> markOrdersAsSynced(List<Id> localIsarIds) async {
    if (localIsarIds.isEmpty) return;
    await isar.writeTxn(() async {
      final orders = await isar.orderCollections.getAll(localIsarIds);
      final List<OrderCollection> updatedOrders = [];

      for (var order in orders) {
        if (order != null) {
          order.isSynced = true;
          updatedOrders.add(order);
        }
      }

      if (updatedOrders.isNotEmpty) {
        await isar.orderCollections.putAll(updatedOrders);
      }
    });
  }

  /// Push all unsynced tracking logs, offline tasks, and sales orders to the server
  Future<int> pushUnsyncedData(ApiService apiService, String tenantId, String token, {int batchSize = 50}) async {
    final unsyncedLogs = await getUnsyncedLogs();
    final unsyncedTasks = await getUnsyncedTasks();
    final unsyncedOrders = await getUnsyncedOrders();

    if (unsyncedLogs.isEmpty && unsyncedTasks.isEmpty && unsyncedOrders.isEmpty) return 0;

    int totalSynced = 0;

    // 1. Push unsynced sales orders
    if (unsyncedOrders.isNotEmpty) {
      final List<Map<String, dynamic>> serializedOrders = [];
      final List<Id> orderIsarIds = [];

      for (var order in unsyncedOrders) {
        List<dynamic> itemsList = [];
        try {
          itemsList = jsonDecode(order.itemsJson) as List<dynamic>;
        } catch (e) {
          print('Error parsing itemsJson for order sync: $e');
        }

        serializedOrders.add({
          'id': order.id,
          'order_number': order.orderNumber,
          'outlet_id': order.outletId,
          'total_amount': order.totalAmount,
          'status': order.status,
          'notes': order.notes,
          'placed_at': order.placedAt.toUtc().toIso8601String(),
          'items': itemsList,
        });
        orderIsarIds.add(order.isarId);
      }

      final orderPayload = {
        'client_timestamp': DateTime.now().toUtc().toIso8601String(),
        'data': {
          'orders': serializedOrders,
        },
      };

      final orderSyncSuccess = await apiService.pushSync(tenantId, token, orderPayload);
      if (orderSyncSuccess) {
        await markOrdersAsSynced(orderIsarIds);
        totalSynced += orderIsarIds.length;
      }
    }

    // 2. Push unsynced tasks (with base64 photo attachments)
    if (unsyncedTasks.isNotEmpty) {
      final List<Map<String, dynamic>> serializedTasks = [];
      final List<Id> taskIsarIds = [];

      for (var task in unsyncedTasks) {
        final List<String> base64Photos = [];
        if (task.localPhotoPaths != null && task.localPhotoPaths!.isNotEmpty) {
          for (var photoPath in task.localPhotoPaths!) {
            try {
              final file = File(photoPath);
              if (file.existsSync()) {
                final bytes = file.readAsBytesSync();
                base64Photos.add(base64Encode(bytes));
              }
            } catch (e) {
              print('Error reading image file for task sync: $e');
            }
          }
        }

        serializedTasks.add({
          'id': task.id,
          'status': task.status,
          'completion_notes': task.completionNotes,
          'last_updated_at': (task.updatedAt ?? DateTime.now()).toUtc().toIso8601String(),
          'evidence_photos': base64Photos,
        });
        taskIsarIds.add(task.isarId);
      }

      final taskPayload = {
        'client_timestamp': DateTime.now().toUtc().toIso8601String(),
        'data': {
          'tasks': serializedTasks,
        },
      };

      final taskSyncSuccess = await apiService.pushSync(tenantId, token, taskPayload);
      if (taskSyncSuccess) {
        await markTasksAsSynced(taskIsarIds);
        totalSynced += taskIsarIds.length;
      }
    }

    // 3. Push unsynced tracking logs in batches
    if (unsyncedLogs.isNotEmpty) {
      final logsSynced = await pushUnsyncedLogs(apiService, tenantId, token, batchSize: batchSize);
      totalSynced += logsSynced;
    }

    return totalSynced;
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
          final s = log.speed;
          if (s.isFinite && !s.isNaN) {
            speedVal = s;
          }
        } catch (_) {}

        try {
          recordedAt = log.recordedAtMobile;
        } catch (_) {}

        double latVal = 0.0;
        if (log.latitude.isFinite && !log.latitude.isNaN) {
          latVal = log.latitude;
        }

        double lngVal = 0.0;
        if (log.longitude.isFinite && !log.longitude.isNaN) {
          lngVal = log.longitude;
        }

        serializedLogs.add({
          'id': log.fastId,
          'user_id': log.userId,
          'location': {
            'latitude': latVal,
            'longitude': lngVal,
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
