import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;

import '../../core/models.dart';
import '../../core/providers.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final families = ref.watch(familiesProvider);
    final selected = ref.watch(currentFamilyProvider);
    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(dashboardProvider);
        ref.invalidate(timelineProvider);
      },
      child: ListView(padding: const EdgeInsets.all(16), children: [
        families.when(
          loading: () => const LinearProgressIndicator(),
          error: (error, _) => Text('$error'),
          data: (items) {
            if (selected == null && items.isNotEmpty) {
              Future.microtask(() =>
                  ref.read(currentFamilyProvider.notifier).state = items.first);
            }
            return DropdownButtonFormField<Family>(
              initialValue: selected,
              decoration: const InputDecoration(labelText: 'Keluarga'),
              items: items
                  .map((family) =>
                      DropdownMenuItem(value: family, child: Text(family.name)))
                  .toList(),
              onChanged: (family) =>
                  ref.read(currentFamilyProvider.notifier).state = family,
            );
          },
        ),
        const SizedBox(height: 20),
        ref.watch(dashboardProvider).when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => _Empty(message: '$error'),
              data: (summary) => Wrap(spacing: 12, runSpacing: 12, children: [
                _Stat('Total Anggota', summary.totalMembers, Icons.groups),
                _Stat('Anggota Hidup', summary.livingMembers, Icons.favorite),
                _Stat('Meninggal', summary.deceasedMembers, Icons.history),
                _Stat('Artikel', summary.totalArticles, Icons.article),
                _Stat('Acara', summary.totalEvents, Icons.event),
              ]),
            ),
        const SizedBox(height: 24),
        Text('Aktivitas Keluarga',
            style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 8),
        ref.watch(timelineProvider).when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => _Empty(message: '$error'),
              data: (items) => items.isEmpty
                  ? const _Empty(message: 'Belum ada aktivitas keluarga.')
                  : Column(
                      children: items
                          .map((item) => ListTile(
                                leading: const CircleAvatar(
                                    child: Icon(Icons.history)),
                                title: Text(item.message),
                                subtitle: Text(
                                    item.createdAt?.toLocal().toString() ?? ''),
                              ))
                          .toList()),
            ),
      ]),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat(this.label, this.value, this.icon);
  final String label;
  final int value;
  final IconData icon;

  @override
  Widget build(BuildContext context) => SizedBox(
      width: 156,
      child: Card(
          child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(children: [
          Icon(icon, color: Theme.of(context).colorScheme.primary),
          Text('$value', style: Theme.of(context).textTheme.headlineMedium),
          Text(label, textAlign: TextAlign.center)
        ]),
      )));
}

class _Empty extends StatelessWidget {
  const _Empty({required this.message});
  final String message;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.all(24),
      child: Column(children: [
        const Icon(Icons.family_restroom, size: 48),
        const SizedBox(height: 8),
        Text(message)
      ]));
}
