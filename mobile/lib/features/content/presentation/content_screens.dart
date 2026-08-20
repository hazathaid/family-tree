import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/models.dart';
import '../../../core/providers.dart';
import '../../../l10n/app_localizations.dart';
import '../domain/content_models.dart';

String _date(DateTime value) =>
    '${value.toLocal().day.toString().padLeft(2, '0')}/${value.toLocal().month.toString().padLeft(2, '0')}/${value.toLocal().year} ${value.toLocal().hour.toString().padLeft(2, '0')}:${value.toLocal().minute.toString().padLeft(2, '0')}';
bool _canManage(Family? family) => family?.canManage ?? false;

class ArticleListScreen extends ConsumerStatefulWidget {
  const ArticleListScreen({super.key});
  @override
  ConsumerState<ArticleListScreen> createState() => _ArticleListState();
}

class _ArticleListState extends ConsumerState<ArticleListScreen> {
  final search = TextEditingController();
  int page = 1;
  String? status;
  bool loading = true;
  Object? error;
  List<Article> articles = const [], featured = const [];
  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  Future<void> load() async {
    final family = ref.read(currentFamilyProvider);
    if (family == null) return;
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final repo = ref.read(contentRepositoryProvider);
      final values = await Future.wait([
        repo.articles(family.uuid,
            page: page, search: search.text, status: status),
        repo.featuredArticles(family.uuid)
      ]);
      if (mounted) {
        setState(() {
          articles = (values[0] as dynamic).items as List<Article>;
          featured = values[1] as List<Article>;
          loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          error = e;
          loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final family = ref.watch(currentFamilyProvider);
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.familyArticles)),
        floatingActionButton: _canManage(family)
            ? FloatingActionButton(
                tooltip: l10n.writeArticle,
                onPressed: () => context.push('/articles/new'),
                child: const Icon(Icons.edit))
            : null,
        body: RefreshIndicator(
            onRefresh: load,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              SearchBar(
                  controller: search,
                  hintText: l10n.searchArticles,
                  onSubmitted: (_) {
                    page = 1;
                    load();
                  }),
              const SizedBox(height: 12),
              SegmentedButton<String>(
                  segments: [
                    ButtonSegment(value: 'all', label: Text(l10n.all)),
                    ButtonSegment(
                        value: 'published', label: Text(l10n.published)),
                    ButtonSegment(value: 'draft', label: Text(l10n.draft))
                  ],
                  selected: {
                    status ?? 'all'
                  },
                  onSelectionChanged: (v) {
                    status = v.first == 'all' ? null : v.first;
                    page = 1;
                    load();
                  }),
              if (featured.isNotEmpty) ...[
                const SizedBox(height: 20),
                Text(l10n.featuredArticles,
                    style: Theme.of(context).textTheme.titleMedium),
                ...featured.map((a) => _ArticleTile(article: a, featured: true))
              ],
              const SizedBox(height: 16),
              if (loading)
                const Center(child: CircularProgressIndicator())
              else if (error != null)
                _Retry(error: error!, retry: load)
              else if (articles.isEmpty)
                _Empty(
                    icon: Icons.article_outlined, text: l10n.noArticles)
              else
                ...articles.map((a) => _ArticleTile(article: a)),
            ])));
  }
}

class _ArticleTile extends StatelessWidget {
  const _ArticleTile({required this.article, this.featured = false});
  final Article article;
  final bool featured;
  @override
  Widget build(BuildContext context) => Card(
      child: ListTile(
          minTileHeight: 64,
          leading: Icon(featured ? Icons.star : Icons.article_outlined),
          title: Text(article.title),
          subtitle: Text('${article.authorName} · ${article.status}'),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => context.push('/articles/${article.uuid}')));
}

class ArticleDetailScreen extends ConsumerStatefulWidget {
  const ArticleDetailScreen({required this.uuid, super.key});
  final String uuid;
  @override
  ConsumerState<ArticleDetailScreen> createState() => _ArticleDetailState();
}

