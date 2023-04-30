# Basic Form Control
This class to control and make safe GET and POST.

```php
Form::isPost();
```
`isPost()` Check request method is a POST or not
```php
Form::isGet();
```
`isGet()` Check request method is a GET or not

```php
Form::get('usename');
```
`get($key)` This method making data safe and returns safe GET data.
```php
Form::post('usename');
```
`post($key)` This method making data safe and returns safe POST data.
```php
Form::formControl('phone');
```
`formControl($exclude_fields=[])` This method making all POST data safe and returns safe POST data.

Usage Example

```php
require 'path/to/form.php';
use UmitYatarkalkmaz\Form;

if(Form::isPost() && Form::post('submit') == 'contact'){
    $data = Form::formControl('phone');
    $email = $data['email'];
    $phone = $data['phone'] ?? false;
}
```