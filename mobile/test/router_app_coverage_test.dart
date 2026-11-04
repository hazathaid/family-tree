import 'package:family_tree_mobile/app.dart';
import 'package:family_tree_mobile/app/router/app_router.dart';
import 'package:family_tree_mobile/core/auth/session_controller.dart';
import 'package:family_tree_mobile/core/config/app_environment.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

AppEnvironment _environment({bool production = false}) =>
    AppEnvironment.validate(
        flavor: production ? AppFlavor.production : AppFlavor.development,
        apiBaseUrl: 'https://api.example.test/api/v1');

List<Override> _overrides({
  required SessionController session,
  required AppEnvironment environment,
  required GoRouter router,
}) {
  final account = FakeAccountRepository();
  return [
    routerProvider.overrideWithValue(router),
    sessionControllerProvider.overrideWithValue(session),
    environmentProvider.overrideWithValue(environment),
    currentFamilyProvider.overrideWith((ref) => testFamily),
    currentUserProvider.overrideWith((ref) => testUser),
    currentMemberUuidProvider.overrideWith((ref) => 'member-uuid'),
    authRepositoryProvider.overrideWithValue(FakeAuthRepository()),
    familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
    memberRepositoryProvider.overrideWithValue(FakeMemberRepository()),
    contentRepositoryProvider.overrideWithValue(FakeContentRepository()),
    discoveryRepositoryProvider.overrideWithValue(FakeDiscoveryRepository()),
    notificationRepositoryProvider
        .overrideWithValue(FakeNotificationRepository()),
    treeRepositoryProvider.overrideWithValue(FakeTreeRepository()),
    familyLinkRepositoryProvider.overrideWithValue(FakeFamilyLinkRepository()),
    accountRepositoryProvider.overrideWithValue(account),
    dashboardProvider.overrideWith((ref) => sampleDashboard),
    timelineProvider.overrideWith((ref) async => [sampleTimeline]),
    notificationsProvider.overrideWith((ref) async => [sampleNotification]),
    familiesProvider.overrideWith(
        (ref) => [testFamily, const Family(uuid: 'family-b', name: 'B')]),
    accountSessionsProvider.overrideWith((ref) async => account.sessionItems),
    notificationPreferencesProvider.overrideWith((ref) async =>
        const NotificationPreferences(
            email: true,
            push: true,
            eventReminders: true,
            familyUpdates: true)),
  ];
}