class _ArticleDetailState extends ConsumerState<ArticleDetailScreen> {
  Article? article;
  List<ArticleComment> comments = const [];
  Object? error;
  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  Future<void> load() async {
    try {
      final repo = ref.read(contentRepositoryProvider);
      final values = await Future.wait(
          [repo.article(widget.uuid), repo.comments(widget.uuid)]);
      if (mounted) {
        setState(() {
          article = values[0] as Article;
          comments = (values[1] as dynamic).items as List<ArticleComment>;
          error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() => error = e);
    }
  }

  Future<void> comment([ArticleComment? existing]) async {
    final l10n = AppLocalizations.of(context);
    final c = TextEditingController(text: existing?.comment);
    final value = await showDialog<String>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text(
                    existing == null ? l10n.writeComment : l10n.editComment),
                content: TextField(controller: c, minLines: 2, maxLines: 5),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: Text(l10n.cancel)),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, c.text.trim()),
                      child: Text(l10n.save))
                ]));
    if (value?.isNotEmpty == true) {
      await ref
          .read(contentRepositoryProvider)
          .saveComment(widget.uuid, value!, uuid: existing?.uuid);
      await load();
    }
  }

  @override
  Widget build(BuildContext context) {
    final a = article;
    final family = ref.watch(currentFamilyProvider);
    final user = ref.watch(currentUserProvider);
    final l10n = AppLocalizations.of(context);
    if (error != null) {
      return Scaffold(
          appBar: AppBar(), body: _Retry(error: error!, retry: load));
    }
    if (a == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return Scaffold(
        appBar: AppBar(title: Text(l10n.articleDetail), actions: [
          if (_canManage(family) || user?.uuid == a.authorUuid)
            PopupMenuButton<String>(
                onSelected: (v) async {
                  final repo = ref.read(contentRepositoryProvider);
                  if (v == 'edit') {
                    context.push('/articles/${a.uuid}/edit', extra: a);
                    return;
                  }
                  if (v == 'publish') await repo.publishArticle(a.uuid);
                  if (v == 'feature') {
                    await repo.featureArticle(a.uuid, !a.isFeatured);
                  }
                  if (v == 'delete') {
                    await repo.deleteArticle(a.uuid);
                    if (context.mounted) context.pop();
                    return;
                  }
                  await load();
                },
                itemBuilder: (_) => [
                      PopupMenuItem(
                          value: 'edit', child: Text(l10n.editLabel)),
                      if (a.status == 'draft')
                        PopupMenuItem(
                            value: 'publish', child: Text(l10n.publish)),
                      PopupMenuItem(
                          value: 'feature',
                          child: Text(a.isFeatured
                              ? l10n.unfeatureArticle
                              : l10n.featureArticle)),
                      PopupMenuItem(
                          value: 'delete', child: Text(l10n.delete))
                    ])
        ]),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          Text(a.title, style: Theme.of(context).textTheme.headlineSmall),
          Text('${a.authorName} · ${a.category.name}'),
          const SizedBox(height: 16),
          if (a.featuredImageUrl != null)
            Image.network(a.featuredImageUrl!,
                semanticLabel: l10n.featuredImageLabel(a.title),
                errorBuilder: (_, __, ___) => const SizedBox.shrink()),
          const SizedBox(height: 12),
          SelectableText(_safeRichText(a.content)),
          const Divider(height: 32),
          Row(children: [
            IconButton(
                tooltip: a.isLikedByMe ? l10n.unlike : l10n.like,
                icon: Icon(
                    a.isLikedByMe ? Icons.favorite : Icons.favorite_border),
                onPressed: () async {
                  await ref
                      .read(contentRepositoryProvider)
                      .likeArticle(a.uuid, !a.isLikedByMe);
                  await load();
                }),
            Text(l10n.likesCount(a.likesCount)),
            const Spacer(),
            TextButton.icon(
                onPressed: () => comment(),
                icon: const Icon(Icons.comment),
                label: Text(l10n.commentsLabel))
          ]),
          ...comments.map((c) => Card(
              child: ListTile(
                  title: Text(c.userName),
                  subtitle: Text(c.comment),
                  trailing: (c.userUuid == user?.uuid || _canManage(family))
                      ? PopupMenuButton<String>(
                          onSelected: (v) async {
                            if (v == 'edit') {
                              await comment(c);
                            } else {
                              await ref
                                  .read(contentRepositoryProvider)
                                  .deleteComment(a.uuid, c.uuid);
                              await load();
                            }
                          },
                          itemBuilder: (_) => [
                                if (c.userUuid == user?.uuid)
                                  PopupMenuItem(
                                      value: 'edit',
                                      child: Text(l10n.editLabel)),
                                PopupMenuItem(
                                    value: 'delete',
                                    child: Text(l10n.delete))
                              ])
                      : null)))
        ]));
  }
}

