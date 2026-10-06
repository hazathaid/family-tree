import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:family_tree_mobile/core/api_client.dart';
import 'package:family_tree_mobile/core/storage/secure_token_store.dart';
import 'package:flutter_test/flutter_test.dart';

class _FakeAdapter implements HttpClientAdapter {
  _FakeAdapter(this.handler);
  final Future<ResponseBody> Function(RequestOptions options) handler;

  @override
  Future<ResponseBody> fetch(RequestOptions options,
          Stream<Uint8List>? requestStream, Future<void>? cancelFuture) =>
      handler(options);

  @override
  void close({bool force = false}) {}
}

ResponseBody _json(Map<String, dynamic> body, int status) =>
    ResponseBody.fromString(jsonEncode(body), status, headers: {
      Headers.contentTypeHeader: [Headers.jsonContentType]
    });

void main() {
  test('refreshes once on 401 and retries the original request', () async {
    final store = MemoryTokenStore();
    await store.write('expired-token');
    await store.writeRefresh('refresh-1');

    var familiesCalls = 0;
    final dio = Dio();
    dio.httpClientAdapter = _FakeAdapter((options) async {
      if (options.path.contains('/auth/refresh')) {
        return _json({
          'success': true,
          'data': {
            'token': 'new-token',
            'refresh_token': 'refresh-2',
            'user': {'uuid': 'u1'}
          }
        }, 200);
      }
      familiesCalls++;
      if (options.headers['Authorization'] == 'Bearer new-token') {
        return _json({'success': true, 'data': {'ok': true}}, 200);
      }
      return _json(
          {'success': false, 'message': 'Unauthenticated', 'errors': {}}, 401);
    });

    final api = ApiClient(
        baseUrl: 'https://api.test/api/v1', tokenStore: store, dio: dio);

    expect(await api.get('/families'), {'ok': true});
    expect(store.token, 'new-token');
    expect(store.refreshToken, 'refresh-2');
    expect(familiesCalls, 2);
  });

  test('clears tokens and signals unauthorized when refresh fails', () async {
    final store = MemoryTokenStore();
    await store.write('expired-token');
    await store.writeRefresh('stale-refresh');
    var unauthorized = false;

    final dio = Dio();
    dio.httpClientAdapter = _FakeAdapter((options) async {
      if (options.path.contains('/auth/refresh')) {
        return _json(
            {'success': false, 'message': 'Unauthenticated', 'errors': {}}, 401);
      }
      return _json(
          {'success': false, 'message': 'Unauthenticated', 'errors': {}}, 401);
    });

    final api = ApiClient(
        baseUrl: 'https://api.test/api/v1',
        tokenStore: store,
        dio: dio,
        onUnauthorized: () async => unauthorized = true);

    await expectLater(api.get('/families'), throwsA(anything));
    expect(store.token, isNull);
    expect(store.refreshToken, isNull);
    expect(unauthorized, isTrue);
  });
}
