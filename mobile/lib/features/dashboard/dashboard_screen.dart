import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:go_router/go_router.dart';

import '../../core/models.dart';
import '../../core/providers.dart';
import '../../core/widgets/async_states.dart';
import '../../l10n/app_localizations.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final family = ref.watch(currentFamilyProvider);
    final summary = ref.watch(dashboardProvider);
    final l10n = AppLocalizations.of(context);
    return RefreshIndicator(
      onRefresh: () async => ref.invalidate(dashboardProvider),
      child: CustomScrollView(slivers: [
        SliverPadding(
            padding: const EdgeInsets.all(16),
            sliver: SliverList.list(children: [
              Semantics(
                  header: true,
                  child: Text(
                      l10n.dashboardWelcome(family?.name ?? l10n.dashboardYourFamily),
                      style: Theme.of(context).textTheme.headlineSmall)),
              const SizedBox(height: 16),
              summary.when(
                loading: () => const AppSkeleton(lines: 8),
                error: (error, _) => AppErrorState(
                    message: l10n.dashboardLoadFailed,
                    onRetry: () => ref.invalidate(dashboardProvider)),
                data: (data) => _Dashboard(data: data),
              ),
            ])),
      ]),
    );
  }
}

class _Dashboard extends StatelessWidget {
  const _Dashboard({required this.data});
  final DashboardSummary data;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        LayoutBuilder(builder: (context, constraints) {
          final width = constraints.maxWidth >= 600
              ? (constraints.maxWidth - 24) / 3
              : (constraints.maxWidth - 12) / 2;
          return Wrap(spacing: 12, runSpacing: 12, children: [
            _Stat(l10n.dashboardTotalMembers, data.totalMembers, Icons.groups,
                width, '/members'),
            _Stat(l10n.dashboardLivingMembers, data.livingMembers,
                Icons.favorite, width, '/members'),
            _Stat(l10n.deceased, data.deceasedMembers, Icons.history, width,
                '/members'),
            _Stat(l10n.dashboardArticles, data.totalArticles, Icons.article,
                width, '/articles'),
            _Stat(l10n.dashboardPhotos, data.totalPhotos, Icons.photo_library,
                width, '/photos'),
            _Stat(l10n.dashboardEvents, data.totalEvents, Icons.event, width,
                '/events'),
          ]);
        }),
        _Section(
            title: l10n.dashboardRecentActivity,
            items: data.activity,
            icon: Icons.history),
        _Section(
            title: l10n.dashboardUpcomingBirthdays,
            items: data.birthdays,
            icon: Icons.cake),
        _Section(
            title: l10n.dashboardUpcomingEvents,
            items: data.events,
            icon: Icons.event,
            routePrefix: '/events'),
        _Section(
            title: l10n.dashboardNotifications(data.unreadNotifications),
            items: data.notifications,
            icon: Icons.notifications,
            onAll: () => context.go('/account/notifications')),
        const SizedBox(height: 20),
        Text(l10n.dashboardFamilyFacts,
            style: Theme.of(context).textTheme.titleLarge),
        Card(
            child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(l10n.dashboardOriginCity(data.originCity ?? '-')),
                      Text(l10n.dashboardOldestMember(
                          data.oldestMember?.title ?? '-')),
                      Text(l10n.dashboardYoungestMember(
                          data.youngestMember?.title ?? '-')),
                    ]))),
        _Section(
            title: l10n.dashboardRecentMembers,
            items: data.recentMembers,
            icon: Icons.person,
            routePrefix: '/members'),
      ]);
  }
}

class _Stat extends StatelessWidget {
  const _Stat(this.label, this.value, this.icon, this.width, this.route);
  final String label;
  final int value;
  final IconData icon;
  final double width;
  final String route;
  @override
  Widget build(BuildContext context) => SizedBox(
      width: width,
      child: Card(
          child: InkWell(
              onTap: () => context.go(route),
              child: ConstrainedBox(
                  constraints: const BoxConstraints(minHeight: 112),
                  child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(children: [
                        Icon(icon),
                        Text('$value',
                            style: Theme.of(context).textTheme.headlineMedium),
                        Text(label, textAlign: TextAlign.center)
                      ]))))));
}

class _Section extends StatelessWidget {
  const _Section(
      {required this.title,
      required this.items,
      required this.icon,
      this.routePrefix,
      this.onAll});
  final String title;
  final List<DashboardEntry> items;
  final IconData icon;
  final String? routePrefix;
  final VoidCallback? onAll;
  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Padding(
        padding: const EdgeInsets.only(top: 20),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(
                child:
                    Text(title, style: Theme.of(context).textTheme.titleLarge)),
            if (onAll != null)
              TextButton(onPressed: onAll, child: Text(l10n.dashboardSeeAll))
          ]),
          if (items.isEmpty)
            Card(child: ListTile(title: Text(l10n.noData)))
          else
            Card(
                child: Column(
                    children: items
                        .map((item) => ListTile(
                            minTileHeight: 56,
                            leading: Icon(icon),
                            title: Text(item.title),
                            subtitle: item.subtitle == null
                                ? null
                                : Text(item.subtitle!),
                            onTap: routePrefix == null
                                ? null
                                : () =>
                                    context.go('$routePrefix/${item.uuid}')))
                        .toList())),
        ]));
  }
}