String _safeRichText(String html) => html
    .replaceAll(
        RegExp(r'<(script|style)[^>]*>.*?</\1>',
            caseSensitive: false, dotAll: true),
        '')
    .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n')
    .replaceAll(RegExp(r'</(p|div|li|h[1-6])>', caseSensitive: false), '\n')
    .replaceAll(RegExp(r'<[^>]+>'), '')
    .replaceAll('&lt;', '<')
    .replaceAll('&gt;', '>')
    .replaceAll('&amp;', '&')
    .trim();

class ArticleEditorScreen extends ConsumerStatefulWidget {
  const ArticleEditorScreen({this.article, super.key});
  final Article? article;
  @override
  ConsumerState<ArticleEditorScreen> createState() => _ArticleEditorState();
}

class _ArticleEditorState extends ConsumerState<ArticleEditorScreen> {
  final title = TextEditingController(),
      excerpt = TextEditingController(),
      content = TextEditingController();
  List<ArticleCategory> categories = const [];
  String? category;
  XFile? featuredImage;
  bool saving = false;
  @override
  void initState() {
    super.initState();
    final a = widget.article;
    title.text = a?.title ?? '';
    excerpt.text = a?.excerpt ?? '';
    content.text = a == null ? '' : _safeRichText(a.content);
    category = a?.category.uuid;
    Future.microtask(() async {
      final c = await ref.read(contentRepositoryProvider).categories();
      if (mounted) {
        setState(() {
          categories = c;
          if (category == null && c.isNotEmpty) category = c.first.uuid;
        });
      }
    });
  }

  Future<void> save() async {
    final family = ref.read(currentFamilyProvider);
    if (family == null ||
        title.text.trim().isEmpty ||
        content.text.trim().isEmpty ||
        category == null) {
      return;
    }
    setState(() => saving = true);
    try {
      final escaped = content.text
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .split('\n')
          .where((e) => e.trim().isNotEmpty)
          .map((e) => '<p>$e</p>')
          .join();
      final value = await ref.read(contentRepositoryProvider).saveArticle(
          family.uuid,
          {
            'category_uuid': category,
            'title': title.text.trim(),
            'excerpt': excerpt.text.trim(),
            'content': escaped,
            'status': widget.article?.status ?? 'draft'
          },
          uuid: widget.article?.uuid);
      if (featuredImage != null) {
        await ref
            .read(contentRepositoryProvider)
            .uploadArticleImage(value.uuid, featuredImage!.path);
      }
      if (mounted) context.go('/articles/${value.uuid}');
    } catch (e) {
      if (mounted) {
        final l10n = AppLocalizations.of(context);
        final message = e is AppError
            ? e.message
            : l10n.requestFailed;
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(
            title:
                Text(widget.article == null ? l10n.writeArticle : l10n.editArticle)),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          TextField(
              controller: title,
              decoration: InputDecoration(labelText: l10n.titleLabel)),
          const SizedBox(height: 12),
          DropdownButtonFormField(
              initialValue: category,
              decoration: InputDecoration(labelText: l10n.category),
              items: categories
                  .map(
                      (c) => DropdownMenuItem(value: c.uuid, child: Text(c.name)))
                  .toList(),
              onChanged: (v) => setState(() => category = v)),
          const SizedBox(height: 12),
          TextField(
              controller: excerpt,
              decoration: InputDecoration(labelText: l10n.excerpt),
              maxLines: 2),
          const SizedBox(height: 12),
          TextField(
              controller: content,
              decoration:
                  InputDecoration(labelText: l10n.contentLabel),
              minLines: 10,
              maxLines: 20),
          const SizedBox(height: 16),
          OutlinedButton.icon(
              onPressed: () async {
                final image = await ImagePicker().pickImage(
                    source: ImageSource.gallery,
                    imageQuality: 88,
                    maxWidth: 2400);
                if (image == null) return;
                if (await image.length() > 10 * 1024 * 1024) {
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                        content: Text(l10n.featuredImageSizeLimit)));
                  }
                  return;
                }
                setState(() => featuredImage = image);
              },
              icon: const Icon(Icons.image_outlined),
              label: Text(featuredImage?.name ?? l10n.chooseFeaturedImage)),
          const SizedBox(height: 8),
          FilledButton(
              onPressed: saving ? null : save,
              child: Text(saving ? l10n.saving : l10n.saveDraft))
        ]));
  }
}

