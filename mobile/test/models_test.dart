import 'package:family_tree_mobile/core/models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('dashboard response is parsed with safe defaults', () {
    final summary =
        DashboardSummary.fromJson({'total_members': 100, 'living_members': 80});
    expect(summary.totalMembers, 100);
    expect(summary.livingMembers, 80);
    expect(summary.deceasedMembers, 0);
  });

  test('tree response maps nodes and edges', () {
    final tree = FamilyTree.fromJson({
      'nodes': [
        {
          'uuid': 'root',
          'name': 'Ahmad',
          'position': {'x': 10, 'y': 20}
        }
      ],
      'edges': [
        {'source_uuid': 'root', 'target_uuid': 'child'}
      ],
    });
    expect(tree.nodes.single.name, 'Ahmad');
    expect(tree.edges.single.targetUuid, 'child');
  });

  test('user exposes verification and safe account fields', () {
    final user = User.fromJson({
      'uuid': 'user-uuid',
      'name': 'Siti',
      'email': 'siti@example.com',
      'phone': '0812',
      'email_verified_at': '2026-07-22T00:00:00Z',
      'status': 'active',
    });
    expect(user.isVerified, isTrue);
    expect(user.phone, '0812');
  });

  test('notification preferences round-trip API fields', () {
    final value = NotificationPreferences.fromJson({
      'email': false,
      'push': true,
      'event_reminders': false,
      'family_updates': true,
    });
    expect(value.toJson()['event_reminders'], isFalse);
    expect(value.push, isTrue);
  });

  test('account session parser does not require sensitive details', () {
    final session = AccountSession.fromJson({
      'uuid': 'session-uuid',
      'device_name': 'Pixel',
      'is_current': true,
      'last_active_at': '2026-07-22T00:00:00Z',
    });
    expect(session.deviceName, 'Pixel');
    expect(session.isCurrent, isTrue);
  });
}
