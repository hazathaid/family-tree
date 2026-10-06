import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/errors/app_error.dart';
import '../../core/providers.dart';
import '../../l10n/app_localizations.dart';

class TwoFactorChallengeScreen extends ConsumerStatefulWidget {
  const TwoFactorChallengeScreen({required this.challengeToken, super.key});
  final String challengeToken;

  @override
  ConsumerState<TwoFactorChallengeScreen> createState() =>
      _TwoFactorChallengeScreenState();
}

class _TwoFactorChallengeScreenState
    extends ConsumerState<TwoFactorChallengeScreen> {
  final code = TextEditingController();
  final recovery = TextEditingController();
  bool loading = false;
  bool useRecovery = false;
  String? error;

  @override
  void dispose() {
    code.dispose();
    recovery.dispose();
    super.dispose();
  }

  Future<void> submit() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final user = await ref.read(authRepositoryProvider).twoFactorChallenge(
            widget.challengeToken,
            code: useRecovery ? null : code.text.trim(),
            recoveryCode: useRecovery ? recovery.text.trim() : null,
          );
      ref.read(currentUserProvider.notifier).state = user;
      final families = await ref.read(familyRepositoryProvider).all();
      ref.read(sessionControllerProvider).resolveUser(
          uuid: user.uuid,
          verified: user.isVerified,
          familyCount: families.length);
      final activeUuid = ref.read(sessionControllerProvider).activeFamilyUuid;
      for (final family in families) {
        if (family.uuid == activeUuid) {
          ref.read(currentFamilyProvider.notifier).state = family;
        }
      }
    } on AppError catch (e) {
      if (mounted) {
        setState(() =>
            error = e.fieldErrors['code']?.first ??
                e.fieldErrors['recovery_code']?.first ??
                e.message);
      }
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        body: SafeArea(
            child: Center(
                child: SingleChildScrollView(
                    padding: const EdgeInsets.all(24),
                    child: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 420),
                        child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              const Icon(Icons.verified_user_outlined,
                                  size: 64, color: Color(0xff1e88e5)),
                              const SizedBox(height: 16),
                              Text(l10n.twoFactorChallengeTitle,
                                  textAlign: TextAlign.center,
                                  style: Theme.of(context)
                                      .textTheme
                                      .headlineSmall),
                              const SizedBox(height: 8),
                              Text(l10n.twoFactorChallengePrompt,
                                  textAlign: TextAlign.center),
                              const SizedBox(height: 24),
                              if (!useRecovery)
                                TextField(
                                    controller: code,
                                    keyboardType: TextInputType.number,
                                    autofillHints: const [
                                      AutofillHints.oneTimeCode
                                    ],
                                    decoration: InputDecoration(
                                        labelText: l10n.twoFactorCodeLabel,
                                        errorText: error))
                              else
                                TextField(
                                    controller: recovery,
                                    decoration: InputDecoration(
                                        labelText: l10n.twoFactorRecoveryLabel,
                                        errorText: error)),
                              TextButton(
                                  onPressed: () => setState(() {
                                        useRecovery = !useRecovery;
                                        error = null;
                                      }),
                                  child: Text(useRecovery
                                      ? l10n.twoFactorUseCode
                                      : l10n.twoFactorUseRecovery)),
                              FilledButton(
                                  onPressed: loading ? null : submit,
                                  child: Text(loading
                                      ? l10n.loading
                                      : l10n.twoFactorVerify)),
                            ]))))));
  }
}
