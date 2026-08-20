import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/discovery/domain/discovery_models.dart';
import 'package:family_tree_mobile/features/discovery/domain/discovery_repository.dart';
import 'package:family_tree_mobile/features/discovery/presentation/discovery_screens.dart';
import 'package:family_tree_mobile/l10n/app_localizations.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

Widget _app(Widget home) => MaterialApp(
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      locale: const Locale('id'),
      home: home,
    );

void main() {
  const family = Family(uuid: 'family', name: 'Keluarga');

  test('phase 7 models preserve server generation, report and rank data', () {
    final search = SearchResults.fromJson({
      'members': [
        {
          'uuid': 'm',
          'family_uuid': 'family',
          'full_name': 'Budi',
          'is_alive': true,
          'generation': 2
        }
      ],
      'articles': [],
      'events': [],
      'pagination': {'page': 2, 'has_more': true}
    });
    final statistics = FamilyStatistics.fromJson({
      'total_members': 3,
      'alive_members': 2,
      'deceased_members': 1,
      'total_generations': 2,
      'members_by_generation': {'0': 1, '1': 2}
    });
    final leader = LeaderboardEntry.fromJson(
        {'rank': 1, 'uuid': 'u', 'name': 'Siti', 'points': 20, 'image': null});
    expect(search.members.single.generation, 2);
    expect(search.hasMore, isTrue);
    expect(statistics.generations.last.total, 2);
    expect(leader.rank, 1);
  });

  testWidgets('gamification uses server points badges and rankings',
      (tester) async {
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => family),
      discoveryRepositoryProvider.overrideWithValue(const _Discovery()),
    ], child: _app(const GamificationScreen())));
    await tester.pumpAndSettle();
    expect(find.text('25 poin'), findsNWidgets(2));
    expect(find.text('Penjaga Sejarah'), findsOneWidget);
    expect(find.text('Budi'), findsOneWidget);
    expect(find.text('Keluarga Besar'), findsOneWidget);
  });
}

class _Discovery implements DiscoveryRepository {
  const _Discovery();
  @override
  Future<SearchResults> search(String familyUuid,
          {String? keyword,
          String? name,
          String? city,
          String? status,
          int? generation,
          String? rootMemberUuid,
          int page = 1}) async =>
      const SearchResults(
          members: [], articles: [], events: [], page: 1, hasMore: false);
  @override
  Future<FamilyStatistics> statistics(String familyUuid) async =>
      const FamilyStatistics(
          totalMembers: 0,
          aliveMembers: 0,
          deceasedMembers: 0,
          totalGenerations: 0,
          generations: []);
  @override
  Future<ActivityReport> activity(
          String familyUuid, DateTime from, DateTime to) async =>
      const ActivityReport(activeUsers: 0, uploads: 0, articles: 0);
  @override
  Future<ReportInsights> insights(
          String familyUuid, DateTime from, DateTime to) async =>
      const ReportInsights(cities: [], growth: [], activity: []);
  @override
  Future<GamificationProfile> profile(String familyUuid) async =>
      const GamificationProfile(points: 25, badges: [
        BadgeAward(
            uuid: 'b', name: 'Penjaga Sejarah', description: 'Arsip keluarga')
      ]);
  @override
  Future<List<LeaderboardEntry>> userLeaderboard(String familyUuid) async =>
      const [LeaderboardEntry(rank: 1, uuid: 'u', name: 'Budi', points: 25)];
  @override
  Future<List<LeaderboardEntry>> familyLeaderboard() async => const [
        LeaderboardEntry(
            rank: 1, uuid: 'f', name: 'Keluarga Besar', points: 100)
      ];
}
