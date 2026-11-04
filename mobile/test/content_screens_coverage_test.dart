import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/content/domain/content_models.dart';
import 'package:family_tree_mobile/features/content/presentation/content_screens.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

const draftArticle = Article(
    uuid: 'article-uuid',
    familyUuid: 'family-uuid',
    title: 'Draf Keluarga',
    content: '<p>Isi <b>artikel</b></p><script>x</script>',
    status: 'draft',
    category: ArticleCategory(uuid: 'category-uuid', name: 'Sejarah'),
    authorUuid: 'user-uuid',
    authorName: 'Budi Santoso');

final eventWithAttendee = FamilyEvent(
    uuid: 'event-uuid',
    familyUuid: 'family-uuid',
    title: 'Reuni Keluarga',
    eventDate: DateTime(2026, 5, 1, 10),
    organizerName: 'Budi Santoso',
    description: 'Deskripsi acara',
    location: 'Bandung',
    myRsvp: 'yes',
    attendees: const [
      EventAttendee(
          uuid: 'attendee-uuid',
          userUuid: 'user-uuid',
          userName: 'Budi',
          status: 'yes')
    ]);

void main() {
  group('ArticleListScreen', () {
    testWidgets('renders featured and regular articles', (tester) async {
      tester.view.physicalSize = const Size(1000, 1600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final content = FakeContentRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const Scaffold(body: ArticleListScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Pilihan keluarga'), findsOneWidget);
      expect(find.text('Sejarah Keluarga'), findsWidgets);
      expect(find.byIcon(Icons.edit), findsOneWidget);

      await tester.tap(find.text('Terbit'));
      await tester.pumpAndSettle();
      expect(content.calls, contains('articles'));
      await tester.tap(find.text('Draf'));
      await tester.pumpAndSettle();
      expect(content.calls.where((call) => call == 'articles').length,
          greaterThan(1));

      await tester.tap(find.text('Sejarah Keluarga').last);
      await tester.pumpAndSettle();
      expect(find.text('route:/articles/article-uuid'), findsOneWidget);
    });

    testWidgets('renders empty and error states', (tester) async {
      final content = FakeContentRepository()
        ..articleItems = []
        ..featuredItems = [];
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: testApp(const Scaffold(body: ArticleListScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Belum ada artikel yang dapat ditampilkan.'),
          findsOneWidget);

      content.error = const AppError(AppErrorType.server, 'Artikel gagal');
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: testApp(const Scaffold(body: ArticleListScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Artikel gagal'), findsOneWidget);
    });
  });

  group('ArticleDetailScreen', () {
    testWidgets('renders, likes, comments and manages an article',
        (tester) async {
      tester.view.physicalSize = const Size(1000, 1800);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final content = FakeContentRepository()..articleValue = draftArticle;
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        currentUserProvider.overrideWith((ref) => testUser),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const ArticleDetailScreen(uuid: 'article-uuid'))));
      await tester.pumpAndSettle();
      expect(find.text('Draf Keluarga'), findsOneWidget);
      expect(find.textContaining('Isi artikel'), findsOneWidget);

      await tester.tap(find.byIcon(Icons.favorite_border));
      await tester.pumpAndSettle();
      expect(content.calls, contains('likeArticle'));

      tester
          .widget<TextButton>(find.widgetWithText(TextButton, 'Komentar'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'Komentar baru');
      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Simpan'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(content.calls, contains('saveComment'));

      final articleMenu = tester.widget<PopupMenuButton<String>>(
          find.descendant(
              of: find.byType(AppBar),
              matching: find.byType(PopupMenuButton<String>)));
      final commentMenu = tester.widget<PopupMenuButton<String>>(find
          .descendant(
              of: find.byType(Card),
              matching: find.byType(PopupMenuButton<String>))
          .first);
      commentMenu.onSelected!('delete');
      await tester.pumpAndSettle();
      expect(content.calls, contains('deleteComment'));

      articleMenu.onSelected!('publish');
      await tester.pumpAndSettle();
      expect(content.calls, contains('publishArticle'));

      articleMenu.onSelected!('feature');
      await tester.pumpAndSettle();
      expect(content.calls, contains('featureArticle'));
    });

    testWidgets('shows article load failures', (tester) async {
      final content = FakeContentRepository()
        ..error = const AppError(AppErrorType.server, 'Artikel gagal');
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: testApp(const ArticleDetailScreen(uuid: 'article-uuid'))));
      await tester.pumpAndSettle();
      expect(find.text('Artikel gagal'), findsOneWidget);
    });
  });

  group('ArticleEditorScreen', () {
    testWidgets('creates and edits an article', (tester) async {
      tester.view.physicalSize = const Size(1000, 1600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final content = FakeContentRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const ArticleEditorScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Sejarah'), findsOneWidget);

      await tester.enterText(find.byType(TextField).at(0), 'Artikel Baru');
      await tester.enterText(find.byType(TextField).at(1), 'Ringkasan');
      await tester.enterText(find.byType(TextField).at(2), 'Isi artikel');
      await tester.drag(find.byType(Scrollable).first, const Offset(0, -800));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Simpan draf'));
      await tester.pumpAndSettle();
      expect(content.calls, contains('saveArticle'));

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const ArticleEditorScreen(article: draftArticle))));
      await tester.pumpAndSettle();
      expect(find.text('Draf Keluarga'), findsOneWidget);
      await tester.drag(find.byType(Scrollable).first, const Offset(0, -800));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Simpan draf'));
      await tester.pumpAndSettle();
      expect(content.calls.where((call) => call == 'saveArticle').length, 2);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const ArticleEditorScreen(article: draftArticle))));
      await tester.pumpAndSettle();
      content.error = const AppError(AppErrorType.server, 'Simpan gagal');
      await tester.drag(find.byType(Scrollable).first, const Offset(0, -800));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Simpan draf'));
      await tester.pumpAndSettle();
      expect(find.text('Simpan gagal'), findsOneWidget);
    });
  });

  group('GalleryScreen', () {
    testWidgets('renders albums and photos and edits albums', (tester) async {
      tester.view.physicalSize = const Size(1000, 1800);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final content = FakeContentRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const Scaffold(body: GalleryScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Album Lama'), findsWidgets);
      expect(find.byIcon(Icons.add_a_photo), findsOneWidget);

      await tester.tap(find.byIcon(Icons.create_new_folder_outlined));
      await tester.pumpAndSettle();
      await tester.enterText(
          find
              .descendant(
                  of: find.byType(AlertDialog),
                  matching: find.byType(TextField))
              .first,
          'Album Baru');
      tester
          .widget<FilledButton>(find.descendant(
              of: find.byType(AlertDialog),
              matching: find.widgetWithText(FilledButton, 'Simpan')))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(content.calls, contains('saveAlbum'));

      final chip = tester.widget<InputChip>(find.byType(InputChip).first);
      chip.onPressed!.call();
      await tester.pumpAndSettle();
      await tester.tap(find.text('Simpan').last);
      await tester.pumpAndSettle();
      expect(content.calls.where((call) => call == 'saveAlbum').length,
          greaterThan(1));

      final deleteChip = tester.widget<InputChip>(find.byType(InputChip).first);
      deleteChip.onDeleted!.call();
      await tester.pumpAndSettle();
      await tester.tap(find.text('Hapus').last);
      await tester.pumpAndSettle();
      expect(content.calls, contains('deleteAlbum'));

      tester
          .widget<DropdownButtonFormField<String?>>(
              find.byType(DropdownButtonFormField<String?>))
          .onChanged!('album-uuid');
      await tester.pumpAndSettle();
      expect(content.calls.where((call) => call == 'photos').length,
          greaterThan(1));
    });

    testWidgets('renders photo errors and empty states', (tester) async {
      final content = FakeContentRepository()..photoItems = [];
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: testApp(const Scaffold(body: GalleryScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Belum ada foto.'), findsOneWidget);

      content.error = const AppError(AppErrorType.server, 'Galeri gagal');
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: testApp(const Scaffold(body: GalleryScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Galeri gagal'), findsOneWidget);
    });
  });

  testWidgets('PhotoUploadScreen renders albums and date controls',
      (tester) async {
    tester.view.physicalSize = const Size(1000, 1600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final content = FakeContentRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      contentRepositoryProvider.overrideWithValue(content),
    ], child: testApp(const PhotoUploadScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Galeri'), findsOneWidget);
    expect(find.text('Kamera'), findsOneWidget);

    tester
        .widget<DropdownButtonFormField<String?>>(
            find.byType(DropdownButtonFormField<String?>))
        .onChanged!('album-uuid');
    await tester.pumpAndSettle();

    await tester.tap(find.text('Tanggal pengambilan'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('OKE'));
    await tester.pumpAndSettle();
    expect(find.text('Tidak ditentukan'), findsNothing);
    final upload = tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Unggah'));
    expect(upload.onPressed, isNull);
  });

  testWidgets('PhotoDetailScreen tags and deletes a photo', (tester) async {
    tester.view.physicalSize = const Size(1000, 1800);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final content = FakeContentRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      contentRepositoryProvider.overrideWithValue(content),
      memberRepositoryProvider.overrideWithValue(FakeMemberRepository()),
    ], child: routedApp(const PhotoDetailScreen(uuid: 'photo-uuid'))));
    await tester.pumpAndSettle();
    expect(find.text('Foto keluarga'), findsOneWidget);
    expect(find.text('Album: Album Lama'), findsOneWidget);

    await tester.tap(find.byTooltip('Tag anggota'));
    await tester.pumpAndSettle();
    await tester.tap(find.byType(CheckboxListTile));
    await tester.pumpAndSettle();
    tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Simpan'))
        .onPressed!
        .call();
    await tester.pumpAndSettle();
    expect(content.calls, contains('tagPhoto'));

    await tester.tap(find.byTooltip('Hapus foto'));
    await tester.pumpAndSettle();
    expect(content.calls, contains('deletePhoto'));
    expect(find.text('route:/photos'), findsOneWidget);
  });

  testWidgets('PhotoDetailScreen shows failures', (tester) async {
    final content = FakeContentRepository()
      ..error = const AppError(AppErrorType.server, 'Foto gagal');
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      contentRepositoryProvider.overrideWithValue(content),
    ], child: testApp(const PhotoDetailScreen(uuid: 'photo-uuid'))));
    await tester.pumpAndSettle();
    expect(find.text('Foto gagal'), findsOneWidget);
  });

  group('EventListScreen', () {
    testWidgets('lists events and switches filters', (tester) async {
      tester.view.physicalSize = const Size(1000, 1600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final content = FakeContentRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const Scaffold(body: EventListScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Reuni Keluarga'), findsOneWidget);
      expect(find.byIcon(Icons.add), findsOneWidget);

      await tester.tap(find.text('Semua'));
      await tester.pumpAndSettle();
      expect(content.calls.where((call) => call == 'events').length,
          greaterThan(1));

      await tester.tap(find.text('Reuni Keluarga'));
      await tester.pumpAndSettle();
      expect(find.text('route:/events/event-uuid'), findsOneWidget);
    });

    testWidgets('shows empty and error states', (tester) async {
      final content = FakeContentRepository()..eventItems = [];
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: testApp(const Scaffold(body: EventListScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Belum ada acara.'), findsOneWidget);

      content.error = const AppError(AppErrorType.server, 'Acara gagal');
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: testApp(const Scaffold(body: EventListScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Acara gagal'), findsOneWidget);
    });
  });

  group('EventDetailScreen', () {
    testWidgets('updates RSVP, edits and deletes an event', (tester) async {
      tester.view.physicalSize = const Size(1000, 1600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final content = FakeContentRepository()..eventValue = eventWithAttendee;
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const EventDetailScreen(uuid: 'event-uuid'))));
      await tester.pumpAndSettle();
      expect(find.text('Reuni Keluarga'), findsOneWidget);
      expect(find.text('Budi'), findsOneWidget);

      await tester.tap(find.text('Mungkin'));
      await tester.pumpAndSettle();
      expect(content.calls, contains('rsvp'));

      await tester.tap(find.byTooltip('Edit acara'));
      await tester.pumpAndSettle();
      expect(find.text('route:/events/event-uuid/edit'), findsOneWidget);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const EventDetailScreen(uuid: 'event-uuid'))));
      await tester.pumpAndSettle();
      await tester.tap(find.byTooltip('Hapus acara'));
      await tester.pumpAndSettle();
      expect(content.calls, contains('deleteEvent'));
      expect(find.text('route:/events'), findsOneWidget);
    });

    testWidgets('shows event load failures', (tester) async {
      final content = FakeContentRepository()
        ..error = const AppError(AppErrorType.server, 'Acara gagal');
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: testApp(const EventDetailScreen(uuid: 'event-uuid'))));
      await tester.pumpAndSettle();
      expect(find.text('Acara gagal'), findsOneWidget);
    });
  });

  group('EventFormScreen', () {
    testWidgets('creates and edits an event', (tester) async {
      tester.view.physicalSize = const Size(1000, 1600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final content = FakeContentRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const EventFormScreen())));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField).at(0), 'Acara Baru');
      await tester.tap(find.text('Pilih waktu'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('OKE'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('OKE'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(content.calls, contains('saveEvent'));

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(EventFormScreen(event: eventWithAttendee))));
      await tester.pumpAndSettle();
      expect(find.text('Reuni Keluarga'), findsOneWidget);
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(content.calls.where((call) => call == 'saveEvent').length, 2);

      content.error = const AppError(AppErrorType.server, 'Simpan acara gagal');
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        contentRepositoryProvider.overrideWithValue(content),
      ], child: routedApp(const EventFormScreen())));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField).at(0), 'Acara Gagal');
      await tester.tap(find.text('Pilih waktu'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('OKE'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('OKE'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(find.text('Simpan acara gagal'), findsOneWidget);
    });
  });

  group('TimelineScreen', () {
    testWidgets('renders items and opens targets', (tester) async {
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        timelineProvider.overrideWith((ref) async => [sampleTimeline]),
      ], child: routedApp(const Scaffold(body: TimelineScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Anggota ditambahkan'), findsOneWidget);
      await tester.tap(find.text('Anggota ditambahkan'));
      await tester.pumpAndSettle();
      expect(find.text('route:/members/member-uuid'), findsOneWidget);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        timelineProvider.overrideWith((ref) async => []),
      ], child: testApp(const Scaffold(body: TimelineScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Belum ada aktivitas.'), findsOneWidget);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        timelineProvider.overrideWith(
            (ref) async => throw const AppError(AppErrorType.server, 'Gagal')),
      ], child: testApp(const Scaffold(body: TimelineScreen()))));
      await tester.pumpAndSettle();
      expect(find.text('Gagal'), findsOneWidget);
    });
  });
}
