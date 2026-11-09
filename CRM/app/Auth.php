<?php

declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $query = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role FROM users WHERE email = ?'
        );
        $query->execute([$email]);
        $user = $query->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('No se pudo regenerar la sesión.');
        }

        $_SESSION = [];
        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        return true;
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            Security::redirect('login');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'],
        ]);
        session_destroy();
    }
}
