import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiClient {
  final Dio dio;
  final _storage = const FlutterSecureStorage();

  ApiClient({String localIp = '192.168.100.40', String port = '8888'})
      : dio = Dio(
          BaseOptions(
            baseUrl: 'http://$localIp:$port/api/v1',
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 15),
            headers: {
              'Host': 'acme.lvh.me', // Force switch to tenant acme in local development
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        ) {
    // Interceptor to automatically attach Authorization Bearer header
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final String? token = await _storage.read(key: 'token');
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (DioException error, handler) {
          // Trigger global redirects or handle unauthorized token resets
          if (error.response?.statusCode == 401) {
            _storage.delete(key: 'token');
          }
          return handler.next(error);
        },
      ),
    );
  }
}
