<?php

namespace App\Security;

use App\Support\AppContext;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

final class WebauthnCredentialRepository implements PublicKeyCredentialSourceRepository
{
    public function __construct(private readonly AppContext $app)
    {
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $stmt = $this->app->db()->prepare(
            'SELECT credential_source_json
             FROM webauthn_credentials
             WHERE credential_id = :credential_id
             LIMIT 1'
        );
        $stmt->execute([
            'credential_id' => $this->encodeCredentialId($publicKeyCredentialId),
        ]);

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return $this->hydrateSource((string) $row['credential_source_json']);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $userId = (int) $publicKeyCredentialUserEntity->id;
        if ($userId <= 0) {
            return [];
        }

        $stmt = $this->app->db()->prepare(
            'SELECT credential_source_json
             FROM webauthn_credentials
             WHERE user_id = :user_id
             ORDER BY id DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        $sources = [];
        foreach ($stmt->fetchAll() as $row) {
            $sources[] = $this->hydrateSource((string) $row['credential_source_json']);
        }

        return $sources;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $userId = (int) $publicKeyCredentialSource->userHandle;
        if ($userId <= 0) {
            throw new \RuntimeException('User handle WebAuthn invalide.');
        }

        $encodedCredentialId = $this->encodeCredentialId($publicKeyCredentialSource->publicKeyCredentialId);
        $payload = json_encode($publicKeyCredentialSource, JSON_THROW_ON_ERROR);

        $stmt = $this->app->db()->prepare(
            'INSERT INTO webauthn_credentials (user_id, credential_id, credential_source_json)
             VALUES (:user_id, :credential_id, :credential_source_json)
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                credential_source_json = VALUES(credential_source_json),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'user_id' => $userId,
            'credential_id' => $encodedCredentialId,
            'credential_source_json' => $payload,
        ]);
    }

    public function hasCredentialsForUserId(int $userId): bool
    {
        $stmt = $this->app->db()->prepare(
            'SELECT id FROM webauthn_credentials WHERE user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    private function hydrateSource(string $json): PublicKeyCredentialSource
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return PublicKeyCredentialSource::createFromArray($data);
    }

    private function encodeCredentialId(string $credentialId): string
    {
        return \ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded($credentialId);
    }
}
