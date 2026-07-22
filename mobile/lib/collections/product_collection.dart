import 'package:isar/isar.dart';

part 'product_collection.g.dart';

@collection
class ProductCollection {
  Id isarId = Isar.autoIncrement;

  @Index(unique: true, replace: true)
  late String id;

  late String sku;

  late String name;

  String? description;

  late double unitPrice;

  late int stockQuantity;

  late bool isActive;
}
