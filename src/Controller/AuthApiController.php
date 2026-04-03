<?php

namespace App\Controller;

use App\Support\AppContext;
use App\Support\ApiResponse;
use App\Support\InputValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class AuthApiController
{
    private const FACE_MATRIX_LENGTH = 1024;
    private const FACE_MATCH_THRESHOLD = 0.78;
    private const FACE_MATCH_MIN_COSINE = 0.72;
    private const FACE_MATCH_MIN_COSINE_GAP = 0.015;
    private const FACE_MATCH_MIN_DISTANCE_GAP = 0.04;
    private const FACE_PROFILE_LABEL_MAX_LENGTH = 120;
    private const SESSION_PENDING_FACE_MATRIX = 'face_auth.pending.matrix';
    private const SESSION_FIRST_LOGIN_GUIDE = 'auth.first_login_guide';
    private const PASSWORD_RESET_TTL_SECONDS = 3600;

    public function __construct(
        private readonly AppContext $app,
        private readonly InputValidator $validator,
        private readonly MailerInterface $mailer,
    ) {
    }

    private function publicUser(array $user): array
    {
        unset($user['password_hash'], $user['is_active'], $user['matrice']);

        return $user;
    }

    private function normalizeFaceMatrix(mixed $value): array
    {
        if (!is_array($value) || count($value) !== self::FACE_MATRIX_LENGTH) {
            throw new \RuntimeException('Matrice de visage invalide.');
        }

        $matrix = [];
        foreach ($value as $item) {
            if (!is_numeric($item)) {
                throw new \RuntimeException('Matrice de visage invalide.');
            }
            $number = (float) $item;
            if ($number < 0 || $number > 1) {
                throw new \RuntimeException('Matrice de visage hors limites.');
            }
            $matrix[] = round($number, 6);
        }

        return $matrix;
    }

    private function normalizeFaceMatrices(mixed $value): array
    {
        if (!is_array($value)) {
            throw new \RuntimeException('Matrices de visage invalides.');
        }

        $matrices = [];
        $seen = [];

        foreach ($value as $item) {
            $matrix = $this->normalizeFaceMatrix($item);
            $key = json_encode($matrix, JSON_THROW_ON_ERROR);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $matrices[] = $matrix;

            if (count($matrices) >= 8) {
                break;
            }
        }

        if ($matrices === []) {
            throw new \RuntimeException('Matrices de visage invalides.');
        }

        return $matrices;
    }

    private function putPendingFaceMatrix(array $matrix): void
    {
        $this->app->session()->set(self::SESSION_PENDING_FACE_MATRIX, json_encode($matrix, JSON_THROW_ON_ERROR));
    }

    private function popPendingFaceMatrix(): ?array
    {
        $session = $this->app->session();
        $raw = (string) $session->get(self::SESSION_PENDING_FACE_MATRIX, '');
        $session->remove(self::SESSION_PENDING_FACE_MATRIX);

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }

    private function markFirstClientLoginGuide(): void
    {
        $this->app->session()->set(self::SESSION_FIRST_LOGIN_GUIDE, true);
    }

    private function consumeFirstClientLoginGuide(): bool
    {
        $session = $this->app->session();
        $shouldShow = (bool) $session->get(self::SESSION_FIRST_LOGIN_GUIDE, false);
        $session->remove(self::SESSION_FIRST_LOGIN_GUIDE);

        return $shouldShow;
    }

    private function saveFaceProfile(int $userId, array $matrix): void
    {
        $stmt = $this->app->db()->prepare(
            'UPDATE users
             SET matrice = :matrice
             WHERE id = :user_id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'matrice' => json_encode($matrix, JSON_THROW_ON_ERROR),
        ]);
    }

    private function ensureFaceAuthProfilesTable(): void
    {
        $db = $this->app->db();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS face_auth_profiles (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                profile_label VARCHAR(' . self::FACE_PROFILE_LABEL_MAX_LENGTH . ') NULL,
                face_matrix_json LONGTEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_face_auth_profiles_user_id (user_id),
                CONSTRAINT fk_face_auth_profiles_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $schema = (string) $db->query('SELECT DATABASE()')->fetchColumn();
        if ($schema === '') {
            return;
        }

        $columnCheck = $db->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = "face_auth_profiles"
               AND COLUMN_NAME = "profile_label"'
        );
        $columnCheck->execute(['schema' => $schema]);
        if ((int) $columnCheck->fetchColumn() === 0) {
            $db->exec(
                'ALTER TABLE face_auth_profiles
                 ADD COLUMN profile_label VARCHAR(' . self::FACE_PROFILE_LABEL_MAX_LENGTH . ') NULL AFTER user_id'
            );
        }

        $indexCheck = $db->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = "face_auth_profiles"
               AND INDEX_NAME = "idx_face_auth_profiles_user_id"'
        );
        $indexCheck->execute(['schema' => $schema]);
        if ((int) $indexCheck->fetchColumn() === 0) {
            $db->exec('ALTER TABLE face_auth_profiles ADD INDEX idx_face_auth_profiles_user_id (user_id)');
        }

        $legacyUniqueCheck = $db->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = "face_auth_profiles"
               AND INDEX_NAME = "uniq_face_auth_user_id"'
        );
        $legacyUniqueCheck->execute(['schema' => $schema]);
        if ((int) $legacyUniqueCheck->fetchColumn() > 0) {
            $db->exec('ALTER TABLE face_auth_profiles DROP INDEX uniq_face_auth_user_id');
        }
    }

    private function syncLegacyMatrixFromFaceProfiles(int $userId): void
    {
        $this->ensureFaceAuthProfilesTable();

        $db = $this->app->db();
        $stmt = $db->prepare(
            'SELECT face_matrix_json
             FROM face_auth_profiles
             WHERE user_id = :user_id
             ORDER BY id ASC
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $matrixJson = $stmt->fetchColumn();

        $update = $db->prepare(
            'UPDATE users
             SET matrice = :matrice
             WHERE id = :user_id'
        );
        $update->execute([
            'matrice' => $matrixJson !== false ? (string) $matrixJson : null,
            'user_id' => $userId,
        ]);
    }

    private function migrateLegacyMatrixToFaceProfiles(int $userId): void
    {
        $this->ensureFaceAuthProfilesTable();

        $db = $this->app->db();
        $countStmt = $db->prepare(
            'SELECT COUNT(*)
             FROM face_auth_profiles
             WHERE user_id = :user_id'
        );
        $countStmt->execute(['user_id' => $userId]);
        if ((int) $countStmt->fetchColumn() > 0) {
            return;
        }

        $legacyStmt = $db->prepare(
            'SELECT matrice
             FROM users
             WHERE id = :user_id
               AND matrice IS NOT NULL
               AND matrice <> ""
             LIMIT 1'
        );
        $legacyStmt->execute(['user_id' => $userId]);
        $legacyMatrix = $legacyStmt->fetchColumn();
        if ($legacyMatrix === false || $legacyMatrix === null || $legacyMatrix === '') {
            return;
        }

        $insert = $db->prepare(
            'INSERT INTO face_auth_profiles (user_id, profile_label, face_matrix_json)
             VALUES (:user_id, :profile_label, :face_matrix_json)'
        );
        $insert->execute([
            'user_id' => $userId,
            'profile_label' => 'Profil principal',
            'face_matrix_json' => (string) $legacyMatrix,
        ]);
    }

    private function normalizeFaceVectorForComparison(array $matrix): array
    {
        $count = count($matrix);
        if ($count === 0) {
            return [];
        }

        $mean = array_sum($matrix) / $count;
        $centered = [];
        $sumSquares = 0.0;

        foreach ($matrix as $value) {
            $normalized = ((float) $value) - $mean;
            $centered[] = $normalized;
            $sumSquares += $normalized * $normalized;
        }

        $norm = sqrt($sumSquares);
        if ($norm < 0.000001) {
            return $centered;
        }

        foreach ($centered as $index => $value) {
            $centered[$index] = $value / $norm;
        }

        return $centered;
    }

    private function cosineSimilarity(array $left, array $right): float
    {
        $limit = min(count($left), count($right));
        if ($limit === 0) {
            return 0.0;
        }

        $dot = 0.0;
        for ($index = 0; $index < $limit; $index += 1) {
            $dot += ((float) $left[$index]) * ((float) $right[$index]);
        }

        return $dot;
    }

    private function buildPublicUrl(Request $request, string $path, array $query = []): string
    {
        $url = rtrim($request->getSchemeAndHttpHost(), '/') . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    private function hashResetToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function hashResetCode(string $code): string
    {
        return hash('sha256', $code);
    }

    private function issuePasswordResetToken(int $userId): array
    {
        $token = bin2hex(random_bytes(32));
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $db = $this->app->db();
        $expiresAt = (new \DateTimeImmutable('+' . self::PASSWORD_RESET_TTL_SECONDS . ' seconds'))
            ->format('Y-m-d H:i:s');

        $db->prepare(
            'UPDATE password_reset_tokens
             SET used_at = NOW()
             WHERE user_id = :user_id AND used_at IS NULL'
        )->execute([
            'user_id' => $userId,
        ]);

        $db->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, reset_code_hash, expires_at, code_expires_at)
             VALUES (:user_id, :token_hash, :reset_code_hash, :expires_at, :code_expires_at)'
        )->execute([
            'user_id' => $userId,
            'token_hash' => $this->hashResetToken($token),
            'reset_code_hash' => $this->hashResetCode($code),
            'expires_at' => $expiresAt,
            'code_expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'code' => $code,
        ];
    }

    private function findResetTokenRecord(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $stmt = $this->app->db()->prepare(
            'SELECT prt.id, prt.user_id, u.email, u.first_name, u.last_name
             FROM password_reset_tokens prt
             INNER JOIN users u ON u.id = prt.user_id
             WHERE prt.token_hash = :token_hash
               AND prt.used_at IS NULL
               AND prt.expires_at > NOW()
               AND u.is_active = 1
             LIMIT 1'
        );
        $stmt->execute([
            'token_hash' => $this->hashResetToken($token),
        ]);

        $record = $stmt->fetch();

        return $record ?: null;
    }

    private function findResetCodeRecord(string $email, string $code): ?array
    {
        if ($email === '' || $code === '') {
            return null;
        }

        $stmt = $this->app->db()->prepare(
            'SELECT prt.id, prt.user_id, u.email
             FROM password_reset_tokens prt
             INNER JOIN users u ON u.id = prt.user_id
             WHERE u.email = :email
               AND prt.reset_code_hash = :reset_code_hash
               AND prt.used_at IS NULL
               AND prt.code_expires_at > NOW()
               AND u.is_active = 1
             ORDER BY prt.id DESC
             LIMIT 1'
        );
        $stmt->execute([
            'email' => $email,
            'reset_code_hash' => $this->hashResetCode($code),
        ]);

        $record = $stmt->fetch();

        return $record ?: null;
    }

    private function consumeResetToken(int $tokenId, int $userId, string $password): void
    {
        $db = $this->app->db();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $db->beginTransaction();

        try {
            $updateUser = $db->prepare(
                'UPDATE users
                 SET password_hash = :password_hash
                 WHERE id = :id'
            );
            $updateUser->execute([
                'password_hash' => $passwordHash,
                'id' => $userId,
            ]);

            $markUsed = $db->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE id = :id'
            );
            $markUsed->execute([
                'id' => $tokenId,
            ]);

            $invalidateOthers = $db->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE user_id = :user_id AND used_at IS NULL'
            );
            $invalidateOthers->execute([
                'user_id' => $userId,
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    private function findUserByFaceMatrix(array $probeMatrix): ?array
    {
        return $this->findUserByFaceMatrices([$probeMatrix]);
    }

    private function findUserByFaceMatrices(array $probeMatrices): ?array
    {
        $this->ensureFaceAuthProfilesTable();
        $normalizedProbeMatrices = [];
        foreach ($probeMatrices as $probeMatrix) {
            if (!is_array($probeMatrix) || count($probeMatrix) !== self::FACE_MATRIX_LENGTH) {
                continue;
            }

            $normalizedProbeMatrices[] = $this->normalizeFaceVectorForComparison($probeMatrix);
        }

        if ($normalizedProbeMatrices === []) {
            return null;
        }

        $stmt = $this->app->db()->query(
            'SELECT *
             FROM (
                SELECT
                    u.id,
                    f.face_matrix_json AS matrice,
                    u.first_name,
                    u.last_name,
                    u.perfume_shop_name,
                    u.phone,
                    u.location,
                    u.email,
                    u.password_hash,
                    u.is_active,
                    COALESCE((
                        SELECT r2.role_name
                        FROM user_roles ur2
                        INNER JOIN roles r2 ON r2.id = ur2.role_id
                        WHERE ur2.user_id = u.id
                        ORDER BY FIELD(r2.role_name, "ADMIN", "DIRECTEUR", "MANAGER", "EMPLOYE", "CLIENT")
                        LIMIT 1
                    ), "CLIENT") AS role_name
                 FROM face_auth_profiles f
                 INNER JOIN users u ON u.id = f.user_id
                 WHERE u.is_active = 1

                 UNION ALL

                 SELECT
                    u.id,
                    u.matrice,
                    u.first_name,
                    u.last_name,
                    u.perfume_shop_name,
                    u.phone,
                    u.location,
                    u.email,
                    u.password_hash,
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
                 WHERE u.is_active = 1
                   AND u.matrice IS NOT NULL
                   AND NOT EXISTS (
                       SELECT 1
                       FROM face_auth_profiles f2
                       WHERE f2.user_id = u.id
                   )
             ) face_candidates
             WHERE matrice IS NOT NULL
               AND matrice <> ""'
        );

        $bestMatchByUser = [];

        foreach ($stmt->fetchAll() as $row) {
            $stored = json_decode((string) $row['matrice'], true);
            if (!is_array($stored) || count($stored) !== self::FACE_MATRIX_LENGTH) {
                continue;
            }

            $normalizedStoredMatrix = $this->normalizeFaceVectorForComparison($stored);
            $userId = (int) ($row['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            foreach ($normalizedProbeMatrices as $normalizedProbeMatrix) {
                $sum = 0.0;
                for ($index = 0; $index < self::FACE_MATRIX_LENGTH; $index += 1) {
                    $delta = $normalizedStoredMatrix[$index] - $normalizedProbeMatrix[$index];
                    $sum += $delta * $delta;
                }

                $distance = sqrt($sum);
                if ($distance > self::FACE_MATCH_THRESHOLD) {
                    continue;
                }

                $cosineSimilarity = $this->cosineSimilarity($normalizedStoredMatrix, $normalizedProbeMatrix);
                if ($cosineSimilarity < self::FACE_MATCH_MIN_COSINE) {
                    continue;
                }

                $candidate = [
                    'user' => $row,
                    'distance' => $distance,
                    'cosine' => $cosineSimilarity,
                ];

                if (
                    !isset($bestMatchByUser[$userId])
                    || $distance < $bestMatchByUser[$userId]['distance']
                    || (
                        abs($distance - $bestMatchByUser[$userId]['distance']) < 0.000001
                        && $cosineSimilarity > $bestMatchByUser[$userId]['cosine']
                    )
                ) {
                    $bestMatchByUser[$userId] = $candidate;
                }
            }
        }

        if ($bestMatchByUser === []) {
            return null;
        }

        $matches = array_values($bestMatchByUser);
        usort(
            $matches,
            static function (array $left, array $right): int {
                if (abs($left['distance'] - $right['distance']) > 0.000001) {
                    return $left['distance'] <=> $right['distance'];
                }

                return $right['cosine'] <=> $left['cosine'];
            }
        );

        $bestMatch = $matches[0];
        $secondBestMatch = $matches[1] ?? null;

        if ($secondBestMatch !== null) {
            $cosineGap = $bestMatch['cosine'] - $secondBestMatch['cosine'];
            $distanceGap = $secondBestMatch['distance'] - $bestMatch['distance'];

            if (
                $cosineGap < self::FACE_MATCH_MIN_COSINE_GAP
                && $distanceGap < self::FACE_MATCH_MIN_DISTANCE_GAP
            ) {
                return ['_ambiguous' => true];
            }
        }

        $bestUser = $bestMatch['user'];
        unset($bestUser['matrice']);

        return $bestUser;
    }

    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return new JsonResponse(['authenticated' => false], 401);
        }

        $user = $this->app->fetchUserWithRoleById($userId);
        if (!$user) {
            $this->app->logoutUser();

            return new JsonResponse(['authenticated' => false], 401);
        }

        return new JsonResponse([
            'authenticated' => true,
            'user' => $this->publicUser($user),
            'role_name' => $user['role_name'],
        ]);
    }

    #[Route('/api/auth/signup', name: 'api_auth_signup', methods: ['POST'])]
    public function signup(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];

        $data = [
            'first_name' => trim((string) ($payload['first_name'] ?? '')),
            'last_name' => trim((string) ($payload['last_name'] ?? '')),
            'perfume_shop_name' => trim((string) ($payload['shop_name'] ?? '')),
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'location' => trim((string) ($payload['location'] ?? '')),
            'email' => mb_strtolower(trim((string) ($payload['email'] ?? ''))),
            'password' => (string) ($payload['password'] ?? ''),
        ];

        $errors = array_filter([
            $this->validator->name($data['first_name'], 'Prenom'),
            $this->validator->name($data['last_name'], 'Nom'),
            $data['perfume_shop_name'] === '' ? 'Nom de la parfumerie obligatoire.' : null,
            mb_strlen($data['perfume_shop_name']) > 150 ? 'Nom de la parfumerie trop long.' : null,
            $this->validator->phone($data['phone']),
            $data['location'] === '' ? 'Localisation obligatoire.' : null,
            mb_strlen($data['location']) > 255 ? 'Localisation trop longue.' : null,
            $this->validator->email($data['email']),
            $this->validator->password($data['password']),
        ]);

        if ($errors !== []) {
            return ApiResponse::validation($errors);
        }

        $db = $this->app->db();
        $check = $db->prepare('SELECT id FROM users WHERE email = :email OR phone = :phone LIMIT 1');
        $check->execute([
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);
        if ($check->fetch()) {
            return ApiResponse::error('Cet email ou telephone existe deja.', 409);
        }

        $stmt = $db->prepare(
            'INSERT INTO users (first_name, last_name, perfume_shop_name, phone, location, email, password_hash)
             VALUES (:first_name, :last_name, :shop, :phone, :location, :email, :password_hash)'
        );
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'shop' => $data['perfume_shop_name'],
            'phone' => $data['phone'],
            'location' => $data['location'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        $userId = (int) $db->lastInsertId();
        $roleStmt = $db->prepare('SELECT id FROM roles WHERE role_name = :name LIMIT 1');
        $roleStmt->execute(['name' => 'CLIENT']);
        $roleId = $roleStmt->fetchColumn();
        if ($roleId) {
            $assignStmt = $db->prepare(
                'INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)'
            );
            $assignStmt->execute([
                'user_id' => $userId,
                'role_id' => (int) $roleId,
            ]);
        }

        $pendingMatrix = $this->popPendingFaceMatrix();
        if ($pendingMatrix !== null) {
            $this->saveFaceProfile($userId, $pendingMatrix);
            $this->migrateLegacyMatrixToFaceProfiles($userId);
            $this->syncLegacyMatrixFromFaceProfiles($userId);
        }

        $this->markFirstClientLoginGuide();

        return ApiResponse::ok([
            'ok' => true,
            'user_id' => $userId,
            'role_name' => 'CLIENT',
            'biometric_available' => $pendingMatrix !== null,
            'redirect' => '/auth',
        ], 201);
    }

    #[Route('/api/auth/signin', name: 'api_auth_signin', methods: ['POST'])]
    public function signin(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        $errors = array_filter([
            $this->validator->email($email),
            $password === '' ? 'Mot de passe obligatoire.' : null,
        ]);
        if ($errors !== []) {
            return ApiResponse::validation($errors);
        }

        try {
            $user = $this->app->fetchUserWithRoleByEmail($email);
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                'La base de donnees ne repond pas. Verifiez que MySQL est demarre et accessible sur 127.0.0.1:3306.',
                503
            );
        }

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return ApiResponse::error('Identifiants invalides.', 401);
        }

        $this->app->loginUser($user);
        $isAdmin = in_array((string) $user['role_name'], ['ADMIN', 'DIRECTEUR', 'MANAGER'], true);
        $redirect = $isAdmin ? '/admin' : '/dashboard';

        if (!$isAdmin && $this->consumeFirstClientLoginGuide()) {
            $redirect = '/dashboard?onboarding=welcome&lang=ar';
        }

        return ApiResponse::ok([
            'ok' => true,
            'user_id' => (int) $user['id'],
            'role_name' => $user['role_name'],
            'redirect' => $redirect,
        ]);
    }

    #[Route('/api/auth/face/pending', name: 'api_auth_face_pending', methods: ['POST'])]
    public function facePending(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];

        try {
            $matrix = $this->normalizeFaceMatrix($payload['matrix'] ?? null);
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        $this->putPendingFaceMatrix($matrix);

        return ApiResponse::ok([
            'ok' => true,
            'message' => 'Visage capture. Vous pouvez maintenant creer le compte.',
        ]);
    }

    #[Route('/api/auth/face/login', name: 'api_auth_face_login', methods: ['POST'])]
    public function faceLogin(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];

        try {
            $probeMatrices = [];

            if (array_key_exists('matrices', $payload) && $payload['matrices'] !== null) {
                $probeMatrices = $this->normalizeFaceMatrices($payload['matrices']);
            }

            if (array_key_exists('matrix', $payload)) {
                $primaryMatrix = $this->normalizeFaceMatrix($payload['matrix']);
                $primaryKey = json_encode($primaryMatrix, JSON_THROW_ON_ERROR);
                $probeMatrices = array_values(array_filter(
                    $probeMatrices,
                    static fn (array $matrix): bool => json_encode($matrix, JSON_THROW_ON_ERROR) !== $primaryKey
                ));
                array_unshift($probeMatrices, $primaryMatrix);
            }

            if ($probeMatrices === []) {
                throw new \RuntimeException('Matrice de visage invalide.');
            }
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        $user = $this->findUserByFaceMatrices($probeMatrices);
        if (!$user) {
            return ApiResponse::error('Aucun compte ne correspond a ce visage.', 404);
        }
        if (($user['_ambiguous'] ?? false) === true) {
            return ApiResponse::error('Plusieurs comptes ressemblent a cette capture. Reprenez le scan bien en face ou reconfigurez le visage du compte.', 409);
        }

        $this->app->loginUser($user);
        $isAdmin = in_array((string) $user['role_name'], ['ADMIN', 'DIRECTEUR', 'MANAGER'], true);
        $redirect = $isAdmin ? '/admin' : '/dashboard';

        if (!$isAdmin && $this->consumeFirstClientLoginGuide()) {
            $redirect = '/dashboard?onboarding=welcome&lang=ar';
        }

        return ApiResponse::ok([
            'ok' => true,
            'message' => 'Connexion par visage validee.',
            'redirect' => $redirect,
        ]);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $this->app->logoutUser();

        return ApiResponse::ok([
            'ok' => true,
            'redirect' => '/auth',
        ]);
    }

    #[Route('/api/auth/forgot', name: 'api_auth_forgot', methods: ['POST'])]
    public function forgot(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));

        $errors = array_filter([
            $this->validator->email($email),
        ]);
        if ($errors !== []) {
            return ApiResponse::validation($errors);
        }

        $stmt = $this->app->db()->prepare(
            'SELECT id, first_name, last_name, email
             FROM users
             WHERE email = :email AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([
            'email' => $email,
        ]);

        $user = $stmt->fetch();

        if (!$user) {
            return ApiResponse::error('Aucun compte correspondant.', 404);
        }

        $resetData = $this->issuePasswordResetToken((int) $user['id']);
        $resetUrl = $this->buildPublicUrl($request, '/reset-password', ['token' => $resetData['token']]);
        $verifyCodeUrl = $this->buildPublicUrl($request, '/verify-reset-code', ['email' => (string) $user['email']]);
        $fullName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
        $recipientName = $fullName !== '' ? $fullName : 'Client IDENE';
        $emailMessage = (new Email())
            ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'ghaiethbouamor23@gmail.com')
            ->to((string) $user['email'])
            ->subject('Societe IDENE | Code de reinitialisation')
            ->text(
                "Bonjour {$recipientName},\n\n"
                . "Nous avons recu une demande de reinitialisation du mot de passe de votre compte Societe IDENE.\n\n"
                . "Votre code de verification est : {$resetData['code']}\n\n"
                . "Saisissez ce code sur la page suivante : {$verifyCodeUrl}\n\n"
                . "Si vous preferez un acces direct, vous pouvez aussi utiliser ce lien : {$resetUrl}\n\n"
                . "Ce code expire dans 60 minutes.\n"
                . "Si vous n etes pas a l origine de cette demande, ignorez simplement cet email.\n\n"
                . "Cordialement,\n"
                . "Societe IDENE"
            )
            ->html(
                '<html><body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,sans-serif;line-height:1.7;color:#172033;">'
                . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fb;padding:32px 16px;">'
                . '<tr><td align="center">'
                . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;">'
                . '<tr><td style="background:#111827;padding:28px 32px;color:#ffffff;">'
                . '<h1 style="margin:0;font-size:28px;line-height:1.2;">Societe IDENE</h1>'
                . '<p style="margin:8px 0 0;font-size:14px;opacity:.88;">Verification securisee pour la reinitialisation du mot de passe</p>'
                . '</td></tr>'
                . '<tr><td style="padding:32px;">'
                . '<p>Bonjour ' . htmlspecialchars($recipientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ',</p>'
                . '<p>Nous avons recu une demande de reinitialisation du mot de passe de votre compte Societe IDENE.</p>'
                . '<p>Utilisez le code ci-dessous pour confirmer votre identite et continuer la procedure.</p>'
                . '<div style="margin:24px 0;padding:20px;border-radius:14px;background:#f3f4f6;border:1px dashed #d1d5db;text-align:center;">'
                . '<div style="font-size:13px;color:#6b7280;letter-spacing:.08em;text-transform:uppercase;">Code de verification</div>'
                . '<div style="margin-top:12px;font-size:34px;font-weight:700;letter-spacing:10px;color:#111827;">' . htmlspecialchars((string) $resetData['code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
                . '</div>'
                . '<p style="margin:0 0 18px;font-size:14px;color:#4b5563;">Ce code expire dans 60 minutes.</p>'
                . '<p style="margin:0 0 18px;font-size:14px;color:#4b5563;">Saisissez-le sur la page suivante :</p>'
                . '<p style="margin:0 0 24px;"><a href="' . htmlspecialchars($verifyCodeUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="display:inline-block;padding:14px 22px;background:#0f62fe;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:600;">Verifier mon code</a></p>'
                . '<p style="margin:0 0 16px;font-size:14px;color:#4b5563;">Si vous preferez un acces direct a la reinitialisation, utilisez ce bouton :</p>'
                . '<p style="margin:0 0 24px;"><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="display:inline-block;padding:14px 22px;background:#111827;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:600;">Ouvrir la reinitialisation</a></p>'
                . '<p style="margin:0;font-size:13px;color:#6b7280;">Si vous n etes pas a l origine de cette demande, ignorez simplement cet email.</p>'
                . '</td></tr>'
                . '<tr><td style="padding:20px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.6;">'
                . 'Email automatique envoye par Societe IDENE depuis ghaiethbouamor23@gmail.com. Merci de ne pas y repondre.'
                . '</td></tr>'
                . '</table>'
                . '</td></tr>'
                . '</table>'
                . '</body></html>'
            );

        try {
            $this->mailer->send($emailMessage);
        } catch (TransportExceptionInterface|\Throwable $e) {
            if (($_ENV['APP_ENV'] ?? 'dev') === 'dev') {
                return ApiResponse::ok([
                    'ok' => true,
                    'message' => 'Email non envoye via Brevo. Lien de reinitialisation genere pour le mode dev.',
                    'reset_url' => $resetUrl,
                    'verify_code_url' => $verifyCodeUrl,
                    'reset_code' => $resetData['code'],
                    'details' => $e->getMessage(),
                ]);
            }

            return ApiResponse::error(
                'Le compte est verifie, mais l email n a pas pu etre envoye. Verifiez la configuration Brevo.',
                500,
                [
                    'details' => $e->getMessage(),
                ]
            );
        }

        return ApiResponse::ok([
            'ok' => true,
            'message' => 'Compte verifie. Un email de reinitialisation vient d etre envoye.',
        ]);
    }

    #[Route('/api/auth/verify-reset-code', name: 'api_auth_verify_reset_code', methods: ['POST'])]
    public function verifyResetCode(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $code = preg_replace('/\D+/', '', (string) ($payload['code'] ?? ''));

        $errors = array_filter([
            $this->validator->email($email),
            $code === '' ? 'Code obligatoire.' : null,
            strlen($code) !== 6 ? 'Le code doit contenir 6 chiffres.' : null,
        ]);
        if ($errors !== []) {
            return ApiResponse::validation($errors);
        }

        $record = $this->findResetCodeRecord($email, $code);
        if (!$record) {
            return ApiResponse::error('Code invalide ou expire.', 404);
        }

        $resetData = $this->issuePasswordResetToken((int) $record['user_id']);

        return ApiResponse::ok([
            'ok' => true,
            'message' => 'Code verifie. Vous pouvez maintenant changer le mot de passe.',
            'reset_url' => '/reset-password?token=' . urlencode((string) $resetData['token']),
        ]);
    }

    #[Route('/api/auth/reset', name: 'api_auth_reset', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $token = trim((string) ($payload['token'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $passwordConfirm = (string) ($payload['password_confirm'] ?? '');

        $errors = array_filter([
            $token === '' ? 'Token de reinitialisation manquant.' : null,
            $this->validator->password($password),
            $password !== $passwordConfirm ? 'La confirmation du mot de passe ne correspond pas.' : null,
        ]);
        if ($errors !== []) {
            return ApiResponse::validation($errors);
        }

        $record = $this->findResetTokenRecord($token);
        if (!$record) {
            return ApiResponse::error('Ce lien de reinitialisation est invalide ou expire.', 404);
        }

        $this->consumeResetToken((int) $record['id'], (int) $record['user_id'], $password);

        return ApiResponse::ok([
            'ok' => true,
            'message' => 'Votre mot de passe a ete mis a jour. Vous pouvez maintenant vous connecter.',
        ]);
    }

    #[Route('/api/profile', name: 'api_profile_update', methods: ['PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return ApiResponse::error('Non authentifie.', 401);
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'first_name' => trim((string) ($payload['first_name'] ?? '')),
            'last_name' => trim((string) ($payload['last_name'] ?? '')),
            'perfume_shop_name' => trim((string) ($payload['perfume_shop_name'] ?? '')),
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'location' => trim((string) ($payload['location'] ?? '')),
            'email' => mb_strtolower(trim((string) ($payload['email'] ?? ''))),
        ];

        $errors = array_filter([
            $this->validator->name($data['first_name'], 'Prenom'),
            $this->validator->name($data['last_name'], 'Nom'),
            $data['perfume_shop_name'] === '' ? 'Nom de la parfumerie obligatoire.' : null,
            mb_strlen($data['perfume_shop_name']) > 150 ? 'Nom de la parfumerie trop long.' : null,
            $this->validator->phone($data['phone']),
            $data['location'] === '' ? 'Localisation obligatoire.' : null,
            mb_strlen($data['location']) > 255 ? 'Localisation trop longue.' : null,
            $this->validator->email($data['email']),
        ]);
        if ($errors !== []) {
            return ApiResponse::validation($errors);
        }

        $db = $this->app->db();
        $check = $db->prepare(
            'SELECT id FROM users
             WHERE (email = :email OR phone = :phone) AND id <> :id
             LIMIT 1'
        );
        $check->execute([
            'email' => $data['email'],
            'phone' => $data['phone'],
            'id' => $userId,
        ]);
        if ($check->fetch()) {
            return ApiResponse::error('Email ou telephone deja utilise.', 409);
        }

        $stmt = $db->prepare(
            'UPDATE users
             SET first_name = :first_name,
                 last_name = :last_name,
                 perfume_shop_name = :shop,
                 phone = :phone,
                 location = :location,
                 email = :email
             WHERE id = :id'
        );
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'shop' => $data['perfume_shop_name'],
            'phone' => $data['phone'],
            'location' => $data['location'],
            'email' => $data['email'],
            'id' => $userId,
        ]);

        return ApiResponse::ok();
    }
}
