import 'dart:io';

import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/features/account/data/api_account_repository.dart';
import 'package:family_tree_mobile/features/auth/data/api_auth_repository.dart';
import 'package:family_tree_mobile/features/content/data/api_content_repository.dart';
import 'package:family_tree_mobile/features/discovery/data/api_discovery_repository.dart';
import 'package:family_tree_mobile/features/family/data/api_family_link_repository.dart';
import 'package:family_tree_mobile/features/family/data/api_family_repository.dart';
import 'package:family_tree_mobile/features/members/data/api_member_repository.dart';
import 'package:family_tree_mobile/features/notifications/data/api_notification_repository.dart';
import 'package:family_tree_mobile/features/tree/data/api_tree_repository.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';

Map<String, dynamic> userJson() => {
      'uuid': 'user-uuid',
      'name': 'Budi',
      'email': 'budi@example.com',
      'phone': '0812',
      'email_verified_at': '2024-01-01T00:00:00Z',
    };

Map<String, dynamic> familyJson() => {
      'uuid': 'family-uuid',
      'name': 'Keluarga',
      'description': 'Deskripsi',
      'origin_city': 'Bandung',
      'current_user_role': 'owner',
    };

Map<String, dynamic> memberJson() => {
      'uuid': 'member-uuid',
      'family_uuid': 'family-uuid',
      'full_name': 'Budi',
      'is_alive': true,
      'gender': 'male',
    };

Map<String, dynamic> relationshipJson() => {
      'uuid': 'relationship-uuid',
      'source_member_uuid': 'member-uuid',
      'source_member_name': 'Budi',
      'target_member_uuid': 'child-uuid',
      'target_member_name': 'Siti',
      'relationship_type': 'father',
    };

Map<String, dynamic> spouseJson() => {
      'relationship_uuid': 'relationship-uuid',
      'spouse_uuid': 'spouse-uuid',
      'spouse_name': 'Dewi',
      'label': 'Mantan Istri',
      'gender': 'female',
      'start_date': '2010-01-01',
      'end_date': '2024-06-01',
      'end_reason': 'divorced',
      'is_ended': true,
      'can_end': false,
      'can_correct': true,
      'can_reopen': true,
    };

Map<String, dynamic> treeJson() => {
      'root_member_uuid': 'member-uuid',
      'mode': 'full',
      'depth': 3,
      'layout': 'vertical',
      'nodes': [
        {
          'uuid': 'member-uuid',
          'name': 'Budi',
          'is_root': true,
          'position': {'x': 10, 'y': 20}
        }
      ],
      'edges': [],
      'viewport': {'width': 900, 'height': 700},
      'expansion': {'can_expand': true, 'can_collapse': false},
    };

Map<String, dynamic> articleJson() => {
      'uuid': 'article-uuid',
      'family_uuid': 'family-uuid',
      'title': 'Sejarah',
      'content': '<p>Isi</p>',
      'status': 'published',
      'category': {'uuid': 'category-uuid', 'name': 'Sejarah'},
      'author': {'uuid': 'user-uuid', 'name': 'Budi'},
    };

Map<String, dynamic> commentJson() => {
      'uuid': 'comment-uuid',
      'user': {'uuid': 'user-uuid', 'name': 'Budi'},
      'comment': 'Komentar',
    };

Map<String, dynamic> albumJson() => {
      'uuid': 'album-uuid',
      'family_uuid': 'family-uuid',
      'name': 'Album',
      'photos_count': 2,
    };

Map<String, dynamic> photoJson() => {
      'uuid': 'photo-uuid',
      'family_uuid': 'family-uuid',
      'url': 'https://example.test/photo.jpg',
      'thumbnail_url': 'https://example.test/thumb.jpg',
      'size': 10,
      'tagged_members': [
        {'uuid': 'member-uuid', 'full_name': 'Budi'}
      ],
    };

