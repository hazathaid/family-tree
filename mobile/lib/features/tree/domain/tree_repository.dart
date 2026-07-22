import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../core/models.dart';

abstract interface class TreeRepository {
  Future<FamilyTree> generate(String rootUuid,
      {required String mode, required int depth, required String layout});
  Future<Uint8List> export(String format, String rootUuid,
      {required String mode,
      required int depth,
      required String layout,
      required String paperSize,
      CancelToken? cancelToken,
      ProgressCallback? onProgress});
}
