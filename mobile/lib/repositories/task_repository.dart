import 'dart:io';
import 'package:isar/isar.dart';
import 'package:path_provider/path_provider.dart';
import 'package:omniroute_mobile/collections/task_collection.dart';
import 'package:omniroute_mobile/services/api_client.dart';

class TaskRepository {
  final Isar isar;
  final ApiClient apiClient;

  TaskRepository({required this.isar, ApiClient? apiClient})
      : apiClient = apiClient ?? ApiClient();

  /// Fetches assigned tasks from the backend API.
  /// Updates local Isar cache for tasks that are marked as synced (isSynced == true).
  /// Falls back to local Isar tasks on network failure.
  Future<List<TaskCollection>> refreshTasks() async {
    try {
      final response = await apiClient.dio.get('/mobile/tasks');

      if (response.statusCode == 200 && response.data != null) {
        final List<dynamic> tasksJson = response.data is List
            ? response.data
            : (response.data['data'] ?? []);

        await isar.writeTxn(() async {
          for (var json in tasksJson) {
            final String remoteId = json['id'].toString();

            // Find existing task in local Isar DB
            final existing = await isar.taskCollections.filter().idEqualTo(remoteId).findFirst();

            // If task exists locally and has unsynced changes (isSynced == false), preserve local edits
            if (existing != null && !existing.isSynced) {
              continue;
            }

            // Extract outlet details / location coordinates if present
            double? lat;
            double? lng;
            String? address;
            if (json['outlet'] != null) {
              address = json['outlet']['name'] as String?;
              if (json['outlet']['location'] != null) {
                lat = (json['outlet']['location']['latitude'] as num?)?.toDouble();
                lng = (json['outlet']['location']['longitude'] as num?)?.toDouble();
              }
            }

            final DateTime? scheduledFor = json['scheduled_for'] != null
                ? DateTime.tryParse(json['scheduled_for'].toString())
                : null;
            final DateTime? updatedAtServer = json['updated_at'] != null
                ? DateTime.tryParse(json['updated_at'].toString())
                : DateTime.now();

            final task = existing ?? TaskCollection();
            task.id = remoteId;
            task.title = json['title']?.toString() ?? 'Untitled Task';
            task.description = json['description']?.toString();
            task.status = json['status']?.toString() ?? 'pending';
            task.dueDate = scheduledFor;
            task.address = address;
            task.latitude = lat;
            task.longitude = lng;
            task.completionNotes = json['completion_notes']?.toString();
            task.isSynced = true; // Synced with server
            task.updatedAt = updatedAtServer;

            await isar.taskCollections.put(task);
          }
        });
      }
    } catch (e) {
      print('Network error during refreshTasks, serving cached Isar tasks: $e');
    }

    // Return current list of tasks from local Isar DB
    return await isar.taskCollections.where().findAll();
  }

  /// Updates task status locally in Isar and copies provided evidence photos to local app directory.
  /// Sets isSynced = false so background sync can push local changes when online.
  Future<TaskCollection?> updateTaskStatus({
    required String taskId,
    required String status,
    String? notes,
    List<dynamic>? photos,
  }) async {
    final task = await isar.taskCollections.filter().idEqualTo(taskId).findFirst();
    if (task == null) {
      print('Task not found in local Isar DB with ID: $taskId');
      return null;
    }

    final List<String> copiedPhotoPaths = List<String>.from(task.localPhotoPaths ?? []);

    if (photos != null && photos.isNotEmpty) {
      final appDir = await getApplicationDocumentsDirectory();
      final timestamp = DateTime.now().millisecondsSinceEpoch;

      for (int i = 0; i < photos.length; i++) {
        final photoInput = photos[i];
        File? sourceFile;

        if (photoInput is File) {
          sourceFile = photoInput;
        } else if (photoInput is String && photoInput.isNotEmpty) {
          sourceFile = File(photoInput);
        }

        if (sourceFile != null && await sourceFile.exists()) {
          final extension = sourceFile.path.split('.').last;
          final newFileName = 'task_${taskId}_${timestamp}_$i.$extension';
          final savedImage = await sourceFile.copy('${appDir.path}/$newFileName');
          copiedPhotoPaths.add(savedImage.path);
        }
      }
    }

    await isar.writeTxn(() async {
      task.status = status;
      if (notes != null) {
        task.completionNotes = notes;
      }
      if (copiedPhotoPaths.isNotEmpty) {
        task.localPhotoPaths = copiedPhotoPaths;
      }
      task.isSynced = false; // Mark as unsynced for sync queue
      task.updatedAt = DateTime.now();

      await isar.taskCollections.put(task);
    });

    return task;
  }

  /// Fetches current active shift status for the logged-in field worker.
  Future<Map<String, dynamic>?> getActiveShift() async {
    try {
      final response = await apiClient.dio.get('/mobile/timesheet/active');
      if (response.statusCode == 200 && response.data != null) {
        return response.data as Map<String, dynamic>;
      }
    } catch (e) {
      print('Error fetching active shift: $e');
    }
    return null;
  }
}
