import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = 'http://192.168.2.54:8888/api/v1';

  /// Authenticate worker via Sanctum API
  Future<Map<String, dynamic>?> login(String tenantId, String email, String password) async {
    final url = Uri.parse('$baseUrl/login');

    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Host': '$tenantId.localhost', // Resolve tenant database context
    };

    final body = {
      'email': email,
      'password': password,
    };

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(body),
      );

      print('Login Response Code: ${response.statusCode}');
      print('Login Response Body: ${response.body}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body) as Map<String, dynamic>;
      } else {
        final errorData = jsonDecode(response.body);
        throw Exception(errorData['message'] ?? 'Invalid credentials.');
      }
    } catch (e) {
      print('Error during login: $e');
      rethrow;
    }
  }

  /// Pull schema updates from the Laravel API sync endpoint
  Future<Map<String, dynamic>> pullSync(String tenantId, String token, String lastSync) async {
    final url = Uri.parse('$baseUrl/sync/pull');

    final Map<String, dynamic> body = {
      'collections': ['outlets', 'tracking_logs'],
    };

    if (lastSync.isNotEmpty) {
      body['last_sync_timestamp'] = lastSync;
    }

    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
      'Host': '$tenantId.localhost', // Resolve tenant database context
    };

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(body),
      );

      print('Pull Sync Response Code: ${response.statusCode}');
      print('Pull Sync Response Body: ${response.body}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body) as Map<String, dynamic>;
      }
    } catch (e) {
      print('Error during pullSync: $e');
    }
    return {};
  }

  /// Push local tracking log updates and outlets to the Laravel API sync endpoint
  Future<bool> pushSync(String tenantId, String token, Map<String, dynamic> payload) async {
    final url = Uri.parse('$baseUrl/sync/push');

    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
      'Host': '$tenantId.localhost', // Resolve tenant database context
    };

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(payload),
      );

      print('Push Sync Response Code: ${response.statusCode}');
      print('Push Sync Response Body: ${response.body}');
      
      return response.statusCode == 200;
    } catch (e) {
      print('Error during pushSync: $e');
      return false;
    }
  }
}
