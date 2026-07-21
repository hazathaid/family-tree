import 'package:flutter/material.dart';

class AppSkeleton extends StatelessWidget {
  const AppSkeleton({this.lines = 4, super.key});
  final int lines;
  @override
  Widget build(BuildContext context) => Semantics(
        label: 'Memuat',
        liveRegion: true,
        child: Column(
            children: List.generate(
                lines,
                (index) => Container(
                    height: 56,
                    margin: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                        color: const Color(0xffe8edf2),
                        borderRadius: BorderRadius.circular(12))))),
      );
}

class AppEmptyState extends StatelessWidget {
  const AppEmptyState(
      {required this.title, required this.message, this.action, super.key});
  final String title;
  final String message;
  final Widget? action;
  @override
  Widget build(BuildContext context) => Center(
      child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.inbox_outlined, size: 48),
            const SizedBox(height: 12),
            Text(title, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center),
            if (action != null) ...[const SizedBox(height: 16), action!]
          ])));
}

class AppErrorState extends StatelessWidget {
  const AppErrorState(
      {required this.onRetry,
      this.message = 'Terjadi kendala. Silakan coba lagi.',
      super.key});
  final VoidCallback onRetry;
  final String message;
  @override
  Widget build(BuildContext context) => AppEmptyState(
      title: 'Tidak dapat memuat data',
      message: message,
      action: FilledButton.icon(
          onPressed: onRetry,
          icon: const Icon(Icons.refresh),
          label: const Text('Coba lagi')));
}

class StaleDataBanner extends StatelessWidget {
  const StaleDataBanner(
      {required this.updatedAt, required this.onRetry, super.key});
  final DateTime updatedAt;
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) =>
      MaterialBanner(
          content:
              Text('Menampilkan data tersimpan dari ${updatedAt.toLocal()}.'),
          leading: const Icon(Icons.cloud_off),
          actions: [
            TextButton(onPressed: onRetry, child: const Text('Coba lagi'))
          ]);
}
