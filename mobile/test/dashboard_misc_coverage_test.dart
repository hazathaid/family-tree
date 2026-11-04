import 'dart:async';

import 'package:family_tree_mobile/core/config/app_environment.dart';
import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/dashboard/dashboard_screen.dart';
import 'package:family_tree_mobile/features/diagnostics/presentation/diagnostics_screen.dart';
import 'package:family_tree_mobile/features/notifications/notifications_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  group('DashboardScreen', () {
    testWidgets('renders sections on phone and tablet', (tester) async {
      for (final size in [const Size(390, 900), const Size(900, 1200)]) {
        tester.view.physicalSize = size;
        tester.view.devicePixelRatio = 1;
        await tester.pumpWidget(ProviderScope(overrides: [
          currentFamilyProvider.overrideWith((ref) => testFamily),
          dashboardProvider.overrideWith((ref) => sampleDashboard),
        ], child: routedApp(const Scaffold(body: DashboardScreen()))));
        await tester.pumpAndSettle();
        expect(find.textContaining('Keluarga Santoso'), findsOneWidget);
        expect(find.text('12'), findsOneWidget);
        expect(find.text('Anggota ditambah'), findsOneWidget);
        expect(find.text('Reuni'), findsOneWidget);
        expect(find.textContaining('Bandung'), findsWidgets);
      }
      addTearDown(tester.view.reset);
    });

    testWidgets('taps stats, sections and see-all action', (tester) async {
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        dashboardProvider.overrideWith((ref) => sampleDashboard),
      ], child: routedApp(const Scaffold(body: DashboardScreen()))));
      await tester.pumpAndSettle();

      await tester.tap(find.text('12'));
      await tester.pumpAndSettle();
      expect(find.text('route:/members'), findsOneWidget);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        dashboardProvider.overrideWith((ref) => sampleDashboard),
      ], child: routedApp(const Scaffold(body: DashboardScreen()))));
      await tester.pumpAndSettle();
      tester
          .widget<TextButton>(find.widgetWithText(TextButton, 'Lihat semua'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(find.text('route:/account/notifications'), findsOneWidget);
    });

    testWidgets('renders loading and error states', (tester) async {
      final pending = Completer<DashboardSummary>();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        dashboardProvider.overrideWith((ref) => pending.future),
      ], child: testApp(const Scaffold(body: DashboardScreen()))));
      await tester.pump();
      expect(find.byType(CircularProgressIndicator), findsNothing);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        dashboardProvider.overrideWith(
            (ref) => throw const AppError(AppErrorType.server, 'Gagal')),
      ], child: testApp(const Scaffold(body: DashboardScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Coba lagi'), findsOneWidget);
    });
  });

  group('NotificationsScreen', () {
    testWidgets('renders items, marks read and opens targets', (tester) async {
      final repository = FakeNotificationRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        notificationRepositoryProvider.overrideWithValue(repository),
        notificationsProvider.overrideWith((ref) async => repository.items),
      ], child: routedApp(const Scaffold(body: NotificationsScreen()))));
      await tester.pumpAndSettle();

      expect(find.text('Notifikasi'), findsWidgets);
      expect(find.text('Pesan keluarga'), findsOneWidget);
      await tester.tap(find.text('Baca semua'));
      await tester.pumpAndSettle();
      expect(repository.calls, contains('markAllRead'));

      await tester.tap(find.text('Pesan keluarga'));
      await tester.pumpAndSettle();
      expect(repository.calls, contains('markRead'));
      expect(find.text('route:/members/member-uuid'), findsOneWidget);
    });

    testWidgets('renders empty, error and read states', (tester) async {
      final repository = FakeNotificationRepository()..items = [];
      await tester.pumpWidget(ProviderScope(overrides: [
        notificationRepositoryProvider.overrideWithValue(repository),
        notificationsProvider.overrideWith((ref) async => []),
      ], child: testApp(const Scaffold(body: NotificationsScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Belum ada notifikasi.'), findsOneWidget);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        notificationRepositoryProvider.overrideWithValue(repository),
        notificationsProvider.overrideWith((ref) async {
          throw const AppError(AppErrorType.server, 'Gagal');
        }),
      ], child: testApp(const Scaffold(body: NotificationsScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Coba lagi'), findsOneWidget);

      final read = FakeNotificationRepository()
        ..items = [
          const AppNotification(
              uuid: 'read', title: 'Dibaca', body: 'Isi', isRead: true)
        ];
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        notificationRepositoryProvider.overrideWithValue(read),
        notificationsProvider.overrideWith((ref) async => read.items),
      ], child: routedApp(const Scaffold(body: NotificationsScreen()))));
      await tester.pumpAndSettle();
      expect(find.byIcon(Icons.notifications_none), findsOneWidget);
      await tester.tap(find.text('Isi'));
      await tester.pumpAndSettle();
      expect(read.calls, isNot(contains('markRead')));
    });
  });

  testWidgets('DiagnosticsScreen renders environment details', (tester) async {
    final environment = AppEnvironment.validate(
        flavor: AppFlavor.development,
        apiBaseUrl: 'http://localhost:8000/api/v1');
    await tester
        .pumpWidget(testApp(DiagnosticsScreen(environment: environment)));
    await tester.pump();
    expect(find.text('development'), findsOneWidget);
    expect(find.text('http://localhost:8000'), findsOneWidget);
  });
}