Future<void> _pump(
  WidgetTester tester, {
  required SessionController session,
  required AppEnvironment environment,
  bool wide = false,
  bool settle = true,
}) async {
  tester.view.physicalSize =
      wide ? const Size(1200, 1600) : const Size(390, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  final router = createAppRouter(session: session, environment: environment);
  await tester.pumpWidget(ProviderScope(
      overrides: _overrides(
          session: session, environment: environment, router: router),
      child: const FamilyTreeApp()));
  if (settle) {
    await tester.pumpAndSettle();
  } else {
    await tester.pump();
  }
}

void main() {
  testWidgets('shows splash while bootstrapping', (tester) async {
    final session = await createTestSession();
    await _pump(tester,
        session: session, environment: _environment(), settle: false);
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });

  testWidgets('redirects unauthenticated visitors to login', (tester) async {
    final session = await createTestSession();
    session.status = SessionStatus.unauthenticated;
    await _pump(tester, session: session, environment: _environment());
    expect(find.text('Masuk'), findsOneWidget);
    expect(session.intendedLocation, '/');
  });

  testWidgets('redirects verification, onboarding and family states',
      (tester) async {
    final verification = await createTestSession();
    verification.status = SessionStatus.needsVerification;
    await _pump(tester, session: verification, environment: _environment());
    expect(find.text('Verifikasi email'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());

    final onboarding = await createTestSession();
    onboarding.status = SessionStatus.needsOnboarding;
    await _pump(tester, session: onboarding, environment: _environment());
    expect(find.text('Mulai dokumentasikan keluarga'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());

    final family = await createTestSession();
    family.status = SessionStatus.needsFamily;
    await _pump(tester, session: family, environment: _environment());
    expect(find.text('Pilih keluarga'), findsOneWidget);
  });

  testWidgets('renders the authenticated shell on phone and tablet',
      (tester) async {
    final phone = await createTestSession(token: 'token');
    phone
      ..userUuid = 'user-uuid'
      ..status = SessionStatus.authenticated;
    await _pump(tester, session: phone, environment: _environment());
    expect(find.textContaining('Keluarga Santoso'), findsOneWidget);
    expect(find.byType(NavigationBar), findsOneWidget);
    await tester.tap(find.text('Keluarga').first);
    await tester.pumpAndSettle();
    expect(find.textContaining('Budi Santoso'), findsWidgets);
    await tester.tap(find.text('Aktivitas'));
    await tester.pumpAndSettle();
    expect(find.text('Linimasa keluarga'), findsOneWidget);
    await tester.tap(find.text('Lainnya'));
    await tester.pumpAndSettle();
    expect(find.text('Pengaturan keluarga'), findsOneWidget);

    await tester.pumpWidget(const SizedBox());
    final tablet = await createTestSession(token: 'token');
    tablet
      ..userUuid = 'user-uuid'
      ..status = SessionStatus.authenticated;
    await _pump(tester,
        session: tablet, environment: _environment(), wide: true);
    expect(find.byType(NavigationRail), findsOneWidget);
    expect(find.byType(NavigationBar), findsNothing);
    await tester.tap(find.text('Lainnya'));
    await tester.pumpAndSettle();
    expect(find.text('Pengaturan keluarga'), findsOneWidget);
  });

  testWidgets('renders every routed screen', (tester) async {
    final session = await createTestSession(token: 'token');
    session
      ..userUuid = 'user-uuid'
      ..status = SessionStatus.authenticated;
    final environment = _environment();
    final router = createAppRouter(session: session, environment: environment);
    await tester.pumpWidget(ProviderScope(
        overrides: _overrides(
            session: session, environment: environment, router: router),
        child: const FamilyTreeApp()));
    await tester.pumpAndSettle();

    for (final route in [
      '/two-factor-challenge?token=challenge',
      '/register',
      '/forgot-password',
      '/reset-password?token=token&email=a%40b.c',
      '/verify-email',
      '/onboarding',
      '/families/select',
      '/diagnostics',
      '/family/manage',
      '/family-links',
      '/family-links/link-uuid/tree',
      '/members',
      '/members/new',
      '/members/duplicates',
      '/members/member-uuid/edit',
      '/relationships',
      '/relationship-resolver',
      '/articles',
      '/articles/new',
      '/articles/article-uuid/edit',
      '/photos',
      '/photos/upload',
      '/photos/photo-uuid',
      '/events',
      '/events/new',
      '/events/event-uuid/edit',
      '/search',
      '/reports',
      '/gamification',
      '/account/profile',
      '/account/notifications',
      '/articles/article-uuid',
      '/events/event-uuid',
      '/notifications/notification-uuid',
      '/members/member-uuid',
      '/members/member-uuid/documents',
    ]) {
      router.go(route);
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull, reason: route);
    }
  });

  testWidgets('blocks diagnostics outside development', (tester) async {
    final session = await createTestSession(token: 'token');
    session
      ..userUuid = 'user-uuid'
      ..status = SessionStatus.authenticated;
    final environment = _environment(production: true);
    final router = createAppRouter(session: session, environment: environment);
    await tester.pumpWidget(ProviderScope(
        overrides: _overrides(
            session: session, environment: environment, router: router),
        child: const FamilyTreeApp()));
    await tester.pumpAndSettle();
    router.go('/diagnostics');
    await tester.pumpAndSettle();
    expect(find.textContaining('Keluarga Santoso'), findsOneWidget);
  });
}
