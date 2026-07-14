import 'package:isar/isar.dart';

part 'outlet.g.dart';

@collection
class Outlet {
  Id id = Isar.autoIncrement;

  @Index(unique: true, replace: true)
  String fastId = '';

  String name = '';

  double latitude = 0.0;

  double longitude = 0.0;

  int version = 1;

  DateTime lastUpdatedAt = DateTime.fromMillisecondsSinceEpoch(0);
}
