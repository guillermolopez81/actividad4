<?php

declare(strict_types=1);

final class Input
{
    public static function text(mixed $value, int $limit): ?string
    {
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
            return null;
        }

        $value = trim($value);
        return mb_strlen($value, 'UTF-8') <= $limit ? $value : null;
    }

    public static function integer(mixed $value, int $min = 1, int $max = 2147483647): ?int
    {
        if (!is_int($value) && (!is_string($value) || !preg_match('/\A[0-9]+\z/', $value))) {
            return null;
        }

        $number = (int)$value;
        return $number >= $min && $number <= $max ? $number : null;
    }
}
