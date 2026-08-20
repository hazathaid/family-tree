import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';

import '../../../core/config/app_environment.dart';
import '../../../l10n/app_localizations.dart';

class DiagnosticsScreen extends StatelessWidget {
  const DiagnosticsScreen({required this.environment, super.key});
  final AppEnvironment environment;
  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
          appBar: AppBar(title: Text(l10n.diagnosticsTitle)),
          body: ListView(padding: const EdgeInsets.all(16), children: [
            ListTile(
                title: Text(l10n.diagnosticsEnvironment),
                subtitle: Text(environment.flavor.name)),
            ListTile(
                title: Text(l10n.diagnosticsApiHost),
                subtitle: Text(environment.sanitizedHost)),
            FutureBuilder<List<ConnectivityResult>>(
              future: Connectivity().checkConnectivity(),
              builder: (_, snapshot) => ListTile(
                  title: Text(l10n.diagnosticsConnectivity),
                  subtitle: Text(
                      snapshot.data?.map((value) => value.name).join(', ') ??
                          l10n.checking)),
            ),
            Text(l10n.diagnosticsPrivacy),
          ]),
        );
  }
}
