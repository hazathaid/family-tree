class FamilyLinkParty {
  const FamilyLinkParty({required this.uuid, required this.name});

  final String uuid;
  final String name;

  factory FamilyLinkParty.fromJson(Map<String, dynamic> json) =>
      FamilyLinkParty(
          uuid: json['uuid'] as String, name: json['name'] as String);
}

class FamilyLinkMember {
  const FamilyLinkMember({required this.uuid, required this.fullName});

  final String uuid;
  final String fullName;

  factory FamilyLinkMember.fromJson(Map<String, dynamic> json) =>
      FamilyLinkMember(
          uuid: json['uuid'] as String,
          fullName: json['full_name'] as String? ?? '');
}

class FamilyLink {
  const FamilyLink({
    required this.uuid,
    required this.status,
    required this.family,
    required this.linkedFamily,
    required this.member,
    required this.linkedMember,
    this.linkType = 'marriage',
    this.canApprove = false,
    this.canRevoke = false,
    this.expiresAt,
    this.revokedAt,
  });

  final String uuid;
  final String status;
  final String linkType;
  final FamilyLinkParty family;
  final FamilyLinkParty linkedFamily;
  final FamilyLinkMember member;
  final FamilyLinkMember linkedMember;
  final bool canApprove;
  final bool canRevoke;
  final DateTime? expiresAt;
  final DateTime? revokedAt;

  bool get isPending => status == 'pending';
  bool get isActive => status == 'active';
  bool get isRevoked => status == 'revoked';
  bool get isExpired => status == 'expired';

  bool involvesFamily(String familyUuid) =>
      family.uuid == familyUuid || linkedFamily.uuid == familyUuid;

  FamilyLinkParty remoteFamily(String familyUuid) =>
      family.uuid == familyUuid ? linkedFamily : family;

  FamilyLinkMember localMember(String familyUuid) =>
      family.uuid == familyUuid ? member : linkedMember;

  FamilyLinkMember remoteMember(String familyUuid) =>
      family.uuid == familyUuid ? linkedMember : member;

  FamilyLink copyWith({String? status, bool? canApprove, bool? canRevoke}) =>
      FamilyLink(
        uuid: uuid,
        status: status ?? this.status,
        linkType: linkType,
        family: family,
        linkedFamily: linkedFamily,
        member: member,
        linkedMember: linkedMember,
        canApprove: canApprove ?? this.canApprove,
        canRevoke: canRevoke ?? this.canRevoke,
        expiresAt: expiresAt,
        revokedAt: revokedAt,
      );

  factory FamilyLink.fromJson(Map<String, dynamic> json) => FamilyLink(
        uuid: json['uuid'] as String,
        status: json['status'] as String? ?? 'pending',
        linkType: json['link_type'] as String? ?? 'marriage',
        family: FamilyLinkParty.fromJson(
            json['family'] as Map<String, dynamic>? ?? const {}),
        linkedFamily: FamilyLinkParty.fromJson(
            json['linked_family'] as Map<String, dynamic>? ?? const {}),
        member: FamilyLinkMember.fromJson(
            json['member'] as Map<String, dynamic>? ?? const {}),
        linkedMember: FamilyLinkMember.fromJson(
            json['linked_member'] as Map<String, dynamic>? ?? const {}),
        canApprove: json['can_approve'] as bool? ?? false,
        canRevoke: json['can_revoke'] as bool? ?? false,
        expiresAt: DateTime.tryParse(json['expires_at'] as String? ?? ''),
        revokedAt: DateTime.tryParse(json['revoked_at'] as String? ?? ''),
      );
}

class FamilyLinkCandidate {
  const FamilyLinkCandidate({
    required this.uuid,
    required this.fullName,
    required this.family,
    this.nickname,
    this.gender,
    this.isAlive = true,
  });

  final String uuid;
  final String fullName;
  final String? nickname;
  final String? gender;
  final bool isAlive;
  final FamilyLinkParty family;

  factory FamilyLinkCandidate.fromJson(Map<String, dynamic> json) =>
      FamilyLinkCandidate(
        uuid: json['uuid'] as String,
        fullName: json['full_name'] as String? ?? '',
        nickname: json['nickname'] as String?,
        gender: json['gender'] as String?,
        isAlive: json['is_alive'] as bool? ?? true,
        family: FamilyLinkParty.fromJson(
            json['family'] as Map<String, dynamic>? ?? const {}),
      );
}
