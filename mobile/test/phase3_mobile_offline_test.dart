import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:omniroute_mobile/collections/order_collection.dart';
import 'package:omniroute_mobile/collections/product_collection.dart';
import 'package:omniroute_mobile/collections/task_collection.dart';

void main() {
  group('Test Phase 3: Mobile Offline Task & Order Entry', () {
    test('1. Offline Task Status Update & Evidence Photo Staging', () {
      final task = TaskCollection()
        ..id = 'task-uuid-101'
        ..title = 'Deliver Goods to Central Outlet'
        ..status = 'pending'
        ..isSynced = true;

      expect(task.status, equals('pending'));
      expect(task.isSynced, isTrue);

      // Transition to in_progress
      task.status = 'in_progress';
      task.isSynced = false;
      expect(task.status, equals('in_progress'));
      expect(task.isSynced, isFalse);

      // Complete job with notes and evidence photo paths
      task.status = 'completed';
      task.completionNotes = 'Delivery verified by store manager.';
      task.localPhotoPaths = ['/storage/photos/photo_1.jpg', '/storage/photos/photo_2.jpg'];
      task.updatedAt = DateTime.now();

      expect(task.status, equals('completed'));
      expect(task.isSynced, isFalse);
      expect(task.completionNotes, equals('Delivery verified by store manager.'));
      expect(task.localPhotoPaths!.length, equals(2));
      expect(task.localPhotoPaths, contains('/storage/photos/photo_1.jpg'));
    });

    test('2. Offline Sales Order Creation & Item Calculations', () {
      final product1 = ProductCollection()
        ..id = 'prod-1'
        ..sku = 'SKU-001'
        ..name = 'Premium Solar Panel'
        ..unitPrice = 150.00
        ..stockQuantity = 50;

      final product2 = ProductCollection()
        ..id = 'prod-2'
        ..sku = 'SKU-002'
        ..name = '100Ah Inverter Battery'
        ..unitPrice = 250.00
        ..stockQuantity = 30;

      // Add 2 Solar Panels and 1 Battery to cart
      final items = [
        {
          'product_id': product1.id,
          'quantity': 2,
          'unit_price': product1.unitPrice,
          'subtotal': product1.unitPrice * 2, // 300.00
        },
        {
          'product_id': product2.id,
          'quantity': 1,
          'unit_price': product2.unitPrice,
          'subtotal': product2.unitPrice * 1, // 250.00
        },
      ];

      final double totalAmount = items.fold(0.0, (sum, i) => sum + (i['subtotal'] as double));
      expect(totalAmount, equals(550.00));

      final String orderUuid = 'order-uuid-999';
      final String orderNum = 'ORD-${DateTime.now().millisecondsSinceEpoch}';

      final order = OrderCollection()
        ..id = orderUuid
        ..orderNumber = orderNum
        ..outletId = 'outlet-uuid-55'
        ..totalAmount = totalAmount
        ..status = 'pending'
        ..notes = 'Urgent delivery requested'
        ..itemsJson = jsonEncode(items)
        ..isSynced = false
        ..placedAt = DateTime.now();

      expect(order.id, equals(orderUuid));
      expect(order.orderNumber, startsWith('ORD-'));
      expect(order.totalAmount, equals(550.00));
      expect(order.status, equals('pending'));
      expect(order.isSynced, isFalse);

      final List<dynamic> parsedItems = jsonDecode(order.itemsJson);
      expect(parsedItems.length, equals(2));
      expect(parsedItems[0]['subtotal'], equals(300.00));
      expect(parsedItems[1]['subtotal'], equals(250.00));
    });

    test('3. Mobile Sync Payload Formatting for Push Endpoint', () {
      final task = TaskCollection()
        ..id = 'task-uuid-101'
        ..status = 'completed'
        ..completionNotes = 'Store inspection complete'
        ..localPhotoPaths = []
        ..isSynced = false
        ..updatedAt = DateTime.now();

      final order = OrderCollection()
        ..id = 'order-uuid-999'
        ..orderNumber = 'ORD-1700000000'
        ..outletId = 'outlet-uuid-55'
        ..totalAmount = 550.00
        ..status = 'pending'
        ..notes = 'Handle with care'
        ..itemsJson = jsonEncode([
          {'product_id': 'prod-1', 'quantity': 2, 'unit_price': 150.0, 'subtotal': 300.0}
        ])
        ..isSynced = false
        ..placedAt = DateTime.now();

      // Format payload for /api/v1/sync/push
      final List<dynamic> itemsList = jsonDecode(order.itemsJson);

      final serializedOrder = {
        'id': order.id,
        'order_number': order.orderNumber,
        'outlet_id': order.outletId,
        'total_amount': order.totalAmount,
        'status': order.status,
        'notes': order.notes,
        'placed_at': order.placedAt.toUtc().toIso8601String(),
        'items': itemsList,
      };

      final serializedTask = {
        'id': task.id,
        'status': task.status,
        'completion_notes': task.completionNotes,
        'last_updated_at': task.updatedAt!.toUtc().toIso8601String(),
        'evidence_photos': [],
      };

      final pushPayload = {
        'client_timestamp': DateTime.now().toUtc().toIso8601String(),
        'data': {
          'orders': [serializedOrder],
          'tasks': [serializedTask],
        }
      };

      final Map<String, dynamic> data = pushPayload['data'] as Map<String, dynamic>;
      expect(data.containsKey('orders'), isTrue);
      expect(data.containsKey('tasks'), isTrue);

      final orders = data['orders'] as List;
      expect(orders.length, equals(1));
      expect(orders[0]['order_number'], equals('ORD-1700000000'));
      expect(orders[0]['items'].length, equals(1));

      final tasks = data['tasks'] as List;
      expect(tasks.length, equals(1));
      expect(tasks[0]['status'], equals('completed'));
    });
  });
}
