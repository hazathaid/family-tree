import 'package:dio/dio.dart';
import 'package:hive_flutter/hive_flutter.dart';

class ApiException implements Exception {
  const ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({required String baseUrl, Dio? dio, Box<String>? sessionBox})
      : _dio = dio ?? Dio(),
        _sessionBox = sessionBox {
    _dio.options = BaseOptions(baseUrl: baseUrl, headers: {'Accept': 'application/json'});
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        final token = _sessionBox?.get('token');
        if (token != null) options.headers['Authorization'] = 'Bearer $token';
        handler.next(options);
      },
    ));
  }

  final Dio _dio;
  final Box<String>? _sessionBox;

  Future<dynamic> get(String path, {Map<String, dynamic>? query}) async =>
      _request(() => _dio.get<dynamic>(path, queryParameters: query));

  Future<dynamic> post(String path, {Map<String, dynamic>? data}) async =>
      _request(() => _dio.post<dynamic>(path, data: data));

  Future<void> saveToken(String token) async => _sessionBox?.put('token', token);

  Future<void> clearToken() async => _sessionBox?.delete('token');

  bool get hasToken => _sessionBox?.containsKey('token') ?? false;

  Future<dynamic> _request(Future<Response<dynamic>> Function() request) async {
    try {
      final response = await request();
      final body = response.data as Map<String, dynamic>;
      if (body['success'] != true) throw ApiException(body['message'] as String? ?? 'Request failed');
      return body['data'];
    } on DioException catch (error) {
      final data = error.response?.data;
      final message = data is Map<String, dynamic> ? data['message'] as String? : null;
      throw ApiException(message ?? 'Tidak dapat terhubung ke server.');
    }
  }
}