class GalleryScreen extends ConsumerStatefulWidget {
  const GalleryScreen({super.key});
  @override
  ConsumerState<GalleryScreen> createState() => _GalleryState();
}

class _GalleryState extends ConsumerState<GalleryScreen> {
  List<PhotoAlbum> albums = const [];
  List<FamilyPhoto> photos = const [];
  String? album;
  Object? error;
  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  Future<void> load() async {
    final family = ref.read(currentFamilyProvider);
    if (family == null) return;
    try {
      final repo = ref.read(contentRepositoryProvider);
      final values = await Future.wait([
        repo.albums(family.uuid),
        repo.photos(family.uuid, albumUuid: album)
      ]);
      if (mounted) {
        setState(() {
          albums = (values[0] as dynamic).items as List<PhotoAlbum>;
          photos = (values[1] as dynamic).items as List<FamilyPhoto>;
          error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() => error = e);
    }
  }

  Future<void> editAlbum([PhotoAlbum? value]) async {
    final l10n = AppLocalizations.of(context);
    final name = TextEditingController(text: value?.name),
        desc = TextEditingController(text: value?.description);
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text(value == null ? l10n.createAlbum : l10n.editAlbum),
                content: Column(mainAxisSize: MainAxisSize.min, children: [
                  TextField(
                      controller: name,
                      decoration: InputDecoration(labelText: l10n.nameLabel)),
                  TextField(
                      controller: desc,
                      decoration: InputDecoration(labelText: l10n.description))
                ]),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: Text(l10n.cancel)),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: Text(l10n.save))
                ]));
    if (ok == true && name.text.trim().isNotEmpty) {
      await ref.read(contentRepositoryProvider).saveAlbum(
          ref.read(currentFamilyProvider)!.uuid,
          name.text.trim(),
          desc.text.trim(),
          uuid: value?.uuid);
      await load();
    }
  }

  @override
  Widget build(BuildContext context) {
    final family = ref.watch(currentFamilyProvider);
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.albumGallery), actions: [
          if (_canManage(family))
            IconButton(
                tooltip: l10n.createAlbum,
                onPressed: () => editAlbum(),
                icon: const Icon(Icons.create_new_folder_outlined))
        ]),
        floatingActionButton: FloatingActionButton(
            tooltip: l10n.uploadPhoto,
            onPressed: () => context.push('/photos/upload'),
            child: const Icon(Icons.add_a_photo)),
        body: error != null
            ? _Retry(error: error!, retry: load)
            : RefreshIndicator(
                onRefresh: load,
                child: ListView(padding: const EdgeInsets.all(16), children: [
                  DropdownButtonFormField<String?>(
                      initialValue: album,
                      decoration: InputDecoration(labelText: l10n.album),
                      items: [
                        DropdownMenuItem(
                            value: null, child: Text(l10n.allPhotos)),
                        ...albums.map((a) => DropdownMenuItem(
                            value: a.uuid,
                            child: Text('${a.name} (${a.photosCount})')))
                      ],
                      onChanged: (v) {
                        album = v;
                        load();
                      }),
                  const SizedBox(height: 12),
                  if (_canManage(family))
                    Wrap(
                        spacing: 8,
                        children: albums
                            .map((a) => InputChip(
                                label: Text(a.name),
                                onPressed: () => editAlbum(a),
                                avatar: const Icon(Icons.edit, size: 16),
                                deleteButtonTooltipMessage: l10n.deleteAlbum,
                                onDeleted: () async {
                                  final ok = await showDialog<bool>(
                                      context: context,
                                      builder: (_) => AlertDialog(
                                              title: Text(l10n
                                                  .deleteAlbumTitle(a.name)),
                                              content: Text(l10n
                                                  .deleteAlbumConfirmation),
                                              actions: [
                                                TextButton(
                                                    onPressed: () =>
                                                        Navigator.pop(
                                                            context, false),
                                                    child:
                                                        Text(l10n.cancel)),
                                                FilledButton(
                                                    onPressed: () =>
                                                        Navigator.pop(
                                                            context, true),
                                                    child:
                                                        Text(l10n.delete))
                                              ]));
                                  if (ok == true) {
                                    await ref
                                        .read(contentRepositoryProvider)
                                        .deleteAlbum(a.uuid);
                                    await load();
                                  }
                                }))
                            .toList()),
                  if (photos.isEmpty)
                    _Empty(
                        icon: Icons.photo_library_outlined,
                        text: l10n.noPhotos)
                  else
                    GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: photos.length,
                        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount:
                                MediaQuery.sizeOf(context).width >= 600 ? 4 : 2,
                            crossAxisSpacing: 8,
                            mainAxisSpacing: 8),
                        itemBuilder: (_, i) => InkWell(
                            onTap: () =>
                                context.push('/photos/${photos[i].uuid}'),
                            child: Semantics(
                                label:
                                    photos[i].caption ?? l10n.familyPhoto,
                                child: Image.network(photos[i].thumbnailUrl,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) =>
                                        const ColoredBox(
                                            color: Colors.black12,
                                            child: Icon(Icons
                                                .broken_image_outlined))))))
                ])));
  }
}

