import 'package:family_tree_mobile/app.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';

void main() {
  testWidgets('unauthenticated user sees login screen', (tester) async {
    final router = GoRouter(initialLocation: '/login', routes: [
      GoRoute(
          path: '/login',
          builder: (context, state) => const FamilyTreeAppTestLogin()),
    ]);
    await tester.pumpWidget(ProviderScope(
      overrides: [routerProvider.overrideWithValue(router)],
      child: const FamilyTreeApp(),
    ));
    await tester.pumpAndSettle();
    expect(find.text('Family Tree Indonesia'), findsOneWidget);
    expect(find.text('Masuk'), findsOneWidget);
  });
}

class FamilyTreeAppTestLogin extends StatelessWidget {
  const FamilyTreeAppTestLogin({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(
      body: Column(children: [Text('Family Tree Indonesia'), Text('Masuk')]));
}
