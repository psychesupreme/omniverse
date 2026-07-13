import 'package:isar/isar.dart';

part 'outlet.g.dart';

@collection
class Outlet {
  Id id = Isar.autoIncrement;

  @Index(unique: true, replace: true)
  late String fastId;

  late String name;

  late double latitude;

  late double longitude;

  late int version;

  late DateTime lastUpdatedAt;
}
