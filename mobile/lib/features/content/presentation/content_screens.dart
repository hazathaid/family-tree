import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/models.dart';
import '../../../core/providers.dart';
import '../domain/content_models.dart';

String _message(Object error) => error is AppError
    ? error.message
    : 'Permintaan tidak berhasil. Silakan coba lagi.';
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
    return Scaffold(
        appBar: AppBar(title: const Text('Artikel keluarga')),
        floatingActionButton: _canManage(family)
            ? FloatingActionButton(
                tooltip: 'Tulis artikel',
                onPressed: () => context.push('/articles/new'),
                child: const Icon(Icons.edit))
            : null,
        body: RefreshIndicator(
            onRefresh: load,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              SearchBar(
                  controller: search,
                  hintText: 'Cari artikel',
                  onSubmitted: (_) {
                    page = 1;
                    load();
                  }),
              const SizedBox(height: 12),
              SegmentedButton<String>(
                  segments: const [
                    ButtonSegment(value: 'all', label: Text('Semua')),
                    ButtonSegment(value: 'published', label: Text('Terbit')),
                    ButtonSegment(value: 'draft', label: Text('Draf'))
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
                Text('Pilihan keluarga',
                    style: Theme.of(context).textTheme.titleMedium),
                ...featured.map((a) => _ArticleTile(article: a, featured: true))
              ],
              const SizedBox(height: 16),
              if (loading)
                const Center(child: CircularProgressIndicator())
              else if (error != null)
                _Retry(error: error!, retry: load)
              else if (articles.isEmpty)
                const _Empty(
                    icon: Icons.article_outlined,
                    text: 'Belum ada artikel yang dapat ditampilkan.')
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
    final c = TextEditingController(text: existing?.comment);
    final value = await showDialog<String>(
        context: context,
        builder: (_) => AlertDialog(
                title:
                    Text(existing == null ? 'Tulis komentar' : 'Edit komentar'),
                content: TextField(controller: c, minLines: 2, maxLines: 5),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Batal')),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, c.text.trim()),
                      child: const Text('Simpan'))
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
    if (error != null) {
      return Scaffold(
          appBar: AppBar(), body: _Retry(error: error!, retry: load));
    }
    if (a == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return Scaffold(
        appBar: AppBar(title: const Text('Detail artikel'), actions: [
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
                      const PopupMenuItem(value: 'edit', child: Text('Edit')),
                      if (a.status == 'draft')
                        const PopupMenuItem(
                            value: 'publish', child: Text('Terbitkan')),
                      PopupMenuItem(
                          value: 'feature',
                          child: Text(a.isFeatured
                              ? 'Hapus pilihan'
                              : 'Jadikan pilihan')),
                      const PopupMenuItem(value: 'delete', child: Text('Hapus'))
                    ])
        ]),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          Text(a.title, style: Theme.of(context).textTheme.headlineSmall),
          Text('${a.authorName} · ${a.category.name}'),
          const SizedBox(height: 16),
          if (a.featuredImageUrl != null)
            Image.network(a.featuredImageUrl!,
                semanticLabel: 'Gambar utama ${a.title}',
                errorBuilder: (_, __, ___) => const SizedBox.shrink()),
          const SizedBox(height: 12),
          SelectableText(_safeRichText(a.content)),
          const Divider(height: 32),
          Row(children: [
            IconButton(
                tooltip: a.isLikedByMe ? 'Batal suka' : 'Suka',
                icon: Icon(
                    a.isLikedByMe ? Icons.favorite : Icons.favorite_border),
                onPressed: () async {
                  await ref
                      .read(contentRepositoryProvider)
                      .likeArticle(a.uuid, !a.isLikedByMe);
                  await load();
                }),
            Text('${a.likesCount} suka'),
            const Spacer(),
            TextButton.icon(
                onPressed: () => comment(),
                icon: const Icon(Icons.comment),
                label: const Text('Komentar'))
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
                                  const PopupMenuItem(
                                      value: 'edit', child: Text('Edit')),
                                const PopupMenuItem(
                                    value: 'delete', child: Text('Hapus'))
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
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(_message(e))));
      }
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(
          title:
              Text(widget.article == null ? 'Tulis artikel' : 'Edit artikel')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        TextField(
            controller: title,
            decoration: const InputDecoration(labelText: 'Judul')),
        const SizedBox(height: 12),
        DropdownButtonFormField(
            initialValue: category,
            decoration: const InputDecoration(labelText: 'Kategori'),
            items: categories
                .map(
                    (c) => DropdownMenuItem(value: c.uuid, child: Text(c.name)))
                .toList(),
            onChanged: (v) => setState(() => category = v)),
        const SizedBox(height: 12),
        TextField(
            controller: excerpt,
            decoration: const InputDecoration(labelText: 'Ringkasan'),
            maxLines: 2),
        const SizedBox(height: 12),
        TextField(
            controller: content,
            decoration: const InputDecoration(
                labelText: 'Isi (teks dan paragraf aman)'),
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
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
                      content: Text('Gambar utama maksimal 10 MB.')));
                }
                return;
              }
              setState(() => featuredImage = image);
            },
            icon: const Icon(Icons.image_outlined),
            label: Text(featuredImage?.name ?? 'Pilih gambar utama')),
        const SizedBox(height: 8),
        FilledButton(
            onPressed: saving ? null : save,
            child: Text(saving ? 'Menyimpan…' : 'Simpan draf'))
      ]));
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
    final name = TextEditingController(text: value?.name),
        desc = TextEditingController(text: value?.description);
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text(value == null ? 'Album baru' : 'Edit album'),
                content: Column(mainAxisSize: MainAxisSize.min, children: [
                  TextField(
                      controller: name,
                      decoration: const InputDecoration(labelText: 'Nama')),
                  TextField(
                      controller: desc,
                      decoration: const InputDecoration(labelText: 'Deskripsi'))
                ]),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Batal')),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Simpan'))
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
    return Scaffold(
        appBar: AppBar(title: const Text('Album & galeri'), actions: [
          if (_canManage(family))
            IconButton(
                tooltip: 'Buat album',
                onPressed: () => editAlbum(),
                icon: const Icon(Icons.create_new_folder_outlined))
        ]),
        floatingActionButton: FloatingActionButton(
            tooltip: 'Unggah foto',
            onPressed: () => context.push('/photos/upload'),
            child: const Icon(Icons.add_a_photo)),
        body: error != null
            ? _Retry(error: error!, retry: load)
            : RefreshIndicator(
                onRefresh: load,
                child: ListView(padding: const EdgeInsets.all(16), children: [
                  DropdownButtonFormField<String?>(
                      initialValue: album,
                      decoration: const InputDecoration(labelText: 'Album'),
                      items: [
                        const DropdownMenuItem(
                            value: null, child: Text('Semua foto')),
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
                                deleteButtonTooltipMessage: 'Hapus album',
                                onDeleted: () async {
                                  final ok = await showDialog<bool>(
                                      context: context,
                                      builder: (_) => AlertDialog(
                                              title: Text(
                                                  'Hapus album ${a.name}?'),
                                              content: const Text(
                                                  'Album akan dihapus. Foto di dalamnya tetap disimpan tanpa album.'),
                                              actions: [
                                                TextButton(
                                                    onPressed: () =>
                                                        Navigator.pop(
                                                            context, false),
                                                    child: const Text('Batal')),
                                                FilledButton(
                                                    onPressed: () =>
                                                        Navigator.pop(
                                                            context, true),
                                                    child: const Text('Hapus'))
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
                    const _Empty(
                        icon: Icons.photo_library_outlined,
                        text: 'Belum ada foto.')
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
                                label: photos[i].caption ?? 'Foto keluarga',
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
    final value = await ImagePicker()
        .pickImage(source: source, imageQuality: 88, maxWidth: 2400);
    if (value == null) return;
    final size = await value.length();
    final ext = value.name.split('.').last.toLowerCase();
    if (size > 10 * 1024 * 1024 ||
        !{'jpg', 'jpeg', 'png', 'webp'}.contains(ext)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
            content:
                Text('Foto harus JPG, PNG, atau WebP dan maksimal 10 MB.')));
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
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(_message(e))));
      }
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: const Text('Unggah foto')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Wrap(spacing: 8, children: [
          OutlinedButton.icon(
              onPressed: () => pick(ImageSource.gallery),
              icon: const Icon(Icons.photo),
              label: const Text('Galeri')),
          OutlinedButton.icon(
              onPressed: () => pick(ImageSource.camera),
              icon: const Icon(Icons.camera_alt),
              label: const Text('Kamera'))
        ]),
        if (file != null) ...[
          const SizedBox(height: 12),
          Image.file(File(file!.path),
              height: 220, fit: BoxFit.contain, semanticLabel: 'Pratinjau foto')
        ],
        const SizedBox(height: 12),
        TextField(
            controller: caption,
            decoration: const InputDecoration(labelText: 'Keterangan'),
            maxLines: 3),
        DropdownButtonFormField<String?>(
            initialValue: albumUuid,
            decoration: const InputDecoration(labelText: 'Album'),
            items: [
              const DropdownMenuItem(value: null, child: Text('Tanpa album')),
              ...albums.map(
                  (a) => DropdownMenuItem(value: a.uuid, child: Text(a.name)))
            ],
            onChanged: (v) => setState(() => albumUuid = v)),
        ListTile(
            title: const Text('Tanggal pengambilan'),
            subtitle:
                Text(captured == null ? 'Tidak ditentukan' : _date(captured!)),
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
            child: const Text('Unggah'))
      ]));
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
                    title: const Text('Tag anggota'),
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
                          child: const Text('Batal')),
                      FilledButton(
                          onPressed: () => Navigator.pop(context, selected),
                          child: const Text('Simpan'))
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
    if (error != null) {
      return Scaffold(
          appBar: AppBar(), body: _Retry(error: error!, retry: load));
    }
    if (p == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return Scaffold(
        appBar: AppBar(title: const Text('Detail foto'), actions: [
          IconButton(
              tooltip: 'Tag anggota',
              onPressed: tags,
              icon: const Icon(Icons.sell_outlined)),
          IconButton(
              tooltip: 'Hapus foto',
              onPressed: () async {
                await ref.read(contentRepositoryProvider).deletePhoto(p.uuid);
                if (context.mounted) context.go('/photos');
              },
              icon: const Icon(Icons.delete_outline))
        ]),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          Image.network(p.url, semanticLabel: p.caption ?? 'Foto keluarga'),
          if (p.caption != null)
            Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Text(p.caption!)),
          Text(p.albumName == null ? 'Tanpa album' : 'Album: ${p.albumName}'),
          if (p.capturedAt != null) Text('Diambil: ${_date(p.capturedAt!)}'),
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
    return Scaffold(
        appBar: AppBar(title: const Text('Acara keluarga')),
        floatingActionButton: _canManage(family)
            ? FloatingActionButton(
                tooltip: 'Buat acara',
                onPressed: () => context.push('/events/new'),
                child: const Icon(Icons.add))
            : null,
        body: RefreshIndicator(
            onRefresh: load,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              SearchBar(
                  controller: search,
                  hintText: 'Cari acara',
                  onSubmitted: (_) => load()),
              const SizedBox(height: 12),
              SegmentedButton<String>(
                  segments: const [
                    ButtonSegment(value: 'upcoming', label: Text('Mendatang')),
                    ButtonSegment(value: 'all', label: Text('Semua'))
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
                const _Empty(icon: Icons.event_busy, text: 'Belum ada acara.')
              else
                ...items.map((e) => Card(
                    child: ListTile(
                        minTileHeight: 64,
                        title: Text(e.title),
                        subtitle: Text(
                            '${_date(e.eventDate)} ${e.location == null ? '' : '· ${e.location}'}\nWaktu perangkat: ${DateTime.now().timeZoneName}'),
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
    if (error != null) {
      return Scaffold(
          appBar: AppBar(), body: _Retry(error: error!, retry: load));
    }
    if (e == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return Scaffold(
        appBar: AppBar(title: const Text('Detail acara'), actions: [
          if (_canManage(family))
            IconButton(
                tooltip: 'Edit acara',
                onPressed: () =>
                    context.push('/events/${e.uuid}/edit', extra: e),
                icon: const Icon(Icons.edit)),
          if (_canManage(family))
            IconButton(
                tooltip: 'Hapus acara',
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
          const Text('Konfirmasi kehadiran'),
          SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'yes', label: Text('Ya')),
                ButtonSegment(value: 'maybe', label: Text('Mungkin')),
                ButtonSegment(value: 'no', label: Text('Tidak'))
              ],
              emptySelectionAllowed: true,
              selected: {if (e.myRsvp != null) e.myRsvp!},
              onSelectionChanged: (v) async {
                await ref.read(contentRepositoryProvider).rsvp(e.uuid, v.first);
                await load();
              }),
          const Divider(height: 32),
          Text('Peserta (${e.attendees.length})',
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
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(_message(e))));
      }
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(
          title: Text(widget.event == null ? 'Buat acara' : 'Edit acara')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        TextField(
            controller: title,
            decoration: const InputDecoration(labelText: 'Judul')),
        TextField(
            controller: description,
            decoration: const InputDecoration(labelText: 'Deskripsi'),
            maxLines: 4),
        TextField(
            controller: location,
            decoration: const InputDecoration(labelText: 'Lokasi')),
        ListTile(
            title: const Text('Tanggal & waktu'),
            subtitle: Text(date == null ? 'Pilih waktu' : _date(date!)),
            onTap: () async {
              final d = await showDatePicker(
                  context: context,
                  firstDate: DateTime.now(),
                  lastDate: DateTime.now().add(const Duration(days: 3650)),
                  initialDate: date ?? DateTime.now());
              if (d == null || !context.mounted) return;
              final t = await showTimePicker(
                  context: context,
                  initialTime: TimeOfDay.fromDateTime(date ?? DateTime.now()));
              if (t != null) {
                setState(() =>
                    date = DateTime(d.year, d.month, d.day, t.hour, t.minute));
              }
            }),
        FilledButton(
            onPressed: saving ? null : save,
            child: Text(saving ? 'Menyimpan…' : 'Simpan'))
      ]));
}

class TimelineScreen extends ConsumerWidget {
  const TimelineScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(timelineProvider);
    return value.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) =>
            _Retry(error: e, retry: () => ref.refresh(timelineProvider.future)),
        data: (items) => RefreshIndicator(
            onRefresh: () => ref.refresh(timelineProvider.future),
            child: ListView(padding: const EdgeInsets.all(16), children: [
              Text('Linimasa keluarga',
                  style: Theme.of(context).textTheme.headlineSmall),
              if (items.isEmpty)
                const _Empty(icon: Icons.history, text: 'Belum ada aktivitas.')
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
  Widget build(BuildContext context) => Center(
      child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.error_outline, size: 48),
            Text(_message(error), textAlign: TextAlign.center),
            const SizedBox(height: 8),
            FilledButton(onPressed: retry, child: const Text('Coba lagi'))
          ])));
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
