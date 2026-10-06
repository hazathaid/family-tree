import 'package:family_tree_mobile/core/models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('two-factor models parse status, setup and login result', () {
    final status = TwoFactorStatus.fromJson({
      'enabled': true,
      'pending': false,
      'recovery_codes_count': 8
    });
    expect(status.enabled, isTrue);
    expect(status.pending, isFalse);
    expect(status.recoveryCodesCount, 8);

    final setup = TwoFactorSetup.fromJson({
      'secret': 'GEZDGNBVGY3TQOJQ',
      'otpauth_url': 'otpauth://totp/Family%20Tree:budi@example.com?secret=GEZDGNBVGY3TQOJQ'
    });
    expect(setup.secret, 'GEZDGNBVGY3TQOJQ');
    expect(setup.otpauthUrl, startsWith('otpauth://totp/'));

    const required = AuthLoginResult(challengeToken: 'challenge-token');
    expect(required.twoFactorRequired, isTrue);
    expect(required.user, isNull);
  });
}
