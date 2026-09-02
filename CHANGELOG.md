# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-09-02

### Changed

- **BREAKING: input is no longer HTML-escaped when it is read.** `get()` and
  `post()` returned `htmlspecialchars()` output, which corrupted every value
  that was then stored or sent anywhere other than an HTML page, and protected
  nothing outside HTML — escaped input is not safe for SQL, headers, shell
  commands or JavaScript. Escaping moved to `escapeHtml()`, to be called where
  the value is printed.

  **On upgrading:** anywhere you echo a value straight from this class, wrap it
  in `Form::escapeHtml()`. Nothing else in the library escapes for you.

- **BREAKING:** `get()` and `post()` are now `fetchGet()` and `fetchPost()`, and
  return `?string` with a caller-supplied default instead of `false`, so an
  absent field can be told apart from an empty one.
- **BREAKING:** `formControl($exclude_fields)` is replaced by
  `validatePost($required)`. The old method iterated whatever happened to be in
  `$_POST`, so it could not detect a required field that was never submitted —
  the case it existed to catch.
- **BREAKING:** `src/form.php` is now `src/Form.php`, so PSR-4 autoloading works
  on a case-sensitive filesystem.
- **BREAKING:** the class is `final` and cannot be instantiated.

### Fixed

- A field submitted as an array, such as `?id[]=1`, crashed with a `TypeError`
  from `trim()`. It is now reported as absent.
- A field holding `"0"` was treated as empty by `formControl()`, because the
  check was a falsy test rather than a comparison against `''`.
- `isPost()` and `isGet()` raised an undefined-index warning when
  `$_SERVER['REQUEST_METHOD']` was not set, and are now case-insensitive.
- `escapeHtml()` adds `ENT_SUBSTITUTE` and an explicit UTF-8 charset, so
  malformed input yields a replacement character instead of an empty string.

### Added

- `findMissingPost()`, which names the required fields the visitor left blank.
- `composer.json` with PSR-4 autoloading, published as `umityatarkalkmaz/form`.
- PHPUnit test suite and PHPStan (level max) analysis.
- GitHub Actions CI across PHP 8.2, 8.3, 8.4 and 8.5.

## [1.0.0]

- Initial release.
