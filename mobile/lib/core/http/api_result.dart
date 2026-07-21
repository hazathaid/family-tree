sealed class ApiResult<T> {
  const ApiResult();

  R when<R>(
          {required R Function(T data) success,
          required R Function(Object error) failure}) =>
      switch (this) {
        ApiSuccess<T>(:final data) => success(data),
        ApiFailure<T>(:final error) => failure(error),
      };
}

final class ApiSuccess<T> extends ApiResult<T> {
  const ApiSuccess(this.data);
  final T data;
}

final class ApiFailure<T> extends ApiResult<T> {
  const ApiFailure(this.error);
  final Object error;
}