class PhotoUploadScreen extends ConsumerStatefulWidget {
  const PhotoUploadScreen({super.key});
  @override
  ConsumerState<PhotoUploadScreen> createState() => _PhotoUploadState();
}

class _PhotoUploadState extends ConsumerState<PhotoUploadScreen> {
  XFile? file;
  final caption = TextEditingController();
  DateTime? captured;
  double progress = 0;
  bool saving = false;
  List<PhotoAlbum> albums = const [];
  String? albumUuid;
  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      final family = ref.read(currentFamilyProvider);
      if (family == null) return;
      final page =
          await ref.read(contentRepositoryProvider).albums(family.uuid);
      if (mounted) setState(() => albums = page.items);
    });
  }

  Future<void> pick(ImageSource source) async {
    final l10n = AppLocalizations.of(context);
    final value = await ImagePicker()
        .pickImage(source: source, imageQuality: 88, maxWidth: 2400);
    if (value == null) return;
    final size = await value.length();
    final ext = value.name.split('.').last.toLowerCase();
    if (size > 10 * 1024 * 1024 ||
        !{'jpg', 'jpeg', 'png', 'webp'}.contains(ext)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(l10n.photoValidation)));
      }
      return;
    }
    setState(() => file = value);
  }

  Future<void> upload() async {
    if (file == null) return;
    setState(() => saving = true);
    try {
      final photo = await ref.read(contentRepositoryProvider).uploadPhoto(
          ref.read(currentFamilyProvider)!.uuid, file!.path,
          albumUuid: albumUuid,
          caption: caption.text.trim(),
          capturedAt: captured, onProgress: (sent, total) {
        if (mounted && total > 0) setState(() => progress = sent / total);
      });
      if (mounted) context.go('/photos/${photo.uuid}');
    } catch (e) {
      if (mounted) {
        final l10n = AppLocalizations.of(context);
        final message = e is AppError ? e.message : l10n.requestFailed;
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.uploadPhoto)),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          Wrap(spacing: 8, children: [
            OutlinedButton.icon(
                onPressed: () => pick(ImageSource.gallery),
                icon: const Icon(Icons.photo),
                label: Text(l10n.gallery)),
            OutlinedButton.icon(
                onPressed: () => pick(ImageSource.camera),
                icon: const Icon(Icons.camera_alt),
                label: Text(l10n.camera))
          ]),
          if (file != null) ...[
            const SizedBox(height: 12),
            Image.file(File(file!.path),
                height: 220,
                fit: BoxFit.contain,
                semanticLabel: l10n.photoPreview)
          ],
          const SizedBox(height: 12),
          TextField(
              controller: caption,
              decoration: InputDecoration(labelText: l10n.caption),
              maxLines: 3),
          DropdownButtonFormField<String?>(
              initialValue: albumUuid,
              decoration: InputDecoration(labelText: l10n.album),
              items: [
                DropdownMenuItem(value: null, child: Text(l10n.noAlbum)),
                ...albums.map(
                    (a) => DropdownMenuItem(value: a.uuid, child: Text(a.name)))
              ],
              onChanged: (v) => setState(() => albumUuid = v)),
          ListTile(
              title: Text(l10n.capturedDate),
              subtitle: Text(captured == null
                  ? l10n.notSpecified
                  : _date(captured!)),
              trailing: const Icon(Icons.calendar_today),
              onTap: () async {
                final d = await showDatePicker(
                    context: context,
                    firstDate: DateTime(1900),
                    lastDate: DateTime.now(),
                    initialDate: captured ?? DateTime.now());
                if (d != null) setState(() => captured = d);
              }),
          if (saving)
            LinearProgressIndicator(value: progress == 0 ? null : progress),
          FilledButton(
              onPressed: saving || file == null ? null : upload,
              child: Text(l10n.upload))
        ]));
  }
}

