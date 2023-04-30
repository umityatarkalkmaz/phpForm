<?php
namespace UmitYatarkalkmaz;
class Form
{
    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    public static function get($key): bool|string
    {
        return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key]), ENT_QUOTES) : false;
    }

    public static function formControl(array $exclude_fields = []): bool|array
    {
        $data = [];
        $error = false;
        foreach ($_POST as $key => $val) {
            if (!in_array($key, $exclude_fields) && !self::post($key)) {
                $error = true;
            } else {
                $data[$key] = self::post($key);
            }
        }
        if ($error) {
            return false;
        }
        return $data;
    }

    public static function post($key): bool|string
    {
        return isset($_POST[$key]) ? htmlspecialchars(trim($_POST[$key]), ENT_QUOTES) : false;
    }
}
