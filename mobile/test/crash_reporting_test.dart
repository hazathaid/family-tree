import 'package:family_tree_mobile/core/crash/crash_reporting.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('CrashReportingService', () {
    test('does not record when disabled', () {
      final reporter = _RecordingReporter();
      final service = CrashReportingService(
        enabled: false,
        reporter: reporter,
      );

      service.recordError(
        StateError('boom'),
        StackTrace.current,
        context: {'family_uuid': 'abc'},
      );

      expect(reporter.records, isEmpty);
    });

    test('records when enabled', () {
      final reporter = _RecordingReporter();
      final service = CrashReportingService(
        enabled: true,
        reporter: reporter,
      );

      service.recordError(
        StateError('boom'),
        StackTrace.current,
        context: {'member_uuid': 'abc'},
      );

      expect(reporter.records, hasLength(1));
      expect(reporter.records.single.context, {
        'member_uuid': 'abc',
      });
    });

    test('redacts sensitive keys in context', () {
      final reporter = _RecordingReporter();
      final service = CrashReportingService(
        enabled: true,
        reporter: reporter,
      );

      service.recordError(
        StateError('boom'),
        StackTrace.current,
        context: {
          'family_uuid': 'abc',
          'token': 'secret-token',
          'authorization': 'Bearer secret',
          'password': 'hunter2',
          'api_key': 'key',
        },
      );

      final context = reporter.records.single.context!;
      expect(context['token'], '[REDACTED]');
      expect(context['authorization'], '[REDACTED]');
      expect(context['password'], '[REDACTED]');
      expect(context['api_key'], '[REDACTED]');
      expect(context['family_uuid'], 'abc');
    });

    test('redacts nested maps and lists recursively', () {
      final reporter = _RecordingReporter();
      final service = CrashReportingService(
        enabled: true,
        reporter: reporter,
      );

      service.recordError(
        StateError('boom'),
        StackTrace.current,
        context: {
          'auth': {'token': 'nested-secret', 'user_uuid': 'u1'},
          'items': [
            {'password': 'inner-secret', 'name': 'ok'},
          ],
        },
      );

      final context = reporter.records.single.context!;
      expect(context['auth'], {
        'token': '[REDACTED]',
        'user_uuid': 'u1',
      });
      expect(context['items'], [
        {'password': '[REDACTED]', 'name': 'ok'},
      ]);
    });

    test('does not leak context when disabled', () {
      final reporter = _RecordingReporter();
      final service = CrashReportingService(
        enabled: false,
        reporter: reporter,
      );

      service.recordError(
        StateError('boom'),
        StackTrace.current,
        context: {'token': 'never-leave-device'},
      );

      expect(reporter.records, isEmpty);
    });
  });
}

class _RecordingReporter implements CrashReporter {
  final records = <_Record>[];

  @override
  void recordError(
    Object error,
    StackTrace stackTrace, {
    Map<String, dynamic>? context,
  }) {
    records.add(_Record(error, stackTrace, context));
  }
}

class _Record {
  _Record(this.error, this.stackTrace, this.context);
  final Object error;
  final StackTrace stackTrace;
  final Map<String, dynamic>? context;
}
