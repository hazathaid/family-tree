import 'package:family_tree_mobile/core/auth/session_controller.dart';
import 'package:family_tree_mobile/core/config/app_environment.dart';
import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/http/page_data.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/storage/secure_token_store.dart';
import 'package:family_tree_mobile/core/widgets/app_components.dart';
import 'package:family_tree_mobile/core/widgets/async_states.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  group('ScopedCache', () {
    test('stores, scopes and expires entries', () async {
      final cache = await createTestCache(maximumEntries: 2);
      expect(cache.get('missing', ttl: const Duration(minutes: 1)), isNull);

      await cache.put('user:family:members', {'name': 'Budi'});
      final entry =
          cache.get('user:family:members', ttl: const Duration(minutes: 1));
      expect(entry, isNotNull);
      expect(entry!.value['name'], 'Budi');
      expect(entry.isStale, isFalse);
      expect(cache.get('user:family:members', ttl: Duration.zero)!.isStale,
          isTrue);

      await cache.saveActiveFamily('user', 'family');
      expect(cache.activeFamily('user'), 'family');
      expect(cache.activeFamily('other'), isNull);

      await cache.put('user:family:second', 2);
      await cache.put('user:family:third', 3);
      expect((await createTestCache(maximumEntries: 2)).maximumEntries, 2);
      expect(cache.maximumEntries, 2);

      await cache.put('other:family:one', 1);
      await cache.clearScope(userUuid: 'user', familyUuid: 'family');
      expect(cache.get('user:family:members', ttl: const Duration(days: 1)),
          isNull);
      await cache.clearScope(userUuid: 'other');
      expect(
          cache.get('other:family:one', ttl: const Duration(days: 1)), isNull);
      await cache.clearAll();
      expect(cache.activeFamily('user'), isNull);
    });

    test('key joins user, family and query', () async {
      final cache = await createTestCache();
      expect(cache.key(userUuid: 'u', familyUuid: 'f', query: 'q'), 'u:f:q');
    });
  });

  group('SessionController', () {
    test('bootstraps unauthenticated and authenticated sessions', () async {
      final anonymous = await createTestSession();
      expect(anonymous.status, SessionStatus.bootstrapping);
      await anonymous.bootstrap();
      expect(anonymous.status, SessionStatus.unauthenticated);

      final signedIn = await createTestSession(token: 'token');
      await signedIn.bootstrap();
      expect(signedIn.status, SessionStatus.authenticated);
    });

    test('resolves verification, onboarding and family states', () async {
      final cache = await createTestCache();
      final session = await createTestSession(cache: cache);

      session.resolveUser(uuid: 'user', verified: false, familyCount: 0);
      expect(session.status, SessionStatus.needsVerification);

      session.resolveUser(uuid: 'user', verified: true, familyCount: 0);
      expect(session.status, SessionStatus.needsOnboarding);

      session.resolveUser(uuid: 'user', verified: true, familyCount: 1);
      expect(session.status, SessionStatus.needsFamily);
      expect(session.activeFamilyUuid, isNull);

      await cache.saveActiveFamily('user', 'family');
      session.resolveUser(uuid: 'user', verified: true, familyCount: 1);
      expect(session.status, SessionStatus.authenticated);
      expect(session.activeFamilyUuid, 'family');

      session.requireFamilySelection();
      expect(session.status, SessionStatus.needsFamily);

      session.authenticated();
      expect(session.status, SessionStatus.authenticated);

      session.familySelected('family-2');
      expect(session.activeFamilyUuid, 'family-2');

      await session.endSession();
      expect(session.status, SessionStatus.unauthenticated);
      expect(session.userUuid, isNull);
    });

    test('ignores family selection without a user', () async {
      final session = await createTestSession();
      session.familySelected('family');
      expect(session.activeFamilyUuid, isNull);
    });
  });

  group('MemoryTokenStore', () {
    test('stores and clears both tokens', () async {
      final store = MemoryTokenStore();
      expect(await store.read(), isNull);
      expect(await store.readRefresh(), isNull);
      await store.write('token');
      await store.writeRefresh('refresh');
      expect(await store.read(), 'token');
      expect(await store.readRefresh(), 'refresh');
      await store.clear();
      expect(await store.read(), isNull);
      expect(await store.readRefresh(), isNull);
    });
  });

  group('PageData', () {
    test('parses pagination and computes hasMore', () {
      final page = PageData<FamilyMember>.fromJson({
        'data': [
          {
            'uuid': 'member',
            'family_uuid': 'family',
            'full_name': 'Budi',
            'is_alive': true
          }
        ],
        'current_page': 2,
        'last_page': 3,
        'total': 30
      }, FamilyMember.fromJson);
      expect(page.items.single.fullName, 'Budi');
      expect(page.currentPage, 2);
      expect(page.hasMore, isTrue);
      expect(page.total, 30);

      final legacy = PageData<FamilyMember>.fromJson(
          {'data': const [], 'page': 3}, FamilyMember.fromJson);
      expect(legacy.currentPage, 3);
      expect(legacy.lastPage, 1);
      expect(legacy.hasMore, isFalse);
      expect(legacy.total, isNull);
    });
  });

  group('AppEnvironment', () {
    test('validates URL and flavor combinations', () {
      final environment = AppEnvironment.validate(
          flavor: AppFlavor.production,
          apiBaseUrl: 'https://api.example.test/api/v1');
      expect(environment.diagnosticsEnabled, isFalse);
      expect(environment.sanitizedHost, 'https://api.example.test');

      final development = AppEnvironment.validate(
          flavor: AppFlavor.development,
          apiBaseUrl: 'http://localhost:8000/api/v1');
      expect(development.diagnosticsEnabled, isTrue);
      expect(development.sanitizedHost, 'http://localhost:8000');

      expect(
          () => AppEnvironment.validate(
              flavor: AppFlavor.production,
              apiBaseUrl: 'http://api.example.test/api/v1'),
          throwsStateError);
      expect(
          () => AppEnvironment.validate(
              flavor: AppFlavor.development, apiBaseUrl: 'api/v1'),
          throwsStateError);
      expect(
          () => AppEnvironment.validate(
              flavor: AppFlavor.development,
              apiBaseUrl: 'ftp://api.example.test/api/v1'),
          throwsStateError);
      expect(
          () => AppEnvironment.validate(
              flavor: AppFlavor.development,
              apiBaseUrl: 'https://api.example.test/v1'),
          throwsStateError);
    });
  });

  group('AppError', () {
    test('marks transient failures retryable', () {
      for (final type in [
        AppErrorType.offline,
        AppErrorType.timeout,
        AppErrorType.rateLimited,
        AppErrorType.server
      ]) {
        expect(AppError(type, 'x').isRetryable, isTrue);
      }
      for (final type in [
        AppErrorType.unauthorized,
        AppErrorType.forbidden,
        AppErrorType.notFound,
        AppErrorType.validation,
        AppErrorType.cancelled,
        AppErrorType.unknown
      ]) {
        expect(AppError(type, 'x').isRetryable, isFalse);
      }
      expect(AppError(AppErrorType.validation, 'x').toString(), 'x');
    });
  });

  group('shared widgets', () {
    testWidgets('render async states and confirmation actions', (tester) async {
      var retried = 0;
      await tester.pumpWidget(testApp(Scaffold(
          body: ListView(children: [
        const AppStatusBadge(label: 'Aktif'),
        const AppSkeleton(lines: 2),
        AppErrorState(message: 'Gagal memuat', onRetry: () => retried++),
        AppEmptyState(
            title: 'Kosong',
            message: 'Belum ada data',
            action:
                TextButton(onPressed: () {}, child: const Text('Tindakan'))),
        StaleDataBanner(updatedAt: DateTime(2026), onRetry: () => retried++)
      ]))));
      await tester.pump();
      expect(find.text('Kosong'), findsOneWidget);
      expect(find.text('Gagal memuat'), findsOneWidget);
      expect(find.byType(Chip), findsOneWidget);

      await tester.tap(find.text('Coba lagi').first);
      await tester.pump();
      expect(retried, 1);
      await tester.ensureVisible(find.text('Coba lagi').last);
      await tester.tap(find.text('Coba lagi').last);
      await tester.pump();
      expect(retried, 2);
    });

    testWidgets('showAppConfirmation returns the selected result',
        (tester) async {
      bool? confirmed;
      await tester.pumpWidget(testApp(Scaffold(
          body: Builder(
              builder: (context) => TextButton(
                  onPressed: () async {
                    confirmed = await showAppConfirmation(context,
                        title: 'Hapus', message: 'Yakin?', destructive: true);
                  },
                  child: const Text('Buka'))))));
      await tester.tap(find.text('Buka'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Lanjutkan'));
      await tester.pumpAndSettle();
      expect(confirmed, isTrue);

      await tester.tap(find.text('Buka'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Batal'));
      await tester.pumpAndSettle();
      expect(confirmed, isFalse);

      await tester.pumpWidget(testApp(Scaffold(
          body: Builder(
              builder: (context) => TextButton(
                  onPressed: () => showAppSnackBar(context, 'Pesan'),
                  child: const Text('Snack'))))));
      await tester.tap(find.text('Snack'));
      await tester.pump();
      expect(find.text('Pesan'), findsOneWidget);
    });
  });

  test('test fixtures expose expected defaults', () {
    expect(testUser.isVerified, isTrue);
    expect(testFamily.canManageRoles, isTrue);
    expect(sampleDashboard.totalMembers, 12);
    expect(sampleNotification.targetPath, '/members/member-uuid');
    expect(sampleTimeline.targetPath, '/members/member-uuid');
  });
}
