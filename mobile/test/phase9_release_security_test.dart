import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  test('production platform manifests enforce secure network defaults', () {
    final android = File('android/app/src/main/AndroidManifest.xml').readAsStringSync();
    final network = File('android/app/src/main/res/xml/network_security_config.xml')
        .readAsStringSync();
    final ios = File('ios/Runner/Info.plist').readAsStringSync();

    expect(android, contains('android:usesCleartextTraffic="false"'));
    expect(android, contains('android.permission.POST_NOTIFICATIONS'));
    expect(network, contains('cleartextTrafficPermitted="false"'));
    expect(ios, contains('NSCameraUsageDescription'));
    expect(ios, isNot(contains('NSAllowsArbitraryLoads')));
  });

  test('iOS release declares privacy, push, and universal links', () {
    final privacy = File('ios/Runner/PrivacyInfo.xcprivacy').readAsStringSync();
    final entitlements = File('ios/Runner/Runner.entitlements').readAsStringSync();
    final release = File('ios/Flutter/Release.xcconfig').readAsStringSync();

    expect(privacy, contains('<key>NSPrivacyTracking</key>'));
    expect(privacy, contains('<false/>'));
    expect(entitlements, contains('applinks:familytree.id'));
    expect(entitlements, contains('aps-environment'));
    expect(release, contains('APS_ENVIRONMENT=production'));
  });

  test('tracked mobile sources contain no common credential markers', () {
    final files = Directory('.')
        .listSync(recursive: true)
        .whereType<File>()
        .where((file) => !_isThirdPartyOrArtifact(file.path) &&
            !file.path.endsWith('phase9_release_security_test.dart') &&
            RegExp(r'\.(dart|xml|plist|xcconfig|kts|gradle|properties|yaml|md)$')
                .hasMatch(file.path));
    final forbidden = RegExp(
      r'(BEGIN (RSA |EC )?PRIVATE KEY|AIza[0-9A-Za-z_-]{30,}|sk_live_[0-9A-Za-z]+)',
    );
    for (final file in files) {
      expect(file.readAsStringSync(), isNot(matches(forbidden)),
          reason: 'Credential-like value found in ${file.path}');
    }
  });
}

bool _isThirdPartyOrArtifact(String path) {
  final segments = path.replaceAll('\\', '/').split('/');
  return segments.any((segment) =>
      segment == 'build' ||
      segment == '.dart_tool' ||
      segment == '.symlinks' ||
      segment == 'Pods');
}
