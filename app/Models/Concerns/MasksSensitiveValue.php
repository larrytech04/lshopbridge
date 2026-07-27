<?php

namespace App\Models\Concerns;

trait MasksSensitiveValue
{
    public static function mask(?string $value): string
    {
        $value = (string) $value;
        $len = strlen($value);

        if ($len <= 6) {
            return str_repeat('•', max($len, 1));
        }

        return substr($value, 0, 3).str_repeat('•', $len - 6).substr($value, -3);
    }
}