Map<String, dynamic> eventJson() => {
      'uuid': 'event-uuid',
      'family_uuid': 'family-uuid',
      'title': 'Reuni',
      'event_date': '2026-05-01T10:00:00Z',
      'organizer': {'uuid': 'user-uuid', 'name': 'Budi'},
      'attendees': [
        {
          'uuid': 'attendee-uuid',
          'user': {'uuid': 'user-uuid', 'name': 'Budi'},
          'status': 'yes'
        }
      ],
    };

Future<String> tempFile(String name) async {
  final directory = await Directory.systemTemp.createTemp('family-tree-test');
  final file = File('${directory.path}/$name');
  await file.writeAsBytes([1, 2, 3, 4]);
  return file.path;
}

void main() {
  group('ApiAuthRepository', () {
    test('covers registration, login, two factor and account actions',
        () async {
      final api = FakeApiClient((method, path, {query, data}) {
        switch (path) {
          case '/auth/register':
            return userJson();
          case '/auth/login':
            return {
              'token': 'token',
              'refresh_token': 'refresh',
              'user': userJson()
            };
          case '/auth/two-factor-challenge':
            return {
              'token': 'token-2fa',
              'refresh_token': 'refresh-2fa',
              'user': userJson()
            };
          case '/auth/me':
            return userJson();
          default:
            return {'ok': true};
        }
      });
      final repo = ApiAuthRepository(api);

      final user = await repo.register('Budi', 'budi@example.com', 'secret',
          phone: '0812');
      expect(user.uuid, 'user-uuid');

      final login = await repo.login('budi@example.com', 'secret');
      expect(login.twoFactorRequired, isFalse);
      expect(login.user?.name, 'Budi');
      expect(api.tokens['token'], 'token');
      expect(api.tokens['refresh'], 'refresh');

      final challenged =
          await repo.twoFactorChallenge('challenge', code: '123456');
      expect(challenged.uuid, 'user-uuid');
      final recovery =
          await repo.twoFactorChallenge('challenge', recoveryCode: 'recovery');
      expect(recovery.uuid, 'user-uuid');

      expect((await repo.me()).email, 'budi@example.com');
      await repo.forgotPassword('budi@example.com');
      await repo.resetPassword('token', 'budi@example.com', 'secret');
      await repo.resendVerification();
      await repo.verifyEmail('1', 'hash', {'expires': '1'});
      await repo.logout();
      expect(api.tokens, isEmpty);
    });

    test('returns a challenge token when login requires two factor', () async {
      final api = FakeApiClient((method, path, {query, data}) =>
          {'two_factor_required': true, 'challenge_token': 'challenge-token'});
      final result = await ApiAuthRepository(api).login('a@b.c', 'secret');
      expect(result.twoFactorRequired, isTrue);
      expect(result.challengeToken, 'challenge-token');
      expect(result.user, isNull);
    });
  });

  group('ApiFamilyRepository', () {
    test('maps families, branches, memberships, dashboard and timeline',
        () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path == '/families' && method == 'GET') {
          return {
            'data': [familyJson()]
          };
        }
        if (path == '/families' && method == 'POST') {
          return familyJson();
        }
        if (path == '/families/family-uuid' || path.endsWith('/assets')) {
          return familyJson();
        }
        if (path.endsWith('/branches') && method == 'GET') {
          return {
            'data': [
              {'uuid': 'branch-uuid', 'name': 'Cabang'}
            ]
          };
        }
        if (path.endsWith('/branches')) {
          return {'uuid': 'branch-uuid', 'name': 'Cabang'};
        }
        if (path.contains('/branches/')) {
          return {'uuid': 'branch-uuid', 'name': 'Cabang'};
        }
        if (path.endsWith('/roles') && method == 'GET') {
          return [
            {'uuid': 'membership-uuid', 'role': 'owner', 'user': userJson()}
          ];
        }
        if (path.endsWith('/roles/invite')) {
          return {
            'uuid': 'membership-uuid',
            'role': 'member',
            'user': userJson()
          };
        }
        if (path.contains('/roles/')) {
          return {
            'uuid': 'membership-uuid',
            'role': 'admin',
            'user': userJson()
          };
        }
        if (path.endsWith('/dashboard')) {
          return {'total_members': 3, 'living_members': 2};
        }
        if (path == '/timeline') {
          return {
            'data': [
              {'uuid': 'timeline-uuid', 'message': 'Aktivitas'}
            ]
          };
        }
        if (path == '/tree/generate') return treeJson();
        return {'ok': true};
      });
      final repo = ApiFamilyRepository(api);

      expect((await repo.all()).single.name, 'Keluarga');
      expect(
          (await repo.create('Baru', description: 'D', originCity: 'B')).uuid,
          'family-uuid');
      expect((await repo.update(testFamily, name: 'Ubah')).name, 'Keluarga');
      expect((await repo.branches('family-uuid')).single.name, 'Cabang');
      expect((await repo.createBranch('family-uuid', 'Cabang', null)).name,
          'Cabang');
      expect(
          (await repo.updateBranch('family-uuid', sampleBranch, 'Ubah', null))
              .name,
          'Cabang');
      await repo.deleteBranch('family-uuid', 'branch-uuid');
      expect((await repo.memberships('family-uuid')).single.role, 'owner');
      expect(
          (await repo.invite('family-uuid', 'a@b.c', 'member')).role, 'member');
      expect(
          (await repo.assignRole('family-uuid', 'membership-uuid', 'admin'))
              .role,
          'admin');
      await repo.removeMembership('family-uuid', 'membership-uuid');
      expect((await repo.dashboard('family-uuid')).totalMembers, 3);
      expect((await repo.timeline('family-uuid')).single.message, 'Aktivitas');
      expect((await repo.tree('member-uuid')).nodes.single.name, 'Budi');

      final assetPath = await tempFile('logo.png');
      await repo.uploadAssets('family-uuid',
          logoPath: assetPath, coverPath: assetPath);
      expect(api.calls.last.data, isNotNull);
    });

    test('accepts a plain list response for families', () async {
      final api =
          FakeApiClient((method, path, {query, data}) => [familyJson()]);
      expect((await ApiFamilyRepository(api).all()).single.uuid, 'family-uuid');
    });
  });

  group('ApiMemberRepository', () {
    test('maps member CRUD, relationships, documents and duplicates', () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path == '/family-members' && method == 'GET') {
          return {
            'data': [memberJson()],
            'current_page': 2,
            'last_page': 4,
            'total': 30,
            'page': 2
          };
        }
        if (path == '/family-members' && method == 'POST') {
          return memberJson();
        }
        if (path.endsWith('/photo')) return memberJson();
        if (path.endsWith('/merge')) return memberJson();
        if (path.endsWith('/documents') && method == 'GET') {
          return [
            {'uuid': 'document-uuid', 'title': 'Akta'}
          ];
        }
        if (path.endsWith('/documents') && method == 'POST') {
          return {'uuid': 'document-uuid', 'title': 'Akta'};
        }
        if (path == '/relationships' && method == 'GET') {
          return {
            'data': [relationshipJson()],
            'current_page': 1,
            'last_page': 1
          };
        }
        if (path == '/relationships') return relationshipJson();
        if (path.startsWith('/relationships/') && method == 'PUT') {
          return relationshipJson();
        }
        if (path.endsWith('/spouses') && method == 'GET') {
          return [spouseJson()];
        }
        if (path.endsWith('/end') && method == 'POST') {
          return relationshipJson();
        }
        if (path.startsWith('/family-members/member-uuid/spouses/') &&
            method == 'PATCH') {
          return relationshipJson();
        }
        if (path.endsWith('/reopen') && method == 'POST') {
          return relationshipJson();
        }
        if (path == '/relationship-engine') {
          return {
            'relationship': 'Ayah',
            'path': [
              {
                'relationship': 'father',
                'from_member_name': 'Siti',
                'to_member_name': 'Budi'
              }
            ]
          };
        }
        if (path.endsWith('/members/duplicates')) {
          return [
            {
              'primary': memberJson(),
              'duplicate': memberJson(),
              'confidence': 'high',
              'reasons': ['same_name']
            }
          ];
        }
        if (path.contains('/family-members/')) return memberJson();
        return {'ok': true};
      });
      final repo = ApiMemberRepository(api);

      final page = await repo.members('family-uuid',
          page: 2,
          limit: 10,
          search: 'Budi',
          gender: 'male',
          isAlive: true,
          branchUuid: 'branch-uuid',
          sort: 'newest');
      expect(page.items.single.fullName, 'Budi');
      expect(page.currentPage, 2);
      expect(page.hasMore, isTrue);
      expect(api.calls.first.query?['search'], 'Budi');
      expect(api.calls.first.query?['is_alive'], isTrue);

      expect((await repo.member('member-uuid')).uuid, 'member-uuid');
      expect((await repo.create('family-uuid', {'full_name': 'Baru'})).uuid,
          'member-uuid');
      expect((await repo.update('member-uuid', {'full_name': 'Ubah'})).uuid,
          'member-uuid');

      final path = await tempFile('photo.png');
      expect((await repo.uploadPhoto('member-uuid', path)).uuid, 'member-uuid');
      await repo.deleteMember('member-uuid');

      final relationships =
          await repo.relationships('family-uuid', memberUuid: 'member-uuid');
      expect(relationships.items.single.sourceName, 'Budi');
      expect(api.calls.last.query?['member_uuid'], 'member-uuid');
      expect(
          (await repo.createRelationship({'relationship_type': 'father'})).type,
          'father');
      expect((await repo.updateRelationship('relationship-uuid', {})).uuid,
          'relationship-uuid');
      await repo.deleteRelationship('relationship-uuid');

      final spouses = await repo.spouses('member-uuid');
      expect(spouses.single.label, 'Mantan Istri');
      expect(spouses.single.canReopen, isTrue);
      expect(
          (await repo.endMarriage('member-uuid', 'relationship-uuid',
                  endDate: '2024-06-01', endReason: 'divorced'))
              .uuid,
          'relationship-uuid');
      expect(
          (await repo.correctMarriage('member-uuid', 'relationship-uuid',
                  endDate: '2023-12-31', endReason: 'widowed'))
              .uuid,
          'relationship-uuid');
      expect(
          (await repo.reopenMarriage('member-uuid', 'relationship-uuid')).uuid,
          'relationship-uuid');

      final resolved = await repo.resolve('member-uuid', 'child-uuid');
      expect(resolved.relationship, 'Ayah');
      expect(resolved.path.single.toName, 'Budi');

      expect((await repo.documents('member-uuid')).single.title, 'Akta');
      expect(
          (await repo.uploadDocument('member-uuid',
                  path: path,
                  title: 'Akta',
                  category: 'legal',
                  documentDate: '2020-01-01',
                  notes: 'Catatan'))
              .uuid,
          'document-uuid');
      await repo.deleteDocument('document-uuid');
      expect((await repo.duplicateCandidates('family-uuid')).single.confidence,
          'high');
      expect((await repo.mergeMember('member-uuid', 'duplicate-uuid')).uuid,
          'member-uuid');
    });
  });

  group('ApiAccountRepository', () {
    test('maps profile, preferences, sessions and two factor flows', () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path == '/profile') return userJson();
        if (path == '/profile/avatar') return userJson();
        if (path == '/profile/notification-preferences') {
          return {
            'email': method == 'GET' ? false : true,
            'push': method == 'GET',
            'event_reminders': method == 'GET' ? false : true,
            'family_updates': true
          };
        }
        if (path == '/profile/sessions') {
          return {
            'data': [
              {
                'uuid': 'session-uuid',
                'device_name': 'Pixel',
                'is_current': true
              }
            ]
          };
        }
        if (path.startsWith('/profile/sessions/')) {
          return {'revoked_current': true};
        }
        if (path == '/profile/two-factor' && method == 'GET') {
          return {'enabled': true, 'pending': false, 'recovery_codes_count': 2};
        }
        if (path == '/profile/two-factor' && method == 'POST') {
          return {'secret': 'SECRET', 'otpauth_url': 'otpauth://totp/test'};
        }
        if (path == '/profile/two-factor/confirm') {
          return {
            'recovery_codes': ['A', 'B']
          };
        }
        if (path == '/profile/two-factor/recovery-codes') {
          return {
            'recovery_codes': ['C']
          };
        }
        return {'ok': true};
      });
      final repo = ApiAccountRepository(api);
      expect((await repo.profile()).uuid, 'user-uuid');
      expect(
          (await repo.updateProfile('Budi', 'b@c.d', '0812',
                  currentPassword: 'secret'))
              .name,
          'Budi');
      final path = await tempFile('avatar.png');
      expect((await repo.uploadAvatar(path)).uuid, 'user-uuid');
      await repo.changePassword('old', 'new');
      expect((await repo.preferences()).email, isFalse);
      expect(
          (await repo.updatePreferences(const NotificationPreferences(
                  email: true,
                  push: false,
                  eventReminders: true,
                  familyUpdates: false)))
              .push,
          isFalse);
      expect((await repo.sessions()).single.deviceName, 'Pixel');
      expect(await repo.revokeSession('session-uuid'), isTrue);
      expect((await repo.twoFactorStatus()).enabled, isTrue);
      expect((await repo.enableTwoFactor('secret')).secret, 'SECRET');
      expect(await repo.confirmTwoFactor('123456'), ['A', 'B']);
      await repo.disableTwoFactor('secret');
      expect(await repo.regenerateRecoveryCodes('secret'), ['C']);
    });

    test('accepts a plain list of sessions', () async {
      final api = FakeApiClient((method, path, {query, data}) => [
            {'uuid': 'session-uuid', 'device_name': 'Pixel'}
          ]);
      expect((await ApiAccountRepository(api).sessions()).single.uuid,
          'session-uuid');
    });
  });

  group('ApiContentRepository', () {
    test('maps articles, comments, albums, photos and events', () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path == '/articles' && method == 'GET') {
          return {
            'data': [articleJson()],
            'current_page': 1,
            'last_page': 1
          };
        }
        if (path == '/articles' && method == 'POST') return articleJson();
        if (path == '/articles/article-uuid') {
          return articleJson();
        }
        if (path == '/articles/article-uuid/publish') return articleJson();
        if (path.contains('/articles/article-uuid/feature')) {
          return articleJson();
        }
        if (path.endsWith('/featured-image')) return articleJson();
        if (path.contains('/articles/article-uuid/comments') &&
            method == 'GET') {
          return {
            'data': [commentJson()],
            'current_page': 1,
            'last_page': 1
          };
        }
        if (path.contains('/articles/article-uuid/comments')) {
          return commentJson();
        }
        if (path.endsWith('/articles/featured')) {
          return {
            'data': [articleJson()]
          };
        }
        if (path == '/article-categories') {
          return {
            'data': [
              {'uuid': 'category-uuid', 'name': 'Sejarah'}
            ]
          };
        }
        if (path == '/photo-albums' && method == 'GET') {
          return {
            'data': [albumJson()],
            'current_page': 1,
            'last_page': 1
          };
        }
        if (path == '/photo-albums') return albumJson();
        if (path.contains('/photo-albums/')) return albumJson();
        if (path == '/member-photos' && method == 'GET') {
          return {
            'data': [photoJson()],
            'current_page': 1,
            'last_page': 1
          };
        }
        if (path == '/member-photos') return photoJson();
        if (path.endsWith('/tags')) return photoJson();
        if (path.contains('/member-photos/')) return photoJson();
        if (path == '/events' && method == 'GET') {
          return {
            'data': [eventJson()],
            'current_page': 1,
            'last_page': 1
          };
        }
        if (path == '/events') return eventJson();
        if (path.contains('/events/') && path.endsWith('/rsvp')) {
          return {'ok': true};
        }
        if (path.contains('/events/')) return eventJson();
        return {'ok': true};
      });
      final repo = ApiContentRepository(api);

      expect(
          (await repo.articles('family-uuid',
                  search: 'sejarah', categoryUuid: 'c', status: 'published'))
              .items
              .single
              .title,
          'Sejarah');
      expect(api.calls.first.query?['search'], 'sejarah');
      expect((await repo.featuredArticles('family-uuid')).single.uuid,
          'article-uuid');
      expect((await repo.categories()).single.name, 'Sejarah');
      expect((await repo.article('article-uuid')).uuid, 'article-uuid');
      expect((await repo.saveArticle('family-uuid', {'title': 'Baru'})).uuid,
          'article-uuid');
      expect(
          (await repo.saveArticle('family-uuid', {'title': 'Ubah'},
                  uuid: 'article-uuid'))
              .uuid,
          'article-uuid');
      await repo.deleteArticle('article-uuid');
      expect((await repo.publishArticle('article-uuid')).uuid, 'article-uuid');
      expect((await repo.featureArticle('article-uuid', true)).uuid,
          'article-uuid');
      await repo.featureArticle('article-uuid', false);
      final path = await tempFile('image.png');
      await repo.uploadArticleImage('article-uuid', path);
      expect((await repo.comments('article-uuid')).items.single.comment,
          'Komentar');
      expect((await repo.saveComment('article-uuid', 'Baru')).uuid,
          'comment-uuid');
      await repo.saveComment('article-uuid', 'Ubah', uuid: 'comment-uuid');
      await repo.deleteComment('article-uuid', 'comment-uuid');
      await repo.likeArticle('article-uuid', true);
      await repo.likeArticle('article-uuid', false);
      expect((await repo.albums('family-uuid')).items.single.name, 'Album');
      expect((await repo.saveAlbum('family-uuid', 'Album', 'D')).uuid,
          'album-uuid');
      await repo.saveAlbum('family-uuid', 'Ubah', null, uuid: 'album-uuid');
      await repo.deleteAlbum('album-uuid');
      expect(
          (await repo.photos('family-uuid', albumUuid: 'album-uuid'))
              .items
              .single
              .uuid,
          'photo-uuid');
      expect((await repo.photo('photo-uuid')).uuid, 'photo-uuid');
      await repo.uploadPhoto('family-uuid', path,
          albumUuid: 'album-uuid',
          caption: 'Foto',
          capturedAt: DateTime(2026),
          onProgress: (_, __) {});
      expect((await repo.tagPhoto('photo-uuid', ['member-uuid'])).uuid,
          'photo-uuid');
      await repo.deletePhoto('photo-uuid');
      expect(
          (await repo.events('family-uuid', search: 'reuni', upcoming: true))
              .items
              .single
              .title,
          'Reuni');
      expect((await repo.event('event-uuid')).uuid, 'event-uuid');
      expect((await repo.saveEvent('family-uuid', {'title': 'Baru'})).uuid,
          'event-uuid');
      await repo.saveEvent('family-uuid', {'title': 'Ubah'},
          uuid: 'event-uuid');
      await repo.deleteEvent('event-uuid');
      await repo.rsvp('event-uuid', 'yes');
    });

    test('accepts plain lists from list endpoints', () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path == '/articles') return [articleJson()];
        return {
          'data': [articleJson()]
        };
      });
      final repo = ApiContentRepository(api);
      expect((await repo.articles('f')).items.single.uuid, 'article-uuid');
      expect((await repo.articles('f')).currentPage, 1);
    });
  });

  group('ApiDiscoveryRepository', () {
    test('maps search, reports and leaderboards', () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path == '/search') {
          return {
            'members': [memberJson()],
            'articles': [articleJson()],
            'events': [eventJson()],
            'pagination': {'page': 2, 'has_more': true}
          };
        }
        if (path.endsWith('/reports/family-statistics')) {
          return {
            'total_members': 10,
            'alive_members': 8,
            'deceased_members': 2,
            'total_generations': 3,
            'members_by_generation': {'1': 4, '2': 6}
          };
        }
        if (path.endsWith('/reports/activity')) {
          return {
            'active_users': 4,
            'uploads': {'total': 7},
            'articles': {'total': 3}
          };
        }
        if (path.endsWith('/reports/insights')) {
          return {
            'cities': [
              {'label': 'Bandung', 'total': 5}
            ],
            'growth': [
              {'label': '2026', 'total': 8}
            ],
            'activity': [
              {'label': 'Jan', 'total': 2}
            ]
          };
        }
        if (path.endsWith('/gamification')) {
          return {
            'points': 120,
            'badges': [
              {'uuid': 'badge', 'name': 'Kontributor', 'description': 'Aktif'}
            ]
          };
        }
        return [
          {'rank': 1, 'uuid': 'user-uuid', 'name': 'Budi', 'points': 120}
        ];
      });
      final repo = ApiDiscoveryRepository(api);
      final search = await repo.search('family-uuid',
          keyword: 'budi',
          name: 'Budi',
          city: 'Bandung',
          status: 'alive',
          generation: 2,
          rootMemberUuid: 'root',
          page: 2);
      expect(search.members.single.fullName, 'Budi');
      expect(search.articles.single.title, 'Sejarah');
      expect(search.events.single.title, 'Reuni');
      expect(search.page, 2);
      expect(search.hasMore, isTrue);
      expect(api.calls.first.query?['generation'], 2);
      expect(api.calls.first.query?['root_member_uuid'], 'root');

      final statistics = await repo.statistics('family-uuid');
      expect(statistics.totalMembers, 10);
      expect(statistics.generations.length, 2);
      final activity = await repo.activity(
          'family-uuid', DateTime(2026, 1, 2), DateTime(2026, 2, 3));
      expect(activity.activeUsers, 4);
      expect(activity.uploads, 7);
      expect(activity.articles, 3);
      expect(api.calls.last.query?['to'], '2026-02-03');
      final insights = await repo.insights(
          'family-uuid', DateTime(2026, 1, 1), DateTime(2026, 1, 31));
      expect(insights.cities.single.label, 'Bandung');
      expect((await repo.profile('family-uuid')).points, 120);
      expect((await repo.userLeaderboard('family-uuid')).single.rank, 1);
      expect((await repo.familyLeaderboard()).single.points, 120);
    });
  });

  group('ApiNotificationRepository', () {
    test('maps pagination, marking and device registration', () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path == '/notifications') {
          return {
            'data': [
              {'uuid': 'n', 'title': 'Judul', 'body': 'Pesan', 'is_read': false}
            ],
            'current_page': 1,
            'last_page': 2
          };
        }
        return {'ok': true};
      });
      final repo = ApiNotificationRepository(api);
      final page = await repo.all(page: 2, status: 'unread');
      expect(page.items.single.title, 'Judul');
      expect(page.hasMore, isTrue);
      expect(api.calls.first.query?['status'], 'unread');
      await repo.markRead('n');
      await repo.markAllRead();
      await repo.registerDevice(platform: 'android', token: 'token');
      await repo.removeDevice('device');
    });

    test('accepts a plain list of notifications', () async {
      final api = FakeApiClient((method, path, {query, data}) => [
            {'uuid': 'n', 'title': 'Judul', 'body': 'Pesan'}
          ]);
      final page = await ApiNotificationRepository(api).all();
      expect(page.items.single.uuid, 'n');
      expect(page.total, 1);
    });
  });

  group('ApiTreeRepository', () {
    test('maps generate, relative, export and async jobs', () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path == '/tree/generate') return treeJson();
        if (path.endsWith('/relatives')) return memberJson();
        if (path == '/tree/exports') {
          return {'uuid': 'export-uuid', 'status': 'pending', 'format': 'pdf'};
        }
        if (path.startsWith('/tree/exports/')) {
          return {
            'uuid': 'export-uuid',
            'status': 'completed',
            'format': 'pdf'
          };
        }
        return {'ok': true};
      });
      final repo = ApiTreeRepository(api);
      expect(
          (await repo.generate('member-uuid',
                  mode: 'full', depth: 3, layout: 'vertical'))
              .nodes
              .single
              .name,
          'Budi');
      expect(api.calls.first.query?['member_uuid'], 'member-uuid');
      expect(
          (await repo.createRelative('member-uuid', {'relation': 'child'}))
              .uuid,
          'member-uuid');
      final bytes = await repo.export('png', 'member-uuid',
          mode: 'full',
          depth: 3,
          layout: 'vertical',
          paperSize: 'A4',
          onProgress: (_, __) {});
      expect(bytes, isNotEmpty);
      final job = await repo.requestExport('member-uuid',
          format: 'pdf',
          mode: 'full',
          depth: 3,
          layout: 'vertical',
          paperSize: 'A4');
      expect(job.uuid, 'export-uuid');
      expect((await repo.exportStatus('export-uuid')).isCompleted, isTrue);
      expect(await repo.downloadExport('export-uuid'), isNotEmpty);
    });
  });

  group('ApiFamilyLinkRepository', () {
    test('maps links, candidates, decisions and partner trees', () async {
      final api = FakeApiClient((method, path, {query, data}) {
        if (path.endsWith('/family-links') && method == 'GET') {
          return [
            {
              'uuid': 'link-uuid',
              'status': 'pending',
              'family': {'uuid': 'family-uuid', 'name': 'A'},
              'linked_family': {'uuid': 'family-b', 'name': 'B'},
              'member': {'uuid': 'member-uuid', 'full_name': 'Budi'},
              'linked_member': {'uuid': 'member-b', 'full_name': 'Siti'}
            }
          ];
        }
        if (path.endsWith('/candidates')) {
          return [
            {
              'uuid': 'member-b',
              'full_name': 'Siti',
              'family': {'uuid': 'family-b', 'name': 'B'}
            }
          ];
        }
        if (path.endsWith('/partner-tree')) return treeJson();
        if (path.endsWith('/family-links') && method == 'POST') {
          return {
            'uuid': 'link-uuid',
            'status': 'pending',
            'family': {'uuid': 'family-uuid', 'name': 'A'},
            'linked_family': {'uuid': 'family-b', 'name': 'B'},
            'member': {'uuid': 'member-uuid', 'full_name': 'Budi'},
            'linked_member': {'uuid': 'member-b', 'full_name': 'Siti'}
          };
        }
        return {
          'uuid': 'link-uuid',
          'status': 'active',
          'family': {'uuid': 'family-uuid', 'name': 'A'},
          'linked_family': {'uuid': 'family-b', 'name': 'B'},
          'member': {'uuid': 'member-uuid', 'full_name': 'Budi'},
          'linked_member': {'uuid': 'member-b', 'full_name': 'Siti'}
        };
      });
      final repo = ApiFamilyLinkRepository(api);
      expect((await repo.links('family-uuid')).single.uuid, 'link-uuid');
      expect(
          (await repo.candidates('family-uuid', search: 'siti'))
              .single
              .fullName,
          'Siti');
      expect(
          (await repo.request('family-uuid',
                  memberUuid: 'member-uuid', linkedMemberUuid: 'member-b'))
              .uuid,
          'link-uuid');
      expect((await repo.approve('link-uuid')).status, 'active');
      expect((await repo.revoke('link-uuid')).status, 'active');
      expect(
          (await repo.partnerTree('link-uuid', familyUuid: 'family-uuid'))
              .nodes
              .single
              .name,
          'Budi');
    });
  });
}
