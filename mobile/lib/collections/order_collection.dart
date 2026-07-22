import 'package:isar/isar.dart';

part 'order_collection.g.dart';

@collection
class OrderCollection {
  Id isarId = Isar.autoIncrement;

  @Index(unique: true, replace: true)
  late String id;

  late String orderNumber;

  String? outletId;

  late double totalAmount;

  late String status;

  String? notes;

  late String itemsJson;

  @Index()
  bool isSynced = false;

  late DateTime placedAt;
}
