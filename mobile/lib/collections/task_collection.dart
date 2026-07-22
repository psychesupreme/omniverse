import 'package:isar/isar.dart';

part 'task_collection.g.dart';

@collection
class TaskCollection {
  Id isarId = Isar.autoIncrement;

  @Index(unique: true, replace: true)
  late String id;

  late String title;

  String? description;

  late String status;

  DateTime? dueDate;

  String? address;

  double? latitude;

  double? longitude;

  String? completionNotes;

  List<String>? localPhotoPaths;

  @Index()
  bool isSynced = true;

  DateTime? updatedAt;
}
