import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/auth/session_controller.dart';
import '../../core/config/app_environment.dart';
import '../../features/auth/login_screen.dart';
import '../../features/auth/auth_screens.dart';
import '../../features/account/presentation/account_screen.dart';
import '../../features/family/presentation/family_onboarding_screen.dart';
import '../../features/dashboard/dashboard_screen.dart';
import '../../features/diagnostics/presentation/diagnostics_screen.dart';
import '../../features/notifications/notifications_screen.dart';
import '../../features/tree/tree_screen.dart';

GoRouter createAppRouter(
        {required SessionController session,
        required AppEnvironment environment}) =>
    GoRouter(
      initialLocation: '/',
      refreshListenable: session,
      redirect: (context, state) {
        final location = state.uri.toString();
        final public = location.startsWith('/login') ||
            location.startsWith('/register') ||
            location.startsWith('/forgot-password') ||
            location.startsWith('/reset-password') ||
            location.startsWith('/verify-email');
        if (session.status == SessionStatus.bootstrapping) {
          return location == '/splash' ? null : '/splash';
        }
        if (session.status == SessionStatus.unauthenticated && !public) {
          session.intendedLocation = location == '/splash' ? '/' : location;
          return '/login';
        }
        if (session.status == SessionStatus.authenticated &&
            (location == '/login' || location == '/splash')) {
          final destination = session.intendedLocation ?? '/';
          session.intendedLocation = null;
          return destination;
        }
        if (session.status == SessionStatus.needsVerification &&
            !location.startsWith('/verify-email')) {
          return '/verify-email';
        }
        if (session.status == SessionStatus.needsOnboarding &&
            location != '/onboarding') {
          return '/onboarding';
        }
        if (session.status == SessionStatus.needsFamily &&
            location != '/families/select') {
          return '/families/select';
        }
        if (location == '/diagnostics' && !environment.diagnosticsEnabled) {
          return '/';
        }
        return null;
      },
      routes: [
        GoRoute(
            path: '/splash',
            builder: (context, state) => const _SplashScreen()),
        GoRoute(
            path: '/login', builder: (context, state) => const LoginScreen()),
        GoRoute(path: '/register', builder: (_, __) => const RegisterScreen()),
        GoRoute(
            path: '/forgot-password',
            builder: (_, __) => const ForgotPasswordScreen()),
        GoRoute(
            path: '/reset-password',
            builder: (context, state) => ResetPasswordScreen(
                token: state.uri.queryParameters['token'] ?? '',
                email: state.uri.queryParameters['email'] ?? '')),
        GoRoute(
            path: '/verify-email',
            builder: (context, state) => VerificationScreen(
                id: state.uri.queryParameters['id'],
                hash: state.uri.queryParameters['hash'],
                query: state.uri.queryParameters)),
        GoRoute(
            path: '/onboarding',
            builder: (context, state) => const FamilyOnboardingScreen()),
        GoRoute(
            path: '/families/select',
            builder: (context, state) => const FamilySelectorScreen()),
        GoRoute(
            path: '/diagnostics',
            builder: (context, state) =>
                DiagnosticsScreen(environment: environment)),
        StatefulShellRoute.indexedStack(
          builder: (context, state, shell) => _AdaptiveShell(shell: shell),
          branches: [
            StatefulShellBranch(routes: [
              GoRoute(
                  path: '/',
                  builder: (context, state) => const DashboardScreen())
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                  path: '/tree',
                  builder: (context, state) => const TreeScreen())
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                  path: '/activity',
                  builder: (context, state) =>
                      const _Placeholder(title: 'Aktivitas'))
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                  path: '/account',
                  builder: (context, state) => const AccountScreen(),
                  routes: [
                    GoRoute(
                        path: 'notifications',
                        builder: (_, __) => const NotificationsScreen()),
                  ])
            ]),
          ],
        ),
        for (final route in const [
          '/members/:uuid',
          '/articles/:uuid',
          '/events/:uuid',
          '/notifications/:uuid'
        ])
          GoRoute(
              path: route,
              builder: (context, state) => _Placeholder(
                  title: 'Tujuan tautan',
                  detail: state.pathParameters['uuid'])),
      ],
    );

class _AdaptiveShell extends StatelessWidget {
  const _AdaptiveShell({required this.shell});
  final StatefulNavigationShell shell;
  static const destinations = [
    NavigationDestination(
        icon: Icon(Icons.home_outlined),
        selectedIcon: Icon(Icons.home),
        label: 'Dashboard'),
    NavigationDestination(
        icon: Icon(Icons.account_tree_outlined),
        selectedIcon: Icon(Icons.account_tree),
        label: 'Keluarga'),
    NavigationDestination(
        icon: Icon(Icons.history_outlined),
        selectedIcon: Icon(Icons.history),
        label: 'Aktivitas'),
    NavigationDestination(icon: Icon(Icons.more_horiz), label: 'Lainnya'),
  ];
  @override
  Widget build(BuildContext context) {
    final tablet = MediaQuery.sizeOf(context).width >= 600;
    final body = SafeArea(child: shell);
    return Scaffold(
      appBar: AppBar(title: const Text('Family Tree Indonesia'), actions: [
        IconButton(
            tooltip: 'Notifikasi',
            onPressed: () => context.go('/account/notifications'),
            icon: const Icon(Icons.notifications_outlined))
      ]),
      body: tablet
          ? Row(children: [
              NavigationRail(
                  selectedIndex: shell.currentIndex,
                  onDestinationSelected: shell.goBranch,
                  labelType: NavigationRailLabelType.all,
                  destinations: destinations
                      .map((item) => NavigationRailDestination(
                          icon: item.icon,
                          selectedIcon: item.selectedIcon,
                          label: Text(item.label)))
                      .toList()),
              const VerticalDivider(width: 1),
              Expanded(child: body)
            ])
          : body,
      bottomNavigationBar: tablet
          ? null
          : NavigationBar(
              selectedIndex: shell.currentIndex,
              onDestinationSelected: shell.goBranch,
              destinations: destinations),
    );
  }
}

class _SplashScreen extends StatelessWidget {
  const _SplashScreen();
  @override
  Widget build(BuildContext context) => Scaffold(
      body: Center(
          child: Semantics(
              label: 'Memulai aplikasi',
              liveRegion: true,
              child: CircularProgressIndicator())));
}

class _Placeholder extends StatelessWidget {
  const _Placeholder({required this.title, this.detail});
  final String title;
  final String? detail;
  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: Text(title)),
      body: Center(
          child: Text(detail == null ? title : '$title\n$detail',
              textAlign: TextAlign.center)));
}
