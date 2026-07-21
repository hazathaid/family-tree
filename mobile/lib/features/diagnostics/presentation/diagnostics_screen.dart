import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';

import '../../../core/config/app_environment.dart';

class DiagnosticsScreen extends StatelessWidget {
  const DiagnosticsScreen({required this.environment, super.key});
  final AppEnvironment environment;
  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Diagnostics')),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          ListTile(
              title: const Text('Environment'),
              subtitle: Text(environment.flavor.name)),
          ListTile(
              title: const Text('API host'),
              subtitle: Text(environment.sanitizedHost)),
          FutureBuilder<List<ConnectivityResult>>(
            future: Connectivity().checkConnectivity(),
            builder: (_, snapshot) => ListTile(
                title: const Text('Connectivity'),
                subtitle: Text(
                    snapshot.data?.map((value) => value.name).join(', ') ??
                        'Memeriksa…')),
          ),
          const Text(
              'Token, credential, payload, dan data pribadi tidak ditampilkan.'),
        ]),
      );
}
