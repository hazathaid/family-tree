import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

String _read(String path) => File(path).readAsStringSync();

void main() {
  test('Android release configuration is production-ready and unsigned-safe',
      () {
    final gradle = _read('android/app/build.gradle.kts');
    final manifest = _read('android/app/src/main/AndroidManifest.xml');
    final network =
        _read('android/app/src/main/res/xml/network_security_config.xml');

    expect(
        gradle, contains('applicationId = "id.familytree.family_tree_mobile"'));
    for (final flavor in ['development', 'staging', 'production']) {
      expect(gradle, contains('create("$flavor")'));
    }
    expect(gradle, contains('versionCode = flutter.versionCode'));
    expect(gradle, contains('versionName = flutter.versionName'));
    expect(gradle, contains('buildTypes'));
    expect(gradle, contains('release'));

    expect(manifest, contains('android:usesCleartextTraffic="false"'));
    expect(
        manifest,
        contains(
            'android:networkSecurityConfig="@xml/network_security_config"'));
    expect(manifest, contains('android.permission.CAMERA'));
    expect(manifest, contains('android.permission.POST_NOTIFICATIONS'));
    expect(manifest, contains('android:scheme="familytree"'));
    expect(manifest, contains('android:host="familytree.id"'));
    expect(manifest, contains('family_updates'));

    expect(network, contains('cleartextTrafficPermitted="false"'));
    expect(network, contains('<certificates src="system" />'));
  });

  test('iOS release configuration declares 15+ target, links, and privacy', () {
    final project = _read('ios/Runner.xcodeproj/project.pbxproj');
    final podfile = _read('ios/Podfile');
    final release = _read('ios/Flutter/Release.xcconfig');
    final staging = _read('ios/Flutter/Staging.xcconfig');
    final plist = _read('ios/Runner/Info.plist');
    final entitlements = _read('ios/Runner/Runner.entitlements');
    final privacy = _read('ios/Runner/PrivacyInfo.xcprivacy');

    expect(project, contains('IPHONEOS_DEPLOYMENT_TARGET = 15.0;'));
    expect(project, isNot(contains('IPHONEOS_DEPLOYMENT_TARGET = 13.0;')));
    expect(project,
        contains(r'PRODUCT_BUNDLE_IDENTIFIER = "$(APP_BUNDLE_IDENTIFIER)"'));
    expect(project,
        contains('CODE_SIGN_ENTITLEMENTS = Runner/Runner.entitlements'));

    expect(podfile, contains("platform :ios, '15.0'"));
    expect(
        podfile,
        contains(
            "config.build_settings['IPHONEOS_DEPLOYMENT_TARGET'] = '15.0'"));

    expect(release,
        contains('APP_BUNDLE_IDENTIFIER=id.familytree.familyTreeMobile'));
    expect(release, contains('APS_ENVIRONMENT=production'));
    expect(release, contains('APP_DISPLAY_NAME=Silsilahku'));
    expect(staging, contains('.staging'));
    expect(staging, contains('APS_ENVIRONMENT=development'));

    expect(plist, contains('<string>familytree</string>'));
    expect(plist, contains('NSCameraUsageDescription'));
    expect(plist, contains('NSPhotoLibraryUsageDescription'));
    expect(plist, isNot(contains('NSAllowsArbitraryLoads')));

    expect(entitlements, contains('applinks:familytree.id'));
    expect(entitlements, contains('aps-environment'));
    expect(privacy, contains('<key>NSPrivacyTracking</key>'));
    expect(privacy, contains('<false/>'));
  });

  test('release workflow builds unsigned bundles and enforces coverage', () {
    final workflow = _read('../.github/workflows/release-gate.yml');

    expect(workflow, contains('composer test:coverage:enforce'));
    expect(workflow, contains('scripts/check-flutter-coverage.py'));
    expect(workflow, contains('--min 80'));
    expect(workflow,
        contains('flutter build appbundle --release --flavor production'));
    expect(workflow, contains('flutter build ios --release --no-codesign'));
  });

  test('mobile version is explicit and crash reporting stays opt-in', () {
    final pubspec = _read('pubspec.yaml');
    final main = _read('lib/main.dart');
    final env = _read('lib/core/config/app_environment.dart');

    expect(pubspec, contains('version: 1.0.0+1'));
    expect(main, contains('crashReportingConsent = false'));
    expect(main, isNot(matches(RegExp(r'https://[0-9a-f]{32}@'))));
    expect(env,
        contains("flavor == AppFlavor.production && uri.scheme != 'https'"));
    expect(env, contains('Production wajib menggunakan HTTPS'));
  });
}
