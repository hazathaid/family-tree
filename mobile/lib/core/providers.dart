import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;

import 'api_client.dart';
import 'auth/session_controller.dart';
import 'config/app_environment.dart';
import 'errors/app_error.dart';
import 'models.dart';
import 'repositories.dart';
import 'storage/scoped_cache.dart';
import '../features/account/data/api_account_repository.dart';
import '../features/members/data/api_member_repository.dart';
import '../features/members/domain/member_repository.dart';
import '../features/tree/data/api_tree_repository.dart';
import '../features/tree/domain/tree_repository.dart';
import '../features/content/data/api_content_repository.dart';
import '../features/content/domain/content_repository.dart';
import '../features/discovery/data/api_discovery_repository.dart';
import '../features/discovery/domain/discovery_repository.dart';

final apiClientProvider =
    Provider<ApiClient>((ref) => throw UnimplementedError());
final environmentProvider =
    Provider<AppEnvironment>((ref) => throw UnimplementedError());
final sessionControllerProvider =
    Provider<SessionController>((ref) => throw UnimplementedError());
final scopedCacheProvider =
    Provider<ScopedCache>((ref) => throw UnimplementedError());
final authRepositoryProvider =
    Provider((ref) => ApiAuthRepository(ref.watch(apiClientProvider)));
final familyRepositoryProvider =
    Provider((ref) => ApiFamilyRepository(ref.watch(apiClientProvider)));
final notificationRepositoryProvider =
    Provider((ref) => ApiNotificationRepository(ref.watch(apiClientProvider)));
final accountRepositoryProvider =
    Provider((ref) => ApiAccountRepository(ref.watch(apiClientProvider)));
final memberRepositoryProvider = Provider<MemberRepository>(
    (ref) => ApiMemberRepository(ref.watch(apiClientProvider)));
final treeRepositoryProvider = Provider<TreeRepository>(
    (ref) => ApiTreeRepository(ref.watch(apiClientProvider)));
final contentRepositoryProvider = Provider<ContentRepository>(
    (ref) => ApiContentRepository(ref.watch(apiClientProvider)));
final discoveryRepositoryProvider = Provider<DiscoveryRepository>(
    (ref) => ApiDiscoveryRepository(ref.watch(apiClientProvider)));
final currentUserProvider = StateProvider<User?>((ref) => null);

final currentFamilyProvider = StateProvider<Family?>((ref) => null);
final currentMemberUuidProvider = StateProvider<String?>((ref) => null);

final familiesProvider = FutureProvider<List<Family>>(
    (ref) => ref.watch(familyRepositoryProvider).all());
final accountSessionsProvider = FutureProvider<List<AccountSession>>(
    (ref) => ref.watch(accountRepositoryProvider).sessions());
final notificationPreferencesProvider = FutureProvider<NotificationPreferences>(
    (ref) => ref.watch(accountRepositoryProvider).preferences());

final dashboardProvider = FutureProvider<DashboardSummary>((ref) {
  final family = ref.watch(currentFamilyProvider);
  if (family == null) {
    throw const ApiException(
        AppErrorType.validation, 'Pilih keluarga terlebih dahulu.');
  }
  return ref.watch(familyRepositoryProvider).dashboard(family.uuid);
});

final timelineProvider = FutureProvider<List<TimelineItem>>((ref) {
  final family = ref.watch(currentFamilyProvider);
  if (family == null) return const [];
  return ref.watch(familyRepositoryProvider).timeline(family.uuid);
});

final notificationsProvider = FutureProvider<List<AppNotification>>(
    (ref) async =>
        (await ref.watch(notificationRepositoryProvider).all()).items);

final treeProvider = FutureProvider<FamilyTree>((ref) {
  final memberUuid = ref.watch(currentMemberUuidProvider);
  if (memberUuid == null) {
    throw const ApiException(
        AppErrorType.validation, 'Pilih anggota sebagai pusat pohon.');
  }
  return ref
      .watch(treeRepositoryProvider)
      .generate(memberUuid, mode: 'full', depth: 3, layout: 'vertical');
});
