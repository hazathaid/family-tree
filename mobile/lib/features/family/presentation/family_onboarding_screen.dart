import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/providers.dart';
import '../../../l10n/app_localizations.dart';

class FamilyOnboardingScreen extends ConsumerStatefulWidget {
  const FamilyOnboardingScreen({this.createOnly = false, super.key});
  final bool createOnly;
  @override
  ConsumerState<FamilyOnboardingScreen> createState() =>
      _FamilyOnboardingState();
}

class _FamilyOnboardingState extends ConsumerState<FamilyOnboardingScreen> {
  final name = TextEditingController(),
      description = TextEditingController(),
      city = TextEditingController();
  bool loading = false;
  String? error;
  Future<void> create() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final family = await ref.read(familyRepositoryProvider).create(
          name.text.trim(),
          description: description.text.trim(),
          originCity: city.text.trim());
      ref.invalidate(familiesProvider);
      ref.read(currentFamilyProvider.notifier).state = family;
      ref.read(sessionControllerProvider).familySelected(family.uuid);
    } on AppError catch (e) {
      if (mounted) {
        setState(() => error = e.fieldErrors['name']?.first ?? e.message);
      }
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.familyOnboardingTitle)),
        body: SafeArea(
            child: Center(
                child: SingleChildScrollView(
                    padding: const EdgeInsets.all(24),
                    child: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 520),
                        child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(l10n.familyOnboardingHeadline,
                                  style:
                                      Theme.of(context).textTheme.headlineSmall),
                              const SizedBox(height: 8),
                              Text(l10n.familyOnboardingBody),
                              const SizedBox(height: 24),
                              TextField(
                                  controller: name,
                                  decoration: InputDecoration(
                                      labelText: l10n.familyName,
                                      errorText: error)),
                              const SizedBox(height: 12),
                              TextField(
                                  controller: city,
                                  decoration: InputDecoration(
                                      labelText: l10n.originCityOptional)),
                              const SizedBox(height: 12),
                              TextField(
                                  controller: description,
                                  maxLines: 3,
                                  decoration: InputDecoration(
                                      labelText: l10n.descriptionOptional)),
                              const SizedBox(height: 24),
                              FilledButton(
                                  onPressed:
                                      loading || name.text.trim().isEmpty
                                          ? (loading ? null : create)
                                          : create,
                                  child: Text(loading
                                      ? l10n.creating
                                      : l10n.familyOnboardingTitle))
                            ]))))));
  }
}

class FamilySelectorScreen extends ConsumerWidget {
  const FamilySelectorScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final families = ref.watch(familiesProvider);
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.selectFamilyTitle)),
        floatingActionButton: FloatingActionButton.extended(
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(
                builder: (_) =>
                    const FamilyOnboardingScreen(createOnly: true))),
            icon: const Icon(Icons.add),
            label: Text(l10n.family)),
        body: SafeArea(
            child: families.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => Center(
                        child:
                            Column(mainAxisSize: MainAxisSize.min, children: [
                      Text(l10n.familiesLoadFailed),
                      FilledButton(
                          onPressed: () => ref.invalidate(familiesProvider),
                          child: Text(l10n.retry))
                    ])),
                data: (items) {
                  if (items.isEmpty) {
                    return Center(
                        child: FilledButton(
                            onPressed: () => Navigator.of(context).push(
                                MaterialPageRoute(
                                    builder: (_) =>
                                        const FamilyOnboardingScreen())),
                            child: Text(l10n.createFirstFamily)));
                  }
                  if (items.length == 1) {
                    Future.microtask(() => _select(ref, items.single));
                  }
                  return ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: items.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (_, index) {
                        final family = items[index];
                        return Card(
                            child: ListTile(
                                minTileHeight: 56,
                                leading: const CircleAvatar(
                                    child: Icon(Icons.family_restroom)),
                                title: Text(family.name),
                                trailing: const Icon(Icons.chevron_right),
                                onTap: () => _select(ref, family)));
                      });
                })));
  }

  Future<void> _select(WidgetRef ref, family) async {
    final previous = ref.read(currentFamilyProvider);
    final userUuid = ref.read(sessionControllerProvider).userUuid;
    if (previous != null && previous.uuid != family.uuid && userUuid != null) {
      await ref
          .read(scopedCacheProvider)
          .clearScope(userUuid: userUuid, familyUuid: previous.uuid);
    }
    ref.read(currentFamilyProvider.notifier).state = family;
    ref.read(sessionControllerProvider).familySelected(family.uuid);
    ref.invalidate(dashboardProvider);
    ref.invalidate(timelineProvider);
    ref.invalidate(treeProvider);
  }
}