class PhotoDetailScreen extends ConsumerStatefulWidget {
  const PhotoDetailScreen({required this.uuid, super.key});
  final String uuid;
  @override
  ConsumerState<PhotoDetailScreen> createState() => _PhotoDetailState();
}

class _PhotoDetailState extends ConsumerState<PhotoDetailScreen> {
  FamilyPhoto? photo;
  Object? error;
  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  Future<void> load() async {
    try {
      final p = await ref.read(contentRepositoryProvider).photo(widget.uuid);
      if (mounted) {
        setState(() {
          photo = p;
          error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() => error = e);
    }
  }

  Future<void> tags() async {
    final l10n = AppLocalizations.of(context);
    final family = ref.read(currentFamilyProvider)!;
    final page = await ref
        .read(memberRepositoryProvider)
        .members(family.uuid, limit: 100);
    final selected = photo!.taggedMembers.map((e) => e.uuid).toSet();
    if (!mounted) return;
    final result = await showDialog<Set<String>>(
        context: context,
        builder: (_) => StatefulBuilder(
            builder: (context, setLocal) => AlertDialog(
                    title: Text(l10n.tagMembers),
                    content: SizedBox(
                        width: 400,
                        child: ListView(
                            shrinkWrap: true,
                            children: page.items
                                .map((m) => CheckboxListTile(
                                    value: selected.contains(m.uuid),
                                    title: Text(m.displayName),
                                    onChanged: (v) => setLocal(() => v == true
                                        ? selected.add(m.uuid)
                                        : selected.remove(m.uuid))))
                                .toList())),
                    actions: [
                      TextButton(
                          onPressed: () => Navigator.pop(context),
                          child: Text(l10n.cancel)),
                      FilledButton(
                          onPressed: () => Navigator.pop(context, selected),
                          child: Text(l10n.save))
                    ])));
    if (result != null) {
      await ref
          .read(contentRepositoryProvider)
          .tagPhoto(widget.uuid, result.toList());
      await load();
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = photo;
    final l10n = AppLocalizations.of(context);
    if (error != null) {
      return Scaffold(
          appBar: AppBar(), body: _Retry(error: error!, retry: load));
    }
    if (p == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return Scaffold(
        appBar: AppBar(title: Text(l10n.photoDetail), actions: [
          IconButton(
              tooltip: l10n.tagMembers,
              onPressed: tags,
              icon: const Icon(Icons.sell_outlined)),
          IconButton(
              tooltip: l10n.deletePhoto,
              onPressed: () async {
                await ref.read(contentRepositoryProvider).deletePhoto(p.uuid);
                if (context.mounted) context.go('/photos');
              },
              icon: const Icon(Icons.delete_outline))
        ]),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          Image.network(p.url, semanticLabel: p.caption ?? l10n.familyPhoto),
          if (p.caption != null)
            Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Text(p.caption!)),
          Text(p.albumName == null
              ? l10n.noAlbum
              : l10n.photoAlbumLabel(p.albumName!)),
          if (p.capturedAt != null)
            Text(l10n.takenAt(_date(p.capturedAt!))),
          Wrap(
              spacing: 6,
              children: p.taggedMembers
                  .map((m) => Chip(label: Text(m.name)))
                  .toList())
        ]));
  }
}

class EventListScreen extends ConsumerStatefulWidget {
  const EventListScreen({super.key});
  @override
  ConsumerState<EventListScreen> createState() => _EventListState();
}

class _EventListState extends ConsumerState<EventListScreen> {
  final search = TextEditingController();
  bool? upcoming = true;
  List<FamilyEvent> items = const [];
  Object? error;
  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  Future<void> load() async {
    try {
      final p = await ref.read(contentRepositoryProvider).events(
          ref.read(currentFamilyProvider)!.uuid,
          search: search.text,
          upcoming: upcoming);
      if (mounted) {
        setState(() {
          items = p.items;
          error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() => error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final family = ref.watch(currentFamilyProvider);
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.familyEvents)),
        floatingActionButton: _canManage(family)
            ? FloatingActionButton(
                tooltip: l10n.createEvent,
                onPressed: () => context.push('/events/new'),
                child: const Icon(Icons.add))
            : null,
        body: RefreshIndicator(
            onRefresh: load,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              SearchBar(
                  controller: search,
                  hintText: l10n.searchEvents,
                  onSubmitted: (_) => load()),
              const SizedBox(height: 12),
              SegmentedButton<String>(
                  segments: [
                    ButtonSegment(
                        value: 'upcoming', label: Text(l10n.upcoming)),
                    ButtonSegment(value: 'all', label: Text(l10n.all))
                  ],
                  selected: {
                    upcoming == true ? 'upcoming' : 'all'
                  },
                  onSelectionChanged: (v) {
                    upcoming = v.first == 'upcoming' ? true : null;
                    load();
                  }),
              const SizedBox(height: 12),
              if (error != null)
                _Retry(error: error!, retry: load)
              else if (items.isEmpty)
                _Empty(icon: Icons.event_busy, text: l10n.noEvents)
              else
                ...items.map((e) => Card(
                    child: ListTile(
                        minTileHeight: 64,
                        title: Text(e.title),
                        subtitle: Text(
                            '${_date(e.eventDate)} ${e.location == null ? '' : '· ${e.location}'}\n${l10n.deviceTime(DateTime.now().timeZoneName)}'),
                        isThreeLine: true,
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => context.push('/events/${e.uuid}'))))
            ])));
  }
}

