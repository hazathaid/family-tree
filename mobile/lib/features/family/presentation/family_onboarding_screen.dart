import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/providers.dart';

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
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: const Text('Buat keluarga')),
      body: SafeArea(
          child: Center(
              child: SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 520),
                      child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Text('Mulai dokumentasikan keluarga',
                                style:
                                    Theme.of(context).textTheme.headlineSmall),
                            const SizedBox(height: 8),
                            const Text(
                                'Anda akan menjadi pemilik keluarga dan dapat mengundang anggota nanti.'),
                            const SizedBox(height: 24),
                            TextField(
                                controller: name,
                                decoration: InputDecoration(
                                    labelText: 'Nama keluarga',
                                    errorText: error)),
                            const SizedBox(height: 12),
                            TextField(
                                controller: city,
                                decoration: const InputDecoration(
                                    labelText: 'Kota asal (opsional)')),
                            const SizedBox(height: 12),
                            TextField(
                                controller: description,
                                maxLines: 3,
                                decoration: const InputDecoration(
                                    labelText: 'Deskripsi (opsional)')),
                            const SizedBox(height: 24),
                            FilledButton(
                                onPressed: loading || name.text.trim().isEmpty
                                    ? (loading ? null : create)
                                    : create,
                                child: Text(
                                    loading ? 'Membuat…' : 'Buat keluarga'))
                          ]))))));
}

class FamilySelectorScreen extends ConsumerWidget {
  const FamilySelectorScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final families = ref.watch(familiesProvider);
    return Scaffold(
        appBar: AppBar(title: const Text('Pilih keluarga')),
        floatingActionButton: FloatingActionButton.extended(
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(
                builder: (_) =>
                    const FamilyOnboardingScreen(createOnly: true))),
            icon: const Icon(Icons.add),
            label: const Text('Keluarga')),
        body: SafeArea(
            child: families.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => Center(
                        child:
                            Column(mainAxisSize: MainAxisSize.min, children: [
                      const Text('Keluarga tidak dapat dimuat.'),
                      FilledButton(
                          onPressed: () => ref.invalidate(familiesProvider),
                          child: const Text('Coba lagi'))
                    ])),
                data: (items) {
                  if (items.isEmpty) {
                    return Center(
                        child: FilledButton(
                            onPressed: () => Navigator.of(context).push(
                                MaterialPageRoute(
                                    builder: (_) =>
                                        const FamilyOnboardingScreen())),
                            child: const Text('Buat keluarga pertama')));
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
