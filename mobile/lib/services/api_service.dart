import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'api_client.dart';

class ApiService {
  final ApiClient _apiClient = ApiClient();
  final _storage = const FlutterSecureStorage();

  /// Authenticate worker via mobile Sanctum API using Dio
  Future<Map<String, dynamic>?> login(String tenantId, String email, String password) async {
    try {
      final response = await _apiClient.dio.post(
        '/mobile/login',
        data: {
          'email': email,
          'password': password,
        },
      );

      print('Login Response Code: ${response.statusCode}');
      print('Login Response Body: ${response.data}');

      if (response.statusCode == 200) {
        final data = response.data as Map<String, dynamic>;
        final token = data['token'] as String;
        final user = data['user'] as Map<String, dynamic>;

        // Store token securely
        await _storage.write(key: 'token', value: token);

        // Map keys to match existing login screen expectations
        return {
          'token': token,
          'user_id': user['id'] as int,
          'name': user['name'] as String,
        };
      } else {
        throw Exception('Invalid credentials.');
      }
    } on DioException catch (e) {
      print('Dio Error during login: ${e.message}');
      final errorMessage = e.response?.data['message'] ?? 'Invalid credentials.';
      throw Exception(errorMessage);
    } catch (e) {
      print('General Error during login: $e');
      rethrow;
    }
  }

  /// Pull schema updates from the Laravel API sync endpoint
  Future<Map<String, dynamic>> pullSync(String tenantId, String token, String lastSync) async {
    final Map<String, dynamic> body = {
      'collections': ['outlets', 'tracking_logs'],
    };

    if (lastSync.isNotEmpty) {
      body['last_sync_timestamp'] = lastSync;
    }

    try {
      final response = await _apiClient.dio.post(
        '/sync/pull',
        data: body,
      );

      print('Pull Sync Response Code: ${response.statusCode}');

      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
    } catch (e) {
      print('Error during pullSync: $e');
    }
    return {};
  }

  /// Push local tracking log updates and outlets to the Laravel API sync endpoint
  Future<bool> pushSync(String tenantId, String token, Map<String, dynamic> payload) async {
    try {
      final response = await _apiClient.dio.post(
        '/sync/push',
        data: payload,
      );

      print('Push Sync Response Code: ${response.statusCode}');
      
      return response.statusCode == 200;
    } catch (e) {
      print('Error during pushSync: $e');
      return false;
    }
  }
}
