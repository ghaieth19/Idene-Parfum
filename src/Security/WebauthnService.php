<?php

namespace App\Security;

use App\Support\AppContext;
use Symfony\Component\HttpFoundation\Request;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

final class WebauthnService
{
    private const SESSION_REGISTER_OPTIONS = 'webauthn.register.options';
    private const SESSION_REGISTER_USER_ID = 'webauthn.register.user_id';
    private const SESSION_LOGIN_OPTIONS = 'webauthn.login.options';
    private const SESSION_LOGIN_USER_ID = 'webauthn.login.user_id';
    private const SESSION_PENDING_REGISTER_OPTIONS = 'webauthn.pending.register.options';
    private const SESSION_PENDING_REGISTER_HANDLE = 'webauthn.pending.register.handle';
    private const SESSION_PENDING_REGISTER_SOURCE = 'webauthn.pending.register.source';

    public function __construct(
        private readonly AppContext $app,
        private readonly WebauthnCredentialRepository $credentialRepository,
    ) {
    }

    public function createRegistrationOptions(Request $request, array $user): PublicKeyCredentialCreationOptions
    {
        $rpId = $this->resolveRelyingPartyId($request);
        $userEntity = $this->createUserEntity($user);
        $excludeCredentials = array_map(
            static fn ($source) => $source->getPublicKeyCredentialDescriptor(),
            $this->credentialRepository->findAllForUserEntity($userEntity)
        );

        $options = $this->buildRegistrationOptions($rpId, $userEntity, $excludeCredentials);

        $this->app->session()->set(self::SESSION_REGISTER_OPTIONS, json_encode($options, JSON_THROW_ON_ERROR));
        $this->app->session()->set(self::SESSION_REGISTER_USER_ID, (int) $user['id']);

        return $options;
    }

    public function createPendingRegistrationOptions(Request $request): PublicKeyCredentialCreationOptions
    {
        $rpId = $this->resolveRelyingPartyId($request);
        $pendingHandle = 'pending-' . bin2hex(random_bytes(16));
        $userEntity = PublicKeyCredentialUserEntity::create(
            'pending@idene.local',
            $pendingHandle,
            'Nouvel utilisateur IDENE'
        );

        $options = $this->buildRegistrationOptions($rpId, $userEntity, []);

        $session = $this->app->session();
        $session->set(self::SESSION_PENDING_REGISTER_OPTIONS, json_encode($options, JSON_THROW_ON_ERROR));
        $session->set(self::SESSION_PENDING_REGISTER_HANDLE, $pendingHandle);
        $session->remove(self::SESSION_PENDING_REGISTER_SOURCE);

        return $options;
    }

    public function finishRegistration(Request $request, int $userId, array $credentialData): void
    {
        $session = $this->app->session();
        $expectedUserId = (int) $session->get(self::SESSION_REGISTER_USER_ID);
        $rawOptions = (string) $session->get(self::SESSION_REGISTER_OPTIONS, '');

        if ($expectedUserId !== $userId || $rawOptions === '') {
            throw new \RuntimeException('Session biometrique invalide. Rechargez puis recommencez.');
        }

        $source = $this->validateRegistrationResponse($request, $rawOptions, $credentialData);
        $this->remapCredentialSourceToUser($source, $userId);
        $this->credentialRepository->saveCredentialSource($source);
        $this->clearRegistrationSession();
    }

    public function finishPendingRegistration(Request $request, array $credentialData): void
    {
        $session = $this->app->session();
        $rawOptions = (string) $session->get(self::SESSION_PENDING_REGISTER_OPTIONS, '');
        $pendingHandle = (string) $session->get(self::SESSION_PENDING_REGISTER_HANDLE, '');

        if ($rawOptions === '' || $pendingHandle === '') {
            throw new \RuntimeException('Aucun scan biometrique en attente. Relancez le scan.');
        }

        $source = $this->validateRegistrationResponse($request, $rawOptions, $credentialData);
        if ($source->userHandle !== $pendingHandle) {
            throw new \RuntimeException('Le scan biometrique ne correspond pas a la session en attente.');
        }

        $session->set(self::SESSION_PENDING_REGISTER_SOURCE, json_encode($source, JSON_THROW_ON_ERROR));
        $session->remove(self::SESSION_PENDING_REGISTER_OPTIONS);
        $session->remove(self::SESSION_PENDING_REGISTER_HANDLE);
    }

    public function attachPendingRegistrationToUser(int $userId): bool
    {
        $session = $this->app->session();
        $rawSource = (string) $session->get(self::SESSION_PENDING_REGISTER_SOURCE, '');
        if ($rawSource === '') {
            return false;
        }

        $data = json_decode($rawSource, true, 512, JSON_THROW_ON_ERROR);
        $source = PublicKeyCredentialSource::createFromArray($data);
        $this->remapCredentialSourceToUser($source, $userId);
        $this->credentialRepository->saveCredentialSource($source);
        $session->remove(self::SESSION_PENDING_REGISTER_SOURCE);

        return true;
    }

    public function createLoginOptions(Request $request, array $user): PublicKeyCredentialRequestOptions
    {
        $rpId = $this->resolveRelyingPartyId($request);
        $userEntity = $this->createUserEntity($user);
        $sources = $this->credentialRepository->findAllForUserEntity($userEntity);
        if ($sources === []) {
            throw new \RuntimeException('Aucune biometrie configuree pour ce compte.');
        }

        $allowCredentials = array_map(
            static fn ($source) => $source->getPublicKeyCredentialDescriptor(),
            $sources
        );

        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $rpId,
            $allowCredentials,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            60000
        );

