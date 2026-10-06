import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../core/models.dart';

abstract interface class TreeRepository {
  Future<FamilyTree> generate(String rootUuid,
      {required String mode, required int depth, required String layout});
  Future<FamilyMember> createRelative(
      String memberUuid, Map<String, dynamic> values);
  Future<Uint8List> export(String format, String rootUuid,
      {required String mode,
      required int depth,
      required String layout,
      required String paperSize,
      CancelToken? cancelToken,
      ProgressCallback? onProgress});
  Future<TreeExportJob> requestExport(String rootUuid,
      {required String format,
      required String mode,
      required int depth,
      required String layout,
      required String paperSize});
  Future<TreeExportJob> exportStatus(String uuid);
  Future<Uint8List> downloadExport(String uuid,
      {CancelToken? cancelToken, ProgressCallback? onProgress});
}