class EventDetailScreen extends ConsumerStatefulWidget {
  const EventDetailScreen({required this.uuid, super.key});
  final String uuid;
  @override
  ConsumerState<EventDetailScreen> createState() => _EventDetailState();
}

class _EventDetailState extends ConsumerState<EventDetailScreen> {
  FamilyEvent? event;
  Object? error;
  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  Future<void> load() async {
    try {
      final e = await ref.read(contentRepositoryProvider).event(widget.uuid);
      if (mounted) {
        setState(() {
          event = e;
          error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() => error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final e = event;
    final family = ref.watch(currentFamilyProvider);
    final l10n = AppLocalizations.of(context);
    if (error != null) {
      return Scaffold(
          appBar: AppBar(), body: _Retry(error: error!, retry: load));
    }
    if (e == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return Scaffold(
        appBar: AppBar(title: Text(l10n.eventDetail), actions: [
          if (_canManage(family))
            IconButton(
                tooltip: l10n.editEvent,
                onPressed: () =>
                    context.push('/events/${e.uuid}/edit', extra: e),
                icon: const Icon(Icons.edit)),
          if (_canManage(family))
            IconButton(
                tooltip: l10n.deleteEvent,
                onPressed: () async {
                  await ref.read(contentRepositoryProvider).deleteEvent(e.uuid);
                  if (context.mounted) context.go('/events');
                },
                icon: const Icon(Icons.delete_outline))
        ]),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          Text(e.title, style: Theme.of(context).textTheme.headlineSmall),
          Text('${_date(e.eventDate)} (${DateTime.now().timeZoneName})'),
          if (e.location != null) Text(e.location!),
          if (e.description != null)
            Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Text(e.description!)),
          Text(l10n.confirmAttendance),
          SegmentedButton<String>(
              segments: [
                ButtonSegment(value: 'yes', label: Text(l10n.yes)),
                ButtonSegment(value: 'maybe', label: Text(l10n.maybe)),
                ButtonSegment(value: 'no', label: Text(l10n.no))
              ],
              emptySelectionAllowed: true,
              selected: {if (e.myRsvp != null) e.myRsvp!},
              onSelectionChanged: (v) async {
                await ref.read(contentRepositoryProvider).rsvp(e.uuid, v.first);
                await load();
              }),
          const Divider(height: 32),
          Text(l10n.attendeesCount(e.attendees.length),
              style: Theme.of(context).textTheme.titleMedium),
          ...e.attendees.map((a) => ListTile(
              title: Text(a.userName), trailing: Chip(label: Text(a.status))))
        ]));
  }
}

class EventFormScreen extends ConsumerStatefulWidget {
  const EventFormScreen({this.event, super.key});
  final FamilyEvent? event;
  @override
  ConsumerState<EventFormScreen> createState() => _EventFormState();
}

class _EventFormState extends ConsumerState<EventFormScreen> {
  final title = TextEditingController(),
      description = TextEditingController(),
      location = TextEditingController();
  DateTime? date;
  bool saving = false;
  @override
  void initState() {
    super.initState();
    final e = widget.event;
    title.text = e?.title ?? '';
    description.text = e?.description ?? '';
    location.text = e?.location ?? '';
    date = e?.eventDate;
  }

  Future<void> save() async {
    if (title.text.trim().isEmpty || date == null) return;
    setState(() => saving = true);
    try {
      final e = await ref.read(contentRepositoryProvider).saveEvent(
          ref.read(currentFamilyProvider)!.uuid,
          {
            'title': title.text.trim(),
            'description': description.text.trim(),
            'location': location.text.trim(),
            'event_date': date!.toUtc().toIso8601String()
          },
          uuid: widget.event?.uuid);
      if (mounted) context.go('/events/${e.uuid}');
    } catch (e) {
      if (mounted) {
        final l10n = AppLocalizations.of(context);
        final message = e is AppError ? e.message : l10n.requestFailed;
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(
            title: Text(
                widget.event == null ? l10n.createEvent : l10n.editEvent)),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          TextField(
              controller: title,
              decoration: InputDecoration(labelText: l10n.titleLabel)),
          TextField(
              controller: description,
              decoration: InputDecoration(labelText: l10n.description),
              maxLines: 4),
          TextField(
              controller: location,
              decoration: InputDecoration(labelText: l10n.location)),
          ListTile(
              title: Text(l10n.dateTimeLabel),
              subtitle: Text(
                  date == null ? l10n.pickTime : _date(date!)),
              onTap: () async {
                final d = await showDatePicker(
                    context: context,
                    firstDate: DateTime.now(),
                    lastDate: DateTime.now().add(const Duration(days: 3650)),
                    initialDate: date ?? DateTime.now());
                if (d == null || !context.mounted) return;
                final t = await showTimePicker(
                    context: context,
                    initialTime:
                        TimeOfDay.fromDateTime(date ?? DateTime.now()));
                if (t != null) {
                  setState(() => date = DateTime(
                      d.year, d.month, d.day, t.hour, t.minute));
                }
              }),
          FilledButton(
              onPressed: saving ? null : save,
              child: Text(saving ? l10n.saving : l10n.save))
        ]));
  }
}

class TimelineScreen extends ConsumerWidget {
  const TimelineScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(timelineProvider);
    final l10n = AppLocalizations.of(context);
    return value.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) =>
            _Retry(error: e, retry: () => ref.refresh(timelineProvider.future)),
        data: (items) => RefreshIndicator(
            onRefresh: () => ref.refresh(timelineProvider.future),
            child: ListView(padding: const EdgeInsets.all(16), children: [
              Text(l10n.familyTimeline,
                  style: Theme.of(context).textTheme.headlineSmall),
              if (items.isEmpty)
                _Empty(icon: Icons.history, text: l10n.noActivity)
              else
                ...items.map((i) => Card(
                    child: ListTile(
                        leading: const Icon(Icons.history),
                        title: Text(i.message),
                        subtitle: i.createdAt == null
                            ? null
                            : Text(_date(i.createdAt!)),
                        onTap: i.targetPath == null
                            ? null
                            : () => context.push(i.targetPath!))))
            ])));
  }
}

class _Retry extends StatelessWidget {
  const _Retry({required this.error, required this.retry});
  final Object error;
  final Future<void> Function() retry;
  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final cause = error;
    final message = cause is AppError ? cause.message : l10n.requestFailed;
    return Center(
        child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              const Icon(Icons.error_outline, size: 48),
              Text(message, textAlign: TextAlign.center),
              const SizedBox(height: 8),
              FilledButton(onPressed: retry, child: Text(l10n.retry))
            ])));
  }
}

class _Empty extends StatelessWidget {
  const _Empty({required this.icon, required this.text});
  final IconData icon;
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.all(40),
      child: Column(children: [
        Icon(icon, size: 56),
        const SizedBox(height: 8),
        Text(text, textAlign: TextAlign.center)
      ]));
}
