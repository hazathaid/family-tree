// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'Family Tree Indonesia';

  @override
  String get loading => 'Loading…';

  @override
  String get retry => 'Try again';

  @override
  String get cancel => 'Cancel';

  @override
  String get continueAction => 'Continue';

  @override
  String get loadingData => 'Loading';

  @override
  String get loadFailed => 'Unable to load data';

  @override
  String get genericError => 'Something went wrong. Please try again.';

  @override
  String showingStoredData(String time) {
    return 'Showing saved data from $time.';
  }

  @override
  String statusLabel(String label) {
    return 'Status: $label';
  }

  @override
  String get email => 'Email';

  @override
  String get password => 'Password';

  @override
  String get showPassword => 'Show password';

  @override
  String get hidePassword => 'Hide password';

  @override
  String get loginTitle => 'Sign in';

  @override
  String get loginButton => 'Sign in';

  @override
  String get forgotPassword => 'Forgot password?';

  @override
  String get createAccount => 'Create new account';

  @override
  String get registerTitle => 'Register';

  @override
  String get registerButton => 'Register';

  @override
  String get nameLabel => 'Name';

  @override
  String get phoneOptional => 'Phone number (optional)';

  @override
  String get haveAccount => 'Already have an account? Sign in';

  @override
  String get accountCreated =>
      'Account created. Please sign in and verify your email.';

  @override
  String get forgotPasswordTitle => 'Forgot password';

  @override
  String get sendResetLink => 'Send reset link';

  @override
  String get sending => 'Sending…';

  @override
  String get backToLogin => 'Back to sign in';

  @override
  String get resetEmailSent =>
      'If the email is registered, a reset link has been sent.';

  @override
  String get resetPasswordTitle => 'Reset password';

  @override
  String get newPassword => 'New password';

  @override
  String get savePassword => 'Save password';

  @override
  String get passwordChanged => 'Password changed successfully.';

  @override
  String get verifyEmailTitle => 'Verify email';

  @override
  String get verifyEmailPrompt =>
      'Check your email and open the verification link.';

  @override
  String get resendEmail => 'Resend email';

  @override
  String resendIn(int seconds) {
    return 'Resend in ${seconds}s';
  }

  @override
  String get verificationSent => 'Verification link sent.';

  @override
  String get logout => 'Sign out';

  @override
  String get membersTitle => 'Family members';
  @override
  String get memberFilters => 'Filter members';
  @override
  String get gender => 'Gender';
  @override
  String get status => 'Status';
  @override
  String get branch => 'Branch';
  @override
  String get sort => 'Sort';
  @override
  String get all => 'All';
  @override
  String get male => 'Male';
  @override
  String get female => 'Female';
  @override
  String get unspecified => 'Unspecified';
  @override
  String get alive => 'Alive';
  @override
  String get deceased => 'Deceased';
  @override
  String get sortNameAscending => 'Name A-Z';
  @override
  String get sortNameDescending => 'Name Z-A';
  @override
  String get sortNewest => 'Newest';
  @override
  String get sortOldest => 'Oldest';
  @override
  String get apply => 'Apply';
  @override
  String get manageRelationships => 'Manage relationships';
  @override
  String get relationshipResolver => 'Relationship resolver';
  @override
  String get addMember => 'Add member';
  @override
  String get searchMemberNameOrNickname => 'Search name or nickname';
  @override
  String get filterAndSort => 'Filter and sort';
  @override
  String get noMatchingMembers => 'No matching members yet.';
  @override
  String get memberName => 'Name';
  @override
  String get previousPage => 'Previous page';
  @override
  String get nextPage => 'Next page';
  @override
  String pageOf(int current, int total) => 'Page $current of $total';
  @override
  String get memberDetailLoadFailed => 'Unable to load member details.';
  @override
  String get editMember => 'Edit member';
  @override
  String relationshipToYou(String relationship) => '$relationship to you';
  @override
  String get inMemory => 'In memory';
  @override
  String get basicInformation => 'Basic information';
  @override
  String get fullName => 'Full name';
  @override
  String get nickname => 'Nickname';
  @override
  String get religionBelief => 'Religion/belief';
  @override
  String get born => 'Born';
  @override
  String get died => 'Died';
  @override
  String get noBranch => 'No branch';
  @override
  String get biography => 'Biography';
  @override
  String get noBiography => 'No biography yet.';
  @override
  String get basicRelationships => 'Basic relationships';
  @override
  String get noBasicRelationships => 'No basic relationships yet.';
  @override
  String get relatedContent => 'Related content';
  @override
  String get relatedPhotos => 'Related photos';
  @override
  String get relatedArticles => 'Related articles';
  @override
  String get noRelatedContent => 'No related content to display yet.';
  @override
  String get memberSaved => 'Member saved successfully.';
  @override
  String get photoUpdated => 'Photo updated successfully.';
  @override
  String deleteMemberTitle(String name) => 'Delete $name?';
  @override
  String get deleteMemberConfirmation => 'The member will be soft-deleted and no longer displayed.';
  @override
  String get delete => 'Delete';
  @override
  String get birthDate => 'Birth date (YYYY-MM-DD)';
  @override
  String get birthPlace => 'Birth place';
  @override
  String get stillAlive => 'Still alive';
  @override
  String get deathDate => 'Death date (YYYY-MM-DD)';
  @override
  String get deathPlace => 'Death place';
  @override
  String get saving => 'Saving...';
  @override
  String get save => 'Save';
  @override
  String get replacePhoto => 'Replace photo';
  @override
  String get deleteMember => 'Delete member';
  @override
  String get addRelationship => 'Add relationship';
  @override
  String get editRelationship => 'Edit relationship';
  @override
  String get relationshipType => 'Basic type';
  @override
  String get sourceMember => 'Source member';
  @override
  String get targetMember => 'Target member';
  @override
  String get source => 'Source';
  @override
  String get target => 'Target';
  @override
  String get selectMember => 'Select member';
  @override
  String get findRelationship => 'Find relationship';
  @override
  String get calculating => 'Calculating...';
  @override
  String get notConnected => 'Not connected';
  @override
  String get sameMember => 'Same member.';
  @override
  String get relationshipPathNotFound => 'No relationship path found.';
  @override
  String get selectMemberTitle => 'Select member';
  @override
  String get searchMembers => 'Search members';
  @override
  String pageFraction(int current, int total) => '$current / $total';
  @override
  String get noValue => '-';
  @override
  String get noBranchShort => 'No branch';
  @override
  String memberSemantics(String name, String status) => '$name, $status';
  @override
  String get islam => 'Islam';
  @override
  String get christian => 'Christian';
  @override
  String get catholic => 'Catholic';
  @override
  String get hindu => 'Hindu';
  @override
  String get buddhist => 'Buddhist';
  @override
  String get confucian => 'Confucian';
  @override
  String get belief => 'Belief follower';
  @override
  String get other => 'Other';
  @override
  String get father => 'Father';
  @override
  String get mother => 'Mother';
  @override
  String get child => 'Child';
  @override
  String get husband => 'Husband';
  @override
  String get wife => 'Wife';
  @override
  String get family => 'Family';
  @override
  String get fullNameRequired => 'Full name is required.';
}
