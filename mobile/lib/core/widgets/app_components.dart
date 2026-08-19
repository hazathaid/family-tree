import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';

class AppStatusBadge extends StatelessWidget {
  const AppStatusBadge({required this.label, this.color, super.key});
  final String label;
  final Color? color;
  @override
  Widget build(BuildContext context) => Semantics(
      label: AppLocalizations.of(context).statusLabel(label),
      child: Chip(
          label: Text(label),
          backgroundColor: (color ?? Theme.of(context).colorScheme.primary)
              .withValues(alpha: .12),
          side: BorderSide.none));
}

Future<bool> showAppConfirmation(BuildContext context,
        {required String title,
        required String message,
        bool destructive = false}) async =>
    await showDialog<bool>(
        context: context,
        builder: (context) {
          final l10n = AppLocalizations.of(context);
          return AlertDialog(title: Text(title), content: Text(message), actions: [
              TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: Text(l10n.cancel)),
              FilledButton(
                  onPressed: () => Navigator.pop(context, true),
                  style: destructive
                      ? FilledButton.styleFrom(
                          backgroundColor: Theme.of(context).colorScheme.error)
                      : null,
                  child: Text(l10n.continueAction))
            ]);
        }) ??
    false;

void showAppSnackBar(BuildContext context, String message) =>
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
