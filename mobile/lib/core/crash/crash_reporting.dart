import 'package:flutter/foundation.dart';

/// Sink for crash reports. Implementations must never receive tokens,
/// credentials, or sensitive payloads; the [CrashReportingService] scrubs
/// context before forwarding it here.
abstract interface class CrashReporter {
  void recordError(
    Object error,
    StackTrace stackTrace, {
    Map<String, dynamic>? context,
  });
}

/// Default reporter used unless a real provider (for example Sentry) is
/// approved and wired through [crashReporterProvider]. Recording is a no-op,
/// so crash reporting is effectively disabled by default.
final class NoopCrashReporter implements CrashReporter {
  const NoopCrashReporter();

  @override
  void recordError(
    Object error,
    StackTrace stackTrace, {
    Map<String, dynamic>? context,
  }) {
    // Intentionally empty: crash reporting is opt-in.
  }
}

/// Opt-in crash reporting gate.
///
/// Recording only happens when [enabled] is true. [context] maps are scrubbed
/// for sensitive keys before they reach the [CrashReporter], so secrets never
/// leave the device even if a caller accidentally attaches them.
class CrashReportingService {
  CrashReportingService({
    required this.enabled,
    CrashReporter reporter = const NoopCrashReporter(),
  }) : _reporter = reporter;

  final bool enabled;
  final CrashReporter _reporter;

  static const Set<String> _sensitiveKeys = {
    'token',
    'access_token',
    'refresh_token',
    'authorization',
    'api_key',
    'apikey',
    'password',
    'secret',
    'cookie',
    'credential',
  };

  void recordError(
    Object error,
    StackTrace stackTrace, {
    Map<String, dynamic>? context,
  }) {
    if (!enabled) {
      return;
    }
    _reporter.recordError(error, stackTrace, context: scrub(context));
  }

  @visibleForTesting
  Map<String, dynamic>? scrub(Map<String, dynamic>? context) {
    if (context == null) {
      return null;
    }

    final scrubbed = <String, dynamic>{};

    for (final entry in context.entries) {
      if (_sensitiveKeys.contains(entry.key.toLowerCase())) {
        scrubbed[entry.key] = '[REDACTED]';
      } else if (entry.value is Map<String, dynamic>) {
        scrubbed[entry.key] =
            scrub(entry.value as Map<String, dynamic>) ?? <String, dynamic>{};
      } else if (entry.value is List) {
        scrubbed[entry.key] = (entry.value as List)
            .map((item) => item is Map<String, dynamic>
                ? scrub(item) ?? <String, dynamic>{}
                : item)
            .toList();
      } else {
        scrubbed[entry.key] = entry.value;
      }
    }

    return scrubbed;
  }
}
