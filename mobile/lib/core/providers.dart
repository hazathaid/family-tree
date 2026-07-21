import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api_client.dart';
import 'models.dart';
import 'repositories.dart';

final apiClientProvider = Provider<ApiClient>((ref) => throw UnimplementedError());
final authRepositoryProvider = Provider((ref) => AuthRepository(ref.watch(apiClientProvider)));
final familyRepositoryProvider = Provider((ref) => FamilyRepository(ref.watch(apiClientProvider)));
final notificationRepositoryProvider =
    Provider((ref) => NotificationRepository(ref.watch(apiClientProvider)));

final currentFamilyProvider = StateProvider<Family?>((ref) => null);
final currentMemberUuidProvider = StateProvider<String?>((ref) => null);

final familiesProvider = FutureProvider<List<Family>>((ref) => ref.watch(familyRepositoryProvider).all());

final dashboardProvider = FutureProvider<DashboardSummary>((ref) {
  final family = ref.watch(currentFamilyProvider);
  if (family == null) throw const ApiException('Pilih keluarga terlebih dahulu.');
  return ref.watch(familyRepositoryProvider).dashboard(family.uuid);
});

final timelineProvider = FutureProvider<List<TimelineItem>>((ref) {
  final family = ref.watch(currentFamilyProvider);
  if (family == null) return const [];
  return ref.watch(familyRepositoryProvider).timeline(family.uuid);
});

final notificationsProvider = FutureProvider<List<AppNotification>>(
    (ref) => ref.watch(notificationRepositoryProvider).all());

final treeProvider = FutureProvider<FamilyTree>((ref) {
  final memberUuid = ref.watch(currentMemberUuidProvider);
  if (memberUuid == null) throw const ApiException('Pilih anggota sebagai pusat pohon.');
  return ref.watch(familyRepositoryProvider).tree(memberUuid);
});
