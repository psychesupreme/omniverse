import 'package:isar/isar.dart';

part 'tracking_log.g.dart';

@collection
class TrackingLog {
  Id id = Isar.autoIncrement;

  @Index(unique: true, replace: true)
  String fastId = '';

  int userId = 0;

  double latitude = 0.0;

  double longitude = 0.0;

  double speed = 0.0;

  DateTime recordedAtMobile = DateTime.fromMillisecondsSinceEpoch(0);

  int version = 1;

  DateTime lastUpdatedAt = DateTime.fromMillisecondsSinceEpoch(0);

  bool isSynced = false;

  bool isMocked = false;
}
