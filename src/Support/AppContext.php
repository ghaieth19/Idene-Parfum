<?php

namespace App\Support;

use PDO;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class AppContext
{
    private const ADMIN_ROLES = ['ADMIN', 'DIRECTEUR', 'MANAGER'];
    private ?PDO $pdo = null;

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function db(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $host = $this->env('DB_HOST', '127.0.0.1');
        $port = $this->env('DB_PORT', '3306');
        $name = $this->env('DB_NAME', 'idene_parfum');
        $charset = $this->env('DB_CHARSET', 'utf8mb4');
        $user = $this->env('DB_USER', 'root');
        $password = $this->env('DB_PASSWORD', '');

        $this->pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset),
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $this->pdo;
    }

    private function env(string $key, ?string $default = null): ?string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public function session(): SessionInterface
    {
        $session = $this->requestStack->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        return $session;
    }

    public function currentUserId(): ?int
    {
        $userId = $this->session()->get('user_id');

        return is_numeric($userId) ? (int) $userId : null;
    }

    public function currentRole(): ?string
    {
        $role = $this->session()->get('role_name');

        return is_string($role) && $role !== '' ? $role : null;
    }

    public function isAdminSession(): bool
    {
        return in_array((string) $this->currentRole(), self::ADMIN_ROLES, true);
    }

    public function loginUser(array $user): void
    {
        $session = $this->session();
        $session->set('user_id', (int) $user['id']);
        $session->set('role_name', (string) ($user['role_name'] ?? 'CLIENT'));
    }

    public function logoutUser(): void
    {
        $this->session()->invalidate();
    }

    public function fetchUserWithRoleById(int $userId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.perfume_shop_name,
                u.phone,
                u.location,
                u.email,
                u.password_hash,
                u.matrice,
                u.is_active,
                COALESCE((
                    SELECT r2.role_name
                    FROM user_roles ur2
                    INNER JOIN roles r2 ON r2.id = ur2.role_id
                    WHERE ur2.user_id = u.id
                    ORDER BY FIELD(r2.role_name, "ADMIN", "DIRECTEUR", "MANAGER", "EMPLOYE", "CLIENT")
                    LIMIT 1
                ), "CLIENT") AS role_name
             FROM users u
             WHERE u.id = :id AND u.is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function fetchUserWithRoleByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.perfume_shop_name,
                u.phone,
                u.location,
                u.email,
                u.password_hash,
                u.matrice,
                u.is_active,
                COALESCE((
                    SELECT r2.role_name
                    FROM user_roles ur2
                    INNER JOIN roles r2 ON r2.id = ur2.role_id
                    WHERE ur2.user_id = u.id
                    ORDER BY FIELD(r2.role_name, "ADMIN", "DIRECTEUR", "MANAGER", "EMPLOYE", "CLIENT")
                    LIMIT 1
                ), "CLIENT") AS role_name
             FROM users u
             WHERE u.email = :email AND u.is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
