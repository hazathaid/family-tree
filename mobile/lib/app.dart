import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'app/router/app_router.dart';
import 'app/theme/app_theme.dart';
import 'core/providers.dart';

class FamilyTreeApp extends ConsumerWidget {
  const FamilyTreeApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    return MaterialApp.router(
      title: 'Family Tree Indonesia',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      themeMode: ThemeMode.light,
      routerConfig: router,
      builder: (context, child) => MediaQuery(
        data: MediaQuery.of(context).copyWith(
            textScaler:
                MediaQuery.textScalerOf(context).clamp(maxScaleFactor: 2)),
        child: child ?? const SizedBox.shrink(),
      ),
    );
  }
}

final routerProvider = Provider<GoRouter>((ref) => createAppRouter(
      session: ref.watch(sessionControllerProvider),
      environment: ref.watch(environmentProvider),
    ));
