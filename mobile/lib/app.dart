import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/providers.dart';
import 'features/auth/login_screen.dart';
import 'features/dashboard/dashboard_screen.dart';
import 'features/notifications/notifications_screen.dart';
import 'features/tree/tree_screen.dart';

class FamilyTreeApp extends ConsumerStatefulWidget {
  const FamilyTreeApp({super.key});

  @override
  ConsumerState<FamilyTreeApp> createState() => _FamilyTreeAppState();
}

class _FamilyTreeAppState extends ConsumerState<FamilyTreeApp> {
  late bool _authenticated;

  @override
  void initState() {
    super.initState();
    _authenticated = ref.read(apiClientProvider).hasToken;
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'Family Tree Indonesia',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xff1e88e5)),
          scaffoldBackgroundColor: const Color(0xfff5f7fa),
          useMaterial3: true,
          inputDecorationTheme: const InputDecorationTheme(border: OutlineInputBorder()),
        ),
        home: _authenticated
            ? const _HomeShell()
            : LoginScreen(onLoggedIn: () => setState(() => _authenticated = true)),
      );
}

class _HomeShell extends StatefulWidget {
  const _HomeShell();

  @override
  State<_HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<_HomeShell> {
  int index = 0;
  static const pages = [DashboardScreen(), TreeScreen(), NotificationsScreen()];
  static const titles = ['Dashboard', 'Family Tree', 'Notifikasi'];

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(titles[index])),
        body: IndexedStack(index: index, children: pages),
        bottomNavigationBar: NavigationBar(
          selectedIndex: index,
          onDestinationSelected: (value) => setState(() => index = value),
          destinations: const [
            NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
            NavigationDestination(icon: Icon(Icons.account_tree_outlined), selectedIcon: Icon(Icons.account_tree), label: 'Tree'),
            NavigationDestination(icon: Icon(Icons.notifications_outlined), selectedIcon: Icon(Icons.notifications), label: 'Notifikasi'),
          ],
        ),
      );
}
