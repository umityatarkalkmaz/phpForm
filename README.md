# Form

Reads request input as trimmed strings, and escapes values on the way out.

## Requirements

PHP 8.2 or newer.

## Installation

```bash
composer require umityatarkalkmaz/form
```

## Usage

```php
use UmitYatarkalkmaz\Form;

if (Form::isPost()) {
    $data = Form::validatePost(['email', 'name']);

    if ($data === null) {
        $missing = Form::findMissingPost(['email', 'name']);
        // e.g. ['name']
    }

    $phone = Form::fetchPost('phone', '');
}
```

## Escaping happens on output, not on input

`fetchGet()` and `fetchPost()` return the value as submitted, trimmed of
surrounding whitespace. They do **not** run it through `htmlspecialchars()`.

That is deliberate. Escaping is a property of where a value is written, not of
where it came from:

- Escaping at input writes `Tom &amp; Jerry` into your database, and every later
  reader — an export, an email, a JSON API — carries the corruption.
- HTML escaping does nothing for SQL, shell commands, headers, or JavaScript
  contexts. Input that has been `htmlspecialchars()`-ed is not "safe"; it is
  only safe for one specific output context.

So escape where you print:

```php
$bio = Form::fetchPost('bio');          // 'Tom & Jerry <3'  — store this

echo Form::escapeHtml($bio);            // 'Tom &amp; Jerry &lt;3'  — print this
```

For SQL, use prepared statements with bound parameters. For JSON, use
`json_encode()`. `escapeHtml()` covers HTML text and quoted attributes; it is
not sufficient inside a `<script>` block, an unquoted attribute, or a URL.

## API

```php
Form::isPost(): bool
Form::isGet(): bool
```

False when the server set no request method at all. The comparison is exact:
HTTP methods are case-sensitive, so a `REQUEST_METHOD` of `post` is not a POST.

```php
Form::fetchGet(string $key, ?string $default = null): ?string
Form::fetchPost(string $key, ?string $default = null): ?string
```

Returns the trimmed value, or `$default` when the field is absent or was not
submitted as a plain string. An array submission such as `?id[]=1` yields
`$default` rather than a `TypeError`, and so does a value containing a NUL byte:
`avatar.png\0.php` is not the string it prints as, and everything written in C
downstream — filesystem calls, some database drivers, header output — reads it
as truncated.

Trimming covers ASCII whitespace plus the invisible characters a browser,
an autofill or a copy-paste delivers: U+00A0 no-break space, U+200B–U+200D
zero-width, and U+FEFF. `trim()` alone works on bytes, so a field holding one
no-break space used to satisfy a required check while looking empty.

```php
Form::validatePost(array $required): ?array
```

Returns exactly the required fields, trimmed, or `null` when any of them is
missing or empty. A field holding `"0"` counts as filled.

Check the result with `=== null`. Success with an empty `$required` is `[]`,
which is falsy, so `if (!$data)` treats a passing validation as a failure:

```php
$data = Form::validatePost($required);

if ($data === null) {      // not: if (!$data)
    // show the errors
}
```

```php
Form::findMissingPost(array $required): array
```

Names the required fields that were missing or empty, for the message you show
the visitor.

```php
Form::escapeHtml(string $value): string
```

`htmlspecialchars()` with `ENT_QUOTES | ENT_SUBSTITUTE` and an explicit UTF-8
charset.

## License

MIT. See [LICENSE](LICENSE).
