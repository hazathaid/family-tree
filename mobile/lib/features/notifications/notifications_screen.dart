import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ref.watch(notificationsProvider).when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text('$error')),
        data: (items) => RefreshIndicator(
          onRefresh: () => ref.refresh(notificationsProvider.future),
          child: items.isEmpty
              ? ListView(children: const [SizedBox(height: 160), Icon(Icons.notifications_none, size: 64),
                  Center(child: Text('Belum ada notifikasi.'))])
              : ListView.builder(
                  itemCount: items.length,
                  itemBuilder: (context, index) {
                    final item = items[index];
                    return ListTile(
                      leading: Icon(item.isRead ? Icons.notifications_none : Icons.notifications_active,
                          color: item.isRead ? null : Theme.of(context).colorScheme.primary),
                      title: Text(item.title, style: TextStyle(fontWeight: item.isRead ? null : FontWeight.bold)),
                      subtitle: Text(item.body),
                      onTap: item.isRead ? null : () async {
                        await ref.read(notificationRepositoryProvider).markRead(item.uuid);
                        ref.invalidate(notificationsProvider);
                      },
                    );
                  },
                ),
        ),
      );
}
