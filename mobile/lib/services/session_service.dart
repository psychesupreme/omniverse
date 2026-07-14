import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SessionService {
  static const _storage = FlutterSecureStorage();

  static const String _keyToken = 'token';
  static const String _keyTenantId = 'tenant_id';
  static const String _keyUserId = 'user_id';
  static const String _keyUserName = 'user_name';
  static const String _keyUserEmail = 'user_email';

  /// Saves the session securely
  static Future<void> saveSession({
    required String token,
    required String tenantId,
    required int userId,
    required String name,
    required String email,
  }) async {
    await _storage.write(key: _keyToken, value: token);
    await _storage.write(key: _keyTenantId, value: tenantId);
    await _storage.write(key: _keyUserId, value: userId.toString());
    await _storage.write(key: _keyUserName, value: name);
    await _storage.write(key: _keyUserEmail, value: email);
  }

  /// Checks if a valid session exists
  static Future<bool> hasSession() async {
    final token = await _storage.read(key: _keyToken);
    final tenantId = await _storage.read(key: _keyTenantId);
    return token != null && token.isNotEmpty && tenantId != null && tenantId.isNotEmpty;
  }

  /// Retrieves the stored session details, or null if empty
  static Future<Map<String, dynamic>?> getSession() async {
    final token = await _storage.read(key: _keyToken);
    final tenantId = await _storage.read(key: _keyTenantId);
    final userIdStr = await _storage.read(key: _keyUserId);
    final name = await _storage.read(key: _keyUserName);
    final email = await _storage.read(key: _keyUserEmail);

    if (token == null || tenantId == null || userIdStr == null) {
      return null;
    }

    final userId = int.tryParse(userIdStr) ?? 0;

    return {
      'token': token,
      'tenantId': tenantId,
      'userId': userId,
      'name': name ?? '',
      'email': email ?? '',
    };
  }

  /// Clears the securely stored session
  static Future<void> clearSession() async {
    await _storage.delete(key: _keyToken);
    await _storage.delete(key: _keyTenantId);
    await _storage.delete(key: _keyUserId);
    await _storage.delete(key: _keyUserName);
    await _storage.delete(key: _keyUserEmail);
  }
}
