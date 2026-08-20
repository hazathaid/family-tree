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
  String pageOf(int current, int total) {
    return 'Page $current of $total';
  }

  @override
  String get memberDetailLoadFailed => 'Unable to load member details.';

  @override
  String get editMember => 'Edit member';

  @override
  String relationshipToYou(String relationship) {
    return '$relationship to you';
  }

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
  String deleteMemberTitle(String name) {
    return 'Delete $name?';
  }

  @override
  String get deleteMemberConfirmation =>
      'The member will be soft-deleted and no longer displayed.';

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
  String pageFraction(int current, int total) {
    return '$current / $total';
  }

  @override
  String get noValue => '-';

  @override
  String get noBranchShort => 'No branch';

  @override
  String memberSemantics(String name, String status) {
    return '$name, $status';
  }

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

  @override
  String dashboardWelcome(String family) {
    return 'Welcome to $family';
  }

  @override
  String get dashboardYourFamily => 'your family';

  @override
  String get dashboardLoadFailed => 'Unable to load dashboard.';

  @override
  String get dashboardTotalMembers => 'Total members';

  @override
  String get dashboardLivingMembers => 'Living members';

  @override
  String get dashboardArticles => 'Articles';

  @override
  String get dashboardPhotos => 'Photos';

  @override
  String get dashboardEvents => 'Events';

  @override
  String get dashboardRecentActivity => 'Recent activity';

  @override
  String get dashboardUpcomingBirthdays => 'Upcoming birthdays';

  @override
  String get dashboardUpcomingEvents => 'Upcoming events';

  @override
  String dashboardNotifications(int count) {
    return 'Notifications ($count unread)';
  }

  @override
  String get dashboardFamilyFacts => 'Family facts';

  @override
  String dashboardOriginCity(String city) {
    return 'Origin city: $city';
  }

  @override
  String dashboardOldestMember(String name) {
    return 'Oldest member: $name';
  }

  @override
  String dashboardYoungestMember(String name) {
    return 'Youngest member: $name';
  }

  @override
  String get dashboardRecentMembers => 'Recent members';

  @override
  String get dashboardSeeAll => 'See all';

  @override
  String get noData => 'No data yet.';

  @override
  String get familyOnboardingTitle => 'Create family';

  @override
  String get familyOnboardingHeadline => 'Start documenting your family';

  @override
  String get familyOnboardingBody =>
      'You will become the family owner and can invite members later.';

  @override
  String get familyName => 'Family name';

  @override
  String get originCityOptional => 'Origin city (optional)';

  @override
  String get descriptionOptional => 'Description (optional)';

  @override
  String get creating => 'Creating…';

  @override
  String get selectFamilyTitle => 'Select family';

  @override
  String get familiesLoadFailed => 'Unable to load families.';

  @override
  String get createFirstFamily => 'Create your first family';

  @override
  String get selectFamilyFirst => 'Select a family first.';

  @override
  String get manageFamilyTitle => 'Manage family';

  @override
  String get tabProfile => 'Profile';

  @override
  String get tabBranches => 'Branches';

  @override
  String get tabAccess => 'Access';

  @override
  String get familySettingsSaved => 'Family settings saved.';

  @override
  String get logoUpdated => 'Logo updated.';

  @override
  String get coverUpdated => 'Cover updated.';

  @override
  String get familyPrivacyTitle => 'Privacy: family members only';

  @override
  String get familyPrivacyBody =>
      'Family privacy follows membership and cannot be changed. Notification preferences are managed per account.';

  @override
  String get originCity => 'Origin city';

  @override
  String get description => 'Description';

  @override
  String get replaceLogo => 'Replace logo';

  @override
  String get replaceCover => 'Replace cover';

  @override
  String get addBranch => 'Add branch';

  @override
  String get editBranch => 'Edit branch';

  @override
  String deleteBranchTitle(String name) {
    return 'Delete $name?';
  }

  @override
  String get deleteBranchConfirmation =>
      'The branch will be deleted. Related members are kept according to server rules.';

  @override
  String get noBranches => 'No branches yet.';

  @override
  String get editLabel => 'Edit';

  @override
  String get inviteMember => 'Invite member';

  @override
  String get roleMember => 'Member';

  @override
  String get roleAdmin => 'Admin';

  @override
  String get roleOwner => 'Owner';

  @override
  String get invite => 'Invite';

  @override
  String deleteAccessTitle(String name) {
    return 'Remove access for $name?';
  }

  @override
  String get deleteOwnerConfirmation => 'The last owner cannot be removed.';

  @override
  String get ownerOnlyAccess => 'Only the owner can manage family access.';

  @override
  String get deleteAccess => 'Remove access';

  @override
  String get requestFailed => 'Request failed. Please try again.';

  @override
  String get familyArticles => 'Family articles';

  @override
  String get writeArticle => 'Write article';

  @override
  String get searchArticles => 'Search articles';

  @override
  String get published => 'Published';

  @override
  String get draft => 'Draft';

  @override
  String get featuredArticles => 'Featured articles';

  @override
  String get noArticles => 'No articles to display yet.';

  @override
  String get writeComment => 'Write comment';

  @override
  String get editComment => 'Edit comment';

  @override
  String get articleDetail => 'Article details';

  @override
  String get publish => 'Publish';

  @override
  String get featureArticle => 'Feature article';

  @override
  String get unfeatureArticle => 'Remove from featured';

  @override
  String featuredImageLabel(String title) {
    return 'Featured image $title';
  }

  @override
  String get like => 'Like';

  @override
  String get unlike => 'Unlike';

  @override
  String likesCount(int count) {
    return '$count likes';
  }

  @override
  String get commentsLabel => 'Comments';

  @override
  String get editArticle => 'Edit article';

  @override
  String get titleLabel => 'Title';

  @override
  String get category => 'Category';

  @override
  String get excerpt => 'Excerpt';

  @override
  String get contentLabel => 'Content (safe text and paragraphs)';

  @override
  String get featuredImageSizeLimit => 'Featured image must be at most 10 MB.';

  @override
  String get chooseFeaturedImage => 'Choose featured image';

  @override
  String get saveDraft => 'Save draft';

  @override
  String get albumGallery => 'Albums & gallery';

  @override
  String get createAlbum => 'Create album';

  @override
  String get editAlbum => 'Edit album';

  @override
  String get uploadPhoto => 'Upload photo';

  @override
  String get album => 'Album';

  @override
  String get allPhotos => 'All photos';

  @override
  String get deleteAlbum => 'Delete album';

  @override
  String deleteAlbumTitle(String name) {
    return 'Delete album $name?';
  }

  @override
  String get deleteAlbumConfirmation =>
      'The album will be deleted. Photos in it are kept without an album.';

  @override
  String get noPhotos => 'No photos yet.';

  @override
  String get familyPhoto => 'Family photo';

  @override
  String get photoValidation =>
      'Photo must be JPG, PNG, or WebP and at most 10 MB.';

  @override
  String get gallery => 'Gallery';

  @override
  String get camera => 'Camera';

  @override
  String get photoPreview => 'Photo preview';

  @override
  String get caption => 'Caption';

  @override
  String get noAlbum => 'No album';

  @override
  String get capturedDate => 'Capture date';

  @override
  String get notSpecified => 'Not specified';

  @override
  String get upload => 'Upload';

  @override
  String get photoDetail => 'Photo details';

  @override
  String get tagMembers => 'Tag members';

  @override
  String get deletePhoto => 'Delete photo';

  @override
  String photoAlbumLabel(String name) {
    return 'Album: $name';
  }

  @override
  String takenAt(String date) {
    return 'Taken: $date';
  }

  @override
  String get familyEvents => 'Family events';

  @override
  String get createEvent => 'Create event';

  @override
  String get searchEvents => 'Search events';

  @override
  String get upcoming => 'Upcoming';

  @override
  String get noEvents => 'No events yet.';

  @override
  String deviceTime(String zone) {
    return 'Device time: $zone';
  }

  @override
  String get eventDetail => 'Event details';

  @override
  String get editEvent => 'Edit event';

  @override
  String get deleteEvent => 'Delete event';

  @override
  String get confirmAttendance => 'Confirm attendance';

  @override
  String get yes => 'Yes';

  @override
  String get maybe => 'Maybe';

  @override
  String get no => 'No';

  @override
  String attendeesCount(int count) {
    return 'Attendees ($count)';
  }

  @override
  String get location => 'Location';

  @override
  String get dateTimeLabel => 'Date & time';

  @override
  String get pickTime => 'Pick time';

  @override
  String get familyTimeline => 'Family timeline';

  @override
  String get noActivity => 'No activity yet.';

  @override
  String get familySearch => 'Family search';

  @override
  String get keyword => 'Keyword';

  @override
  String get advancedFilters => 'Advanced filters';

  @override
  String get birthDeathCity => 'Birth/death city';

  @override
  String get livingStatus => 'Living status';

  @override
  String get relativeGeneration => 'Relative generation';

  @override
  String get rootMemberUuid => 'Root member UUID';

  @override
  String get generationFilterHelper =>
      'Required when a generation filter is used';

  @override
  String get searchAction => 'Search';

  @override
  String get noSearchResults => 'No matching results.';

  @override
  String get membersGroup => 'Members';

  @override
  String generationLabel(int generation) {
    return 'Generation $generation';
  }

  @override
  String get articlesGroup => 'Articles';

  @override
  String get eventsGroup => 'Events';

  @override
  String get loadMore => 'Load more';

  @override
  String get reportsTitle => 'Reports & insights';

  @override
  String reportFromDate(String date) {
    return 'From $date';
  }

  @override
  String reportToDate(String date, String zone) {
    return 'To $date $zone';
  }

  @override
  String get reportsActiveUsers => 'Active users';

  @override
  String get reportsUploads => 'Photos';

  @override
  String get reportsArticles => 'Articles';

  @override
  String get generationDistribution => 'Generation distribution';

  @override
  String get reportCities => 'Cities';

  @override
  String get memberGrowth => 'Member growth';

  @override
  String get activityTrend => 'Activity trend';

  @override
  String get contributionRanking => 'Contribution & rankings';

  @override
  String contributionPointsLabel(int points) {
    return '$points contribution points';
  }

  @override
  String pointsLabel(int points) {
    return '$points points';
  }

  @override
  String get yourContribution => 'Your contribution';

  @override
  String get myBadges => 'My badges';

  @override
  String get noBadges => 'No badges yet.';

  @override
  String get familyUserRanking => 'Family user rankings';

  @override
  String get familyRanking => 'Family rankings';

  @override
  String groupSemantics(String title, int count) {
    return '$title, $count results';
  }

  @override
  String dataSemantics(String title) {
    return 'Data $title';
  }

  @override
  String reportRowSemantics(String label, int total) {
    return '$label: $total';
  }

  @override
  String get noRankings => 'No rankings yet.';

  @override
  String get notificationsTitle => 'Notifications';

  @override
  String get markAllRead => 'Mark all as read';

  @override
  String get noNotifications => 'No notifications yet.';

  @override
  String get exportShareFailed => 'Export could not be opened or shared.';

  @override
  String exportReady(String format) {
    return 'Export $format ready';
  }

  @override
  String get previewUnavailable => 'Preview unavailable.';

  @override
  String pdfExportReady(int size) {
    return 'PDF created successfully ($size KB). Share to open or save it.';
  }

  @override
  String get close => 'Close';

  @override
  String get share => 'Share';

  @override
  String get relationshipUnavailable => 'Relationship unavailable';

  @override
  String get openMemberDetail => 'Open member details';

  @override
  String get addParent => 'Add parent';

  @override
  String get addSpouse => 'Add spouse';

  @override
  String get addChild => 'Add child';

  @override
  String get relativeLabelParent => 'parent';

  @override
  String get relativeLabelSpouse => 'spouse';

  @override
  String get relativeLabelChild => 'child';

  @override
  String addRelative(String relation) {
    return 'Add $relation';
  }

  @override
  String forMember(String name) {
    return 'For $name';
  }

  @override
  String get nameRequired => 'Name is required.';

  @override
  String get addAction => 'Add';

  @override
  String relativeAdded(String name) {
    return '$name added successfully.';
  }

  @override
  String get relativeAddFailed => 'Member could not be added.';

  @override
  String get preparingExport => 'Preparing export…';

  @override
  String get cancelExport => 'Cancel export';

  @override
  String get pickTreeRoot => 'Pick tree root';

  @override
  String get chooseCenter => 'Choose center';

  @override
  String get exportTree => 'Export tree';

  @override
  String get exportPng => 'Export PNG';

  @override
  String get exportPdf => 'Export PDF';

  @override
  String get ancestor => 'Ancestors';

  @override
  String get descendant => 'Descendants';

  @override
  String get fullTree => 'Full';

  @override
  String get vertical => 'Vertical';

  @override
  String get horizontal => 'Horizontal';

  @override
  String get radial => 'Radial';

  @override
  String get compact => 'Compact';

  @override
  String collapseDepth(int depth) {
    return 'Collapse ($depth)';
  }

  @override
  String expandDepth(int depth) {
    return 'Expand ($depth/20)';
  }

  @override
  String get livingOnly => 'Living only';

  @override
  String get semanticList => 'Accessible list';

  @override
  String get searchFocusMember => 'Search/focus member';

  @override
  String get zoomOut => 'Zoom out';

  @override
  String get zoomIn => 'Zoom in';

  @override
  String get centerTree => 'Center';

  @override
  String get pickCenterMember => 'Pick center member';

  @override
  String get loadingTree => 'Loading family tree';

  @override
  String get treeEmpty => 'The family tree is still empty.';

  @override
  String get treeUnknownRelationship => 'Unknown';

  @override
  String get treeRelationshipUnknown => 'unknown relationship';

  @override
  String get treeAliveLower => 'alive';

  @override
  String get treeDeceasedLower => 'deceased';

  @override
  String treeNodeSemantics(String name, String relation, String status) {
    return '$name, $relation, $status';
  }

  @override
  String showingNodes(int shown, int total) {
    return 'Showing $shown of $total nodes';
  }

  @override
  String get accountTitle => 'Account';

  @override
  String get profile => 'Profile';

  @override
  String get notificationPreferences => 'Notification preferences';

  @override
  String get securitySessions => 'Security and sessions';

  @override
  String get switchFamily => 'Switch family';

  @override
  String get avatarSizeLimit => 'Avatar must be at most 5 MB.';

  @override
  String get profileUpdated => 'Profile updated.';

  @override
  String get chooseAvatar => 'Choose avatar';

  @override
  String get phone => 'Phone';

  @override
  String get currentPasswordLabel => 'Current password (if email changes)';

  @override
  String get push => 'Push';

  @override
  String get eventReminders => 'Event reminders';

  @override
  String get familyUpdates => 'Family updates';

  @override
  String get security => 'Security';

  @override
  String get currentPassword => 'Current password';

  @override
  String get changePassword => 'Change password';

  @override
  String get deviceSessions => 'Device sessions';

  @override
  String get reloadSessions => 'Reload sessions';

  @override
  String get thisDevice => 'This device';

  @override
  String lastActive(String time) {
    return 'Last active: $time';
  }

  @override
  String get revokeSession => 'Revoke session';

  @override
  String get diagnosticsTitle => 'Diagnostics';

  @override
  String get diagnosticsEnvironment => 'Environment';

  @override
  String get diagnosticsApiHost => 'API host';

  @override
  String get diagnosticsConnectivity => 'Connectivity';

  @override
  String get checking => 'Checking…';

  @override
  String get diagnosticsPrivacy =>
      'Tokens, credentials, payloads, and personal data are not displayed.';

  @override
  String get navDashboard => 'Dashboard';

  @override
  String get navActivity => 'Activity';

  @override
  String get navMore => 'More';

  @override
  String get startingApp => 'Starting app';

  @override
  String get familySettings => 'Family settings';

  @override
  String get photosAlbums => 'Photos & albums';

  @override
  String get searchMenu => 'Search';

  @override
  String get reportsMenu => 'Reports';
}
