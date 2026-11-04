import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/discovery/domain/discovery_models.dart';
import 'package:family_tree_mobile/features/discovery/presentation/discovery_screens.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  group('DiscoverySearchScreen', () {
    testWidgets('searches with filters and loads more results', (tester) async {
      tester.view.physicalSize = const Size(1000, 1800);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final discovery = FakeDiscoveryRepository()
        ..searchValue = const SearchResults(
            members: [testMember],
            articles: [testArticle],
            events: [],
            page: 1,
            hasMore: true);
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        discoveryRepositoryProvider.overrideWithValue(discovery),
      ], child: routedApp(const Scaffold(body: DiscoverySearchScreen()))));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Filter lanjutan'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField).at(0), 'budi');
      await tester.enterText(find.byType(TextField).at(1), 'Budi');
      await tester.enterText(find.byType(TextField).at(2), 'Bandung');
      await tester.enterText(find.byType(TextField).at(3), '2');
      await tester.enterText(find.byType(TextField).at(4), 'root');
      tester
          .widget<DropdownButtonFormField<String>>(
              find.byType(DropdownButtonFormField<String>))
          .onChanged!('alive');
      await tester.pumpAndSettle();
      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Cari'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();

      expect(find.text('Budi Santoso'), findsWidgets);
      expect(find.text('Sejarah Keluarga'), findsOneWidget);
      expect(find.text('Muat berikutnya'), findsOneWidget);
      await tester.tap(find.text('Muat berikutnya'));
      await tester.pumpAndSettle();
      expect(discovery.calls.where((call) => call == 'search').length, 2);

      await tester.tap(find.text('Budi Santoso').first);
      await tester.pumpAndSettle();
      expect(find.text('route:/members/member-uuid'), findsOneWidget);
    });

    testWidgets('shows empty and error states', (tester) async {
      tester.view.physicalSize = const Size(1000, 1600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final discovery = FakeDiscoveryRepository()
        ..searchValue = const SearchResults(
            members: [], articles: [], events: [], page: 1, hasMore: false);
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        discoveryRepositoryProvider.overrideWithValue(discovery),
      ], child: testApp(const Scaffold(body: DiscoverySearchScreen()))));
      await tester.pumpAndSettle();
      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Cari'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(find.text('Tidak ada hasil yang cocok.'), findsOneWidget);

      discovery.error = const AppError(AppErrorType.server, 'Gagal');
      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Cari'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(find.text('Gagal'), findsOneWidget);
    });
  });

  group('ReportsScreen', () {
    testWidgets('renders statistics and insights on phone and tablet',
        (tester) async {
      tester.view.physicalSize = const Size(900, 1400);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final discovery = FakeDiscoveryRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        discoveryRepositoryProvider.overrideWithValue(discovery),
      ], child: testApp(const ReportsScreen())));
      await tester.pumpAndSettle();
      expect(find.text('10'), findsOneWidget);
      expect(find.text('Bandung'), findsOneWidget);
      expect(find.text('2026'), findsOneWidget);
      expect(find.text('Jan'), findsOneWidget);

      await tester.tap(find.textContaining('Dari'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('OKE'));
      await tester.pumpAndSettle();
      expect(discovery.calls, contains('statistics'));
    });

    testWidgets('shows report errors', (tester) async {
      final discovery = FakeDiscoveryRepository()
        ..error = const AppError(AppErrorType.server, 'Laporan gagal');
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        discoveryRepositoryProvider.overrideWithValue(discovery),
      ], child: testApp(const ReportsScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Laporan gagal'), findsOneWidget);
    });
  });

  group('GamificationScreen', () {
    testWidgets('renders badges and leaderboards', (tester) async {
      final discovery = FakeDiscoveryRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        discoveryRepositoryProvider.overrideWithValue(discovery),
      ], child: testApp(const GamificationScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Kontributor'), findsOneWidget);
      expect(find.text('Budi'), findsNWidgets(2));
      expect(find.text('120 poin'), findsWidgets);
    });

    testWidgets('renders empty and error states', (tester) async {
      final discovery = FakeDiscoveryRepository()
        ..profileValue = const GamificationProfile(points: 0, badges: [])
        ..leaderboardValue = const [];
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        discoveryRepositoryProvider.overrideWithValue(discovery),
      ], child: testApp(const GamificationScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Belum ada badge.'), findsOneWidget);
      expect(find.text('Belum ada peringkat.'), findsWidgets);

      discovery.error = const AppError(AppErrorType.server, 'Gagal');
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        discoveryRepositoryProvider.overrideWithValue(discovery),
      ], child: testApp(const GamificationScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Gagal'), findsOneWidget);
    });
  });
}
