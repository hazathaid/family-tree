import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers.dart';
import '../../l10n/app_localizations.dart';

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});
  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsState();
}

class _NotificationsState extends ConsumerState<NotificationsScreen> {
  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Column(children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: Row(children: [
            Expanded(
                child: Text(l10n.notificationsTitle,
                    style:
                        const TextStyle(fontSize: 20, fontWeight: FontWeight.bold))),
            TextButton(
                onPressed: () async {
                  await ref.read(notificationRepositoryProvider).markAllRead();
                  ref.invalidate(notificationsProvider);
                },
                child: Text(l10n.markAllRead)),
          ]),
        ),
        Expanded(
          child: ref.watch(notificationsProvider).when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (_, __) => Center(
                    child: FilledButton(
                        onPressed: () => ref.invalidate(notificationsProvider),
                        child: Text(l10n.retry))),
                data: (items) => RefreshIndicator(
                  onRefresh: () => ref.refresh(notificationsProvider.future),
                  child: items.isEmpty
                      ? ListView(children: [
                          const SizedBox(height: 160),
                          const Icon(Icons.notifications_none, size: 64),
                          Center(child: Text(l10n.noNotifications))
                        ])
                      : ListView.builder(
                          itemCount: items.length,
                          itemBuilder: (context, index) {
                            final item = items[index];
                            return ListTile(
                              minTileHeight: 64,
                              leading: Icon(
                                  item.isRead
                                      ? Icons.notifications_none
                                      : Icons.notifications_active,
                                  color: item.isRead
                                      ? null
                                      : Theme.of(context).colorScheme.primary),
                              title: Text(item.title,
                                  style: TextStyle(
                                      fontWeight: item.isRead
                                          ? null
                                          : FontWeight.bold)),
                              subtitle: Text(item.body),
                              onTap: () async {
                                if (!item.isRead) {
                                  await ref
                                      .read(notificationRepositoryProvider)
                                      .markRead(item.uuid);
                                  ref.invalidate(notificationsProvider);
                                }
                                if (context.mounted &&
                                    item.targetPath != null) {
                                  context.push(item.targetPath!);
                                }
                              },
                            );
                          },
                        ),
                ),
              ),
        ),
      ]);
  }
}
