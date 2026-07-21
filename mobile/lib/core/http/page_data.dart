class PageData<T> {
  const PageData(
      {required this.items,
      required this.currentPage,
      required this.lastPage,
      required this.total});

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int? total;
  bool get hasMore => currentPage < lastPage;

  factory PageData.fromJson(
      Map<String, dynamic> json, T Function(Map<String, dynamic>) decode) {
    final rawItems = json['data'] as List<dynamic>? ?? const [];
    return PageData(
      items: rawItems
          .map((item) => decode(item as Map<String, dynamic>))
          .toList(growable: false),
      currentPage: json['current_page'] as int? ?? json['page'] as int? ?? 1,
      lastPage: json['last_page'] as int? ?? 1,
      total: json['total'] as int?,
    );
  }
}
