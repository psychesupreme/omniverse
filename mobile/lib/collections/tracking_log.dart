import 'package:isar/isar.dart';

part 'tracking_log.g.dart';

@collection
class TrackingLog {
  Id id = Isar.autoIncrement;

  @Index(unique: true, replace: true)
  late String fastId;

  late int userId;

  late double latitude;

  late double longitude;

  late double speed;

  late DateTime recordedAtMobile;

  late int version;

  late DateTime lastUpdatedAt;

  bool isSynced = false;

  bool isMocked = false;
}