        $this->app->session()->set(self::SESSION_LOGIN_OPTIONS, json_encode($options, JSON_THROW_ON_ERROR));
        $this->app->session()->set(self::SESSION_LOGIN_USER_ID, (int) $user['id']);

        return $options;
    }

    public function createDiscoverableLoginOptions(Request $request): PublicKeyCredentialRequestOptions
    {
        $rpId = $this->resolveRelyingPartyId($request);
        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $rpId,
            [],
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            60000
        );

        $this->app->session()->set(self::SESSION_LOGIN_OPTIONS, json_encode($options, JSON_THROW_ON_ERROR));
        $this->app->session()->remove(self::SESSION_LOGIN_USER_ID);

        return $options;
    }

    public function finishLogin(Request $request, array $credentialData): array
    {
        $session = $this->app->session();
        $expectedUserId = (int) $session->get(self::SESSION_LOGIN_USER_ID);
        $rawOptions = (string) $session->get(self::SESSION_LOGIN_OPTIONS, '');

        if ($rawOptions === '') {
            throw new \RuntimeException('Session biometrique expiree. Recommencez la connexion.');
        }

        $credential = $this->createLoader()->load(json_encode($credentialData, JSON_THROW_ON_ERROR));
        if (!$credential->response instanceof AuthenticatorAssertionResponse) {
            throw new \RuntimeException('Reponse biometrique invalide.');
        }

        $credentialSource = $this->credentialRepository->findOneByCredentialId($credential->rawId);
        if ($credentialSource === null) {
            throw new \RuntimeException('Identifiant biometrique inconnu.');
        }

        $userId = (int) $credentialSource->userHandle;
        if ($userId <= 0) {
            throw new \RuntimeException('Compte biometrique invalide.');
        }
        if ($expectedUserId > 0 && $expectedUserId !== $userId) {
            $this->clearLoginSession();
            throw new \RuntimeException('Cette biometrie ne correspond pas au compte attendu.');
        }

        $user = $this->app->fetchUserWithRoleById($userId);
        if (!$user) {
            $this->clearLoginSession();
            throw new \RuntimeException('Compte introuvable ou desactive.');
        }

        $options = PublicKeyCredentialRequestOptions::createFromArray(
            json_decode($rawOptions, true, 512, JSON_THROW_ON_ERROR)
        );

        $validatedSource = AuthenticatorAssertionResponseValidator::create()->check(
            $credentialSource,
            $credential->response,
            $options,
            $request->getHost(),
            (string) $userId
        );

        $this->credentialRepository->saveCredentialSource($validatedSource);
        $this->clearLoginSession();

        return $user;
    }

    public function userHasCredentials(int $userId): bool
    {
        return $this->credentialRepository->hasCredentialsForUserId($userId);
    }

    private function buildRegistrationOptions(
        string $rpId,
        PublicKeyCredentialUserEntity $userEntity,
        array $excludeCredentials
    ): PublicKeyCredentialCreationOptions {
        return PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create('IDENE PARFUM', $rpId),
            $userEntity,
            random_bytes(32),
            [
                PublicKeyCredentialParameters::createPk(-7),
                PublicKeyCredentialParameters::createPk(-257),
            ],
            AuthenticatorSelectionCriteria::create(
                AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED
            ),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excludeCredentials,
            60000
        );
    }

    private function validateRegistrationResponse(Request $request, string $rawOptions, array $credentialData): PublicKeyCredentialSource
    {
        $credential = $this->createLoader()->load(json_encode($credentialData, JSON_THROW_ON_ERROR));
        if (!$credential->response instanceof AuthenticatorAttestationResponse) {
            throw new \RuntimeException('Reponse d enrollement invalide.');
        }

        $options = PublicKeyCredentialCreationOptions::createFromArray(
            json_decode($rawOptions, true, 512, JSON_THROW_ON_ERROR)
        );

        return AuthenticatorAttestationResponseValidator::create()->check(
            $credential->response,
            $options,
            $request->getHost()
        );
    }

    private function remapCredentialSourceToUser(PublicKeyCredentialSource $source, int $userId): void
    {
        $source->userHandle = (string) $userId;
    }

    private function createUserEntity(array $user): PublicKeyCredentialUserEntity
    {
        $displayName = trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? '')));
        if ($displayName === '') {
            $displayName = (string) ($user['email'] ?? 'Utilisateur IDENE');
        }

        return PublicKeyCredentialUserEntity::create(
            (string) ($user['email'] ?? ('user-' . (int) $user['id'])),
            (string) (int) $user['id'],
            $displayName
        );
    }

    private function createLoader(): PublicKeyCredentialLoader
    {
        $attestationLoader = AttestationObjectLoader::create(
            AttestationStatementSupportManager::create()
        );

        return PublicKeyCredentialLoader::create($attestationLoader);
    }

    private function resolveRelyingPartyId(Request $request): string
    {
        $host = mb_strtolower(trim($request->getHost()));

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return $host;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            throw new \RuntimeException('Domaine biometrique invalide. Utilisez http://localhost en local, ou un vrai domaine en https.');
        }

        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $host)) {
            throw new \RuntimeException('Domaine biometrique invalide. Utilisez localhost en local, ou un vrai domaine en https.');
        }

        return $host;
    }

    private function clearRegistrationSession(): void
    {
        $session = $this->app->session();
        $session->remove(self::SESSION_REGISTER_OPTIONS);
        $session->remove(self::SESSION_REGISTER_USER_ID);
    }

    private function clearLoginSession(): void
    {
        $session = $this->app->session();
        $session->remove(self::SESSION_LOGIN_OPTIONS);
        $session->remove(self::SESSION_LOGIN_USER_ID);
    }
}
