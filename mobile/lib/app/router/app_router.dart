import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/auth/session_controller.dart';
import '../../core/config/app_environment.dart';
import '../../core/models.dart';
import '../../features/auth/login_screen.dart';
import '../../features/auth/auth_screens.dart';
import '../../features/account/presentation/account_screen.dart';
import '../../features/family/presentation/family_onboarding_screen.dart';
import '../../features/family/presentation/family_management_screen.dart';
import '../../features/dashboard/dashboard_screen.dart';
import '../../features/diagnostics/presentation/diagnostics_screen.dart';
import '../../features/notifications/notifications_screen.dart';
import '../../features/members/presentation/member_screens.dart';
import '../../features/tree/tree_screen.dart';
import '../../features/content/domain/content_models.dart';
import '../../features/content/presentation/content_screens.dart';
import '../../features/discovery/presentation/discovery_screens.dart';

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
        GoRoute(
            path: '/family/manage',
            builder: (_, __) => const FamilyManagementScreen()),
        GoRoute(
            path: '/members',
            builder: (_, __) => const MemberDirectoryScreen()),
        GoRoute(
            path: '/members/new', builder: (_, __) => const MemberFormScreen()),
        GoRoute(
            path: '/members/:uuid/edit',
            builder: (_, state) =>
                MemberFormScreen(member: state.extra as FamilyMember?)),
        GoRoute(
            path: '/relationships',
            builder: (_, __) => const RelationshipManagementScreen()),
        GoRoute(
            path: '/relationship-resolver',
            builder: (_, __) => const RelationshipResolverScreen()),
        GoRoute(
            path: '/articles', builder: (_, __) => const ArticleListScreen()),
        GoRoute(
            path: '/articles/new',
            builder: (_, __) => const ArticleEditorScreen()),
        GoRoute(
            path: '/articles/:uuid/edit',
            builder: (_, state) =>
                ArticleEditorScreen(article: state.extra as Article?)),
        GoRoute(path: '/photos', builder: (_, __) => const GalleryScreen()),
        GoRoute(
            path: '/photos/upload',
            builder: (_, __) => const PhotoUploadScreen()),
        GoRoute(
            path: '/photos/:uuid',
            builder: (_, state) =>
                PhotoDetailScreen(uuid: state.pathParameters['uuid']!)),
        GoRoute(path: '/events', builder: (_, __) => const EventListScreen()),
        GoRoute(
            path: '/events/new', builder: (_, __) => const EventFormScreen()),
        GoRoute(
            path: '/events/:uuid/edit',
            builder: (_, state) =>
                EventFormScreen(event: state.extra as FamilyEvent?)),
        GoRoute(
            path: '/search', builder: (_, __) => const DiscoverySearchScreen()),
        GoRoute(path: '/reports', builder: (_, __) => const ReportsScreen()),
        GoRoute(
            path: '/gamification',
            builder: (_, __) => const GamificationScreen()),
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
                  builder: (context, state) => const TimelineScreen())
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                  path: '/account',
                  builder: (context, state) => const _MoreScreen(),
                  routes: [
                    GoRoute(
                        path: 'profile',
                        builder: (_, __) => const AccountScreen()),
                    GoRoute(
                        path: 'notifications',
                        builder: (_, __) => const NotificationsScreen()),
                  ])
            ]),
          ],
        ),
        GoRoute(
            path: '/articles/:uuid',
            builder: (_, state) =>
                ArticleDetailScreen(uuid: state.pathParameters['uuid']!)),
        GoRoute(
            path: '/events/:uuid',
            builder: (_, state) =>
                EventDetailScreen(uuid: state.pathParameters['uuid']!)),
        GoRoute(
            path: '/notifications/:uuid',
            builder: (_, __) => const NotificationsScreen()),
        GoRoute(
            path: '/members/:uuid',
            builder: (_, state) =>
                MemberDetailScreen(uuid: state.pathParameters['uuid']!)),
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

class _MoreScreen extends StatelessWidget {
  const _MoreScreen();
  @override
  Widget build(BuildContext context) =>
      ListView(padding: const EdgeInsets.all(16), children: [
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.people_outline),
                title: const Text('Anggota keluarga'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/members'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.settings),
                title: const Text('Pengaturan keluarga'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/family/manage'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.person),
                title: const Text('Akun'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/account/profile'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.notifications),
                title: const Text('Notifikasi'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/account/notifications'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.article_outlined),
                title: const Text('Artikel'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/articles'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.photo_library_outlined),
                title: const Text('Foto & album'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/photos'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.event_outlined),
                title: const Text('Acara'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/events'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.search),
                title: const Text('Pencarian'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/search'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.insights_outlined),
                title: const Text('Laporan'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/reports'))),
        Card(
            child: ListTile(
                minTileHeight: 56,
                leading: const Icon(Icons.emoji_events_outlined),
                title: const Text('Kontribusi & peringkat'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/gamification'))),
      ]);
}
