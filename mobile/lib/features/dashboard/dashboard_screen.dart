import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:go_router/go_router.dart';

import '../../core/models.dart';
import '../../core/providers.dart';
import '../../core/widgets/async_states.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final family = ref.watch(currentFamilyProvider);
    final summary = ref.watch(dashboardProvider);
    return RefreshIndicator(
      onRefresh: () async => ref.invalidate(dashboardProvider),
      child: CustomScrollView(slivers: [
        SliverPadding(
            padding: const EdgeInsets.all(16),
            sliver: SliverList.list(children: [
              Semantics(
                  header: true,
                  child: Text(
                      'Selamat datang di ${family?.name ?? 'keluarga Anda'}',
                      style: Theme.of(context).textTheme.headlineSmall)),
              const SizedBox(height: 16),
              summary.when(
                loading: () => const AppSkeleton(lines: 8),
                error: (error, _) => AppErrorState(
                    message: 'Dashboard tidak dapat dimuat.',
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
  Widget build(BuildContext context) =>
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        LayoutBuilder(builder: (context, constraints) {
          final width = constraints.maxWidth >= 600
              ? (constraints.maxWidth - 24) / 3
              : (constraints.maxWidth - 12) / 2;
          return Wrap(spacing: 12, runSpacing: 12, children: [
            _Stat('Total anggota', data.totalMembers, Icons.groups, width,
                '/members'),
            _Stat('Anggota hidup', data.livingMembers, Icons.favorite, width,
                '/members'),
            _Stat('Meninggal', data.deceasedMembers, Icons.history, width,
                '/members'),
            _Stat('Artikel', data.totalArticles, Icons.article, width,
                '/articles'),
            _Stat('Foto', data.totalPhotos, Icons.photo_library, width,
                '/photos'),
            _Stat('Acara', data.totalEvents, Icons.event, width, '/events'),
          ]);
        }),
        _Section(
            title: 'Aktivitas terbaru',
            items: data.activity,
            icon: Icons.history),
        _Section(
            title: 'Ulang tahun mendatang',
            items: data.birthdays,
            icon: Icons.cake),
        _Section(
            title: 'Acara mendatang',
            items: data.events,
            icon: Icons.event,
            routePrefix: '/events'),
        _Section(
            title: 'Notifikasi (${data.unreadNotifications} belum dibaca)',
            items: data.notifications,
            icon: Icons.notifications,
            onAll: () => context.go('/account/notifications')),
        const SizedBox(height: 20),
        Text('Fakta keluarga', style: Theme.of(context).textTheme.titleLarge),
        Card(
            child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Kota asal: ${data.originCity ?? '-'}'),
                      Text(
                          'Anggota tertua: ${data.oldestMember?.title ?? '-'}'),
                      Text(
                          'Anggota termuda: ${data.youngestMember?.title ?? '-'}'),
                    ]))),
        _Section(
            title: 'Anggota terbaru',
            items: data.recentMembers,
            icon: Icons.person,
            routePrefix: '/members'),
      ]);
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
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.only(top: 20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(
              child:
                  Text(title, style: Theme.of(context).textTheme.titleLarge)),
          if (onAll != null)
            TextButton(onPressed: onAll, child: const Text('Lihat semua'))
        ]),
        if (items.isEmpty)
          const Card(child: ListTile(title: Text('Belum ada data.')))
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
                              : () => context.go('$routePrefix/${item.uuid}')))
                      .toList())),
      ]));
}
