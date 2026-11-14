import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/members/presentation/member_screens.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  const family = Family(
      uuid: 'family-uuid', name: 'Keluarga Santoso', currentUserRole: 'owner');

  test('member spouse model parses marriage state and action flags', () {
    final spouse = MemberSpouse.fromJson({
      'relationship_uuid': 'relationship-uuid',
      'spouse_uuid': 'spouse-uuid',
      'spouse_name': 'Dewi',
      'label': 'Mantan Istri',
      'gender': 'female',
      'start_date': '2010-01-01',
      'end_date': '2024-06-01',
      'end_reason': 'divorced',
      'is_ended': true,
      'can_end': false,
      'can_correct': true,
      'can_reopen': true,
    });

    expect(spouse.label, 'Mantan Istri');
    expect(spouse.startDate?.year, 2010);
    expect(spouse.endDate?.year, 2024);
    expect(spouse.isEnded, isTrue);
    expect(spouse.canCorrect, isTrue);
    expect(spouse.canReopen, isTrue);
  });

  testWidgets('member detail shows spouses and opens the marriage end sheet',
      (tester) async {
    final repository = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      memberRepositoryProvider.overrideWithValue(repository),
      currentFamilyProvider.overrideWith((ref) => family),
    ], child: testApp(const MemberDetailScreen(uuid: 'member-uuid'))));
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(find.text('Dewi Santoso'), 300);
    expect(find.text('Dewi Santoso'), findsOneWidget);
    expect(find.textContaining('Istri'), findsWidgets);

    await tester.scrollUntilVisible(find.byType(PopupMenuButton<String>), 300);
    await tester.tap(find.byType(PopupMenuButton<String>));
    await tester.pumpAndSettle();
    expect(find.text('Catat akhir pernikahan'), findsOneWidget);

    await tester.tap(find.text('Catat akhir pernikahan'));
    await tester.pumpAndSettle();
    expect(find.text('Tanggal berakhir'), findsOneWidget);
    expect(find.text('Alasan'), findsOneWidget);

    await tester.tap(find.text('Batal'));
    await tester.pumpAndSettle();
  });

  testWidgets('member detail reopens a marriage after confirmation',
      (tester) async {
    final repository = FakeMemberRepository()
      ..spouseItems = const [
        MemberSpouse(
            relationshipUuid: 'relationship-uuid',
            spouseUuid: 'spouse-uuid',
            spouseName: 'Dewi Santoso',
            label: 'Mantan Istri',
            gender: 'female',
            endDate: null,
            endReason: 'divorced',
            isEnded: true,
            canCorrect: true,
            canReopen: true),
      ];
    await tester.pumpWidget(ProviderScope(overrides: [
      memberRepositoryProvider.overrideWithValue(repository),
      currentFamilyProvider.overrideWith((ref) => family),
    ], child: testApp(const MemberDetailScreen(uuid: 'member-uuid'))));
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(find.text('Dewi Santoso'), 300);
    await tester.scrollUntilVisible(find.byType(PopupMenuButton<String>), 300);
    await tester.tap(find.byType(PopupMenuButton<String>));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Batalkan perceraian'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Status pernikahan akan kembali aktif'),
        findsOneWidget);

    await tester.tap(find.widgetWithText(FilledButton, 'Batalkan perceraian'));
    await tester.pumpAndSettle();

    expect(repository.calls, contains('reopenMarriage'));
  });
}
