import 'package:family_tree_mobile/app.dart';
import 'package:family_tree_mobile/core/api_client.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('unauthenticated user sees login screen', (tester) async {
    final api = ApiClient(baseUrl: 'https://example.test/api/v1');
    await tester.pumpWidget(ProviderScope(
      overrides: [apiClientProvider.overrideWithValue(api)],
      child: const FamilyTreeApp(),
    ));
    expect(find.text('Family Tree Indonesia'), findsOneWidget);
    expect(find.text('Masuk'), findsOneWidget);
  });
}
