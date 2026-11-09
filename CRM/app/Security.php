<?php

declare(strict_types=1);

final class Security
{
    public static function token(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function verify(): void
    {
        $submittedToken = $_POST['csrf'] ?? '';

        if (!is_string($submittedToken) || !hash_equals(self::token(), $submittedToken)) {
            http_response_code(403);
            exit('Solicitud rechazada: token CSRF no válido.');
        }
    }

    public static function e(?string $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function redirect(string $page, array $args = []): never
    {
        $query = http_build_query(['page' => $page] + $args);
        header('Location: index.php?' . $query, true, 303);
        exit;
    }
}
