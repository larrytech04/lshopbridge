<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\WebauthnCredential;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Illuminate\Http\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * WebAuthn/passkey registration and authentication, built on the vetted
 * web-auth/webauthn-lib (MIT, actively maintained) rather than hand-rolling
 * CBOR/COSE parsing and signature verification — unlike TOTP (plain HMAC),
 * WebAuthn's attestation/assertion format is genuinely unsafe to reimplement.
 *
 * Only "none" attestation is requested (no enterprise device attestation),
 * matching what almost every consumer-facing passkey implementation does;
 * see the webauthn_credentials migration for why that keeps storage simple.
 *
 * The challenge is the only piece of ceremony state carried between the
 * "begin" and "finish" HTTP requests (stashed in the session as a plain
 * base64url string). Everything else the ceremony needs — rp, user,
 * exclude/allow credential lists — is rebuilt fresh from the database each
 * time rather than round-tripped, so there's no risk of a stale credential
 * list surviving a session.
 */
class WebauthnService
{
    private const CHALLENGE_BYTES = 32;

    private const TIMEOUT_MS = 60000;

    public function rpId(): string
    {
        return (string) parse_url(config('app.url'), PHP_URL_HOST);
    }

    public function rpEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(config('app.name'), $this->rpId());
    }

    /** A stable, opaque per-user identifier (never the raw user id) — required by the WebAuthn spec, capped at 64 bytes. */
    public function userHandleFor(int $userId): string
    {
        return hash('sha256', 'webauthn-user:'.$userId.':'.config('app.key'), true);
    }

    /** @return PublicKeyCredentialDescriptor[] */
    private function credentialDescriptorsFor(User $user): array
    {
        return WebauthnCredential::where('user_id', $user->id)->get()
            ->map(fn (WebauthnCredential $c) => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                base64_decode($c->credential_id),
                $c->transports ?? [],
            ))->all();
    }

    private function buildCreationOptions(User $user, string $challenge): PublicKeyCredentialCreationOptions
    {
        return PublicKeyCredentialCreationOptions::create(
            rp: $this->rpEntity(),
            user: PublicKeyCredentialUserEntity::create($user->email, $this->userHandleFor($user->id), $user->name),
            challenge: $challenge,
            pubKeyCredParams: [
                PublicKeyCredentialParameters::create('public-key', ES256::ID),
                PublicKeyCredentialParameters::create('public-key', RS256::ID),
            ],
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED,
            ),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $this->credentialDescriptorsFor($user),
            timeout: self::TIMEOUT_MS,
        );
    }

    private function buildRequestOptions(User $user, string $challenge): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: $challenge,
            rpId: $this->rpId(),
            allowCredentials: $this->credentialDescriptorsFor($user),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            timeout: self::TIMEOUT_MS,
        );
    }

    /** @return array{options: array<string, mixed>, challenge: string} JSON-ready options for the browser + the base64url challenge to stash in session */
    public function creationOptionsFor(User $user): array
    {
        $challenge = random_bytes(self::CHALLENGE_BYTES);
        $options = $this->buildCreationOptions($user, $challenge);

        return [
            'options' => [
                'challenge' => $this->b64url($challenge),
                'rp' => ['name' => $options->rp->name, 'id' => $options->rp->id],
                'user' => [
                    'id' => $this->b64url($options->user->id),
                    'name' => $options->user->name,
                    'displayName' => $options->user->displayName,
                ],
                'pubKeyCredParams' => array_map(fn (PublicKeyCredentialParameters $p) => ['type' => $p->type, 'alg' => $p->alg], $options->pubKeyCredParams),
                'authenticatorSelection' => [
                    'userVerification' => $options->authenticatorSelection->userVerification,
                    'residentKey' => $options->authenticatorSelection->residentKey,
                ],
                'attestation' => $options->attestation,
                'excludeCredentials' => $this->describeDescriptors($options->excludeCredentials),
                'timeout' => self::TIMEOUT_MS,
            ],
            'challenge' => $this->b64url($challenge),
        ];
    }

    /** @return array{options: array<string, mixed>, challenge: string} */
    public function requestOptionsFor(User $user): array
    {
        $challenge = random_bytes(self::CHALLENGE_BYTES);
        $options = $this->buildRequestOptions($user, $challenge);

        return [
            'options' => [
                'challenge' => $this->b64url($challenge),
                'rpId' => $options->rpId,
                'allowCredentials' => $this->describeDescriptors($options->allowCredentials),
                'userVerification' => $options->userVerification,
                'timeout' => self::TIMEOUT_MS,
            ],
            'challenge' => $this->b64url($challenge),
        ];
    }

    /** @param  PublicKeyCredentialDescriptor[]  $descriptors
     * @return array<int, array<string, mixed>> */
    private function describeDescriptors(array $descriptors): array
    {
        return array_map(fn (PublicKeyCredentialDescriptor $d) => [
            'type' => $d->type, 'id' => $this->b64url($d->id), 'transports' => $d->transports,
        ], $descriptors);
    }

    /**
     * @param  array<string, mixed>  $responseJson  the browser's raw registration response, decoded from JSON
     */
    public function verifyRegistration(array $responseJson, string $challengeB64, User $user, Request $request): WebauthnCredential
    {
        $options = $this->buildCreationOptions($user, $this->b64urlDecode($challengeB64));

        $credential = $this->serializer()->denormalize($responseJson, PublicKeyCredential::class, 'json');
        if (! $credential instanceof PublicKeyCredential || ! $credential->response instanceof AuthenticatorAttestationResponse) {
            throw AuthenticatorResponseVerificationException::create('Not an attestation response.');
        }

        if (WebauthnCredential::where('credential_id', base64_encode($credential->rawId))->exists()) {
            throw AuthenticatorResponseVerificationException::create('This authenticator is already registered.');
        }

        $validator = AuthenticatorAttestationResponseValidator::create($this->ceremonyFactory($request)->creationCeremony());
        $record = $validator->check($credential->response, $options, $request->getHost());

        return WebauthnCredential::fromCredentialRecord($user, (string) $request->input('name', 'Passkey'), $record);
    }

    /**
     * @param  array<string, mixed>  $responseJson  the browser's raw assertion response, decoded from JSON
     */
    public function verifyAssertion(array $responseJson, string $challengeB64, User $user, Request $request): WebauthnCredential
    {
        $options = $this->buildRequestOptions($user, $this->b64urlDecode($challengeB64));

        $credential = $this->serializer()->denormalize($responseJson, PublicKeyCredential::class, 'json');
        if (! $credential instanceof PublicKeyCredential || ! $credential->response instanceof AuthenticatorAssertionResponse) {
            throw AuthenticatorResponseVerificationException::create('Not an assertion response.');
        }

        $stored = WebauthnCredential::where('user_id', $user->id)
            ->where('credential_id', base64_encode($credential->rawId))
            ->first();
        $stored !== null || throw AuthenticatorResponseVerificationException::create('Unknown credential.');

        $validator = AuthenticatorAssertionResponseValidator::create($this->ceremonyFactory($request)->requestCeremony());
        $record = $validator->check(
            $stored->toCredentialRecord(),
            $credential->response,
            $options,
            $request->getHost(),
            $this->userHandleFor($user->id),
        );

        $stored->update(['counter' => $record->counter, 'last_used_at' => now()]);

        return $stored;
    }

    /**
     * The allowed origin is derived from the live request rather than
     * hardcoded to config('app.url') — this app serves from exactly one
     * origin at a time, so this keeps the check tied to wherever it's
     * actually being accessed from (dev on a non-default port, prod on the
     * real domain) instead of silently breaking if APP_URL ever drifts from
     * reality. rp.id (a stable identity tied to already-registered
     * credentials) is intentionally NOT derived this way — see rpId().
     */
    private function ceremonyFactory(Request $request): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAllowedOrigins([$request->getSchemeAndHttpHost()]);
        $factory->setAlgorithmManager(Manager::create()->add(ES256::create(), RS256::create()));
        $factory->setAttestationStatementSupportManager(new AttestationStatementSupportManager([
            new NoneAttestationStatementSupport(),
        ]));

        return $factory;
    }

    private function serializer(): SerializerInterface
    {
        static $serializer = null;

        return $serializer ??= (new WebauthnSerializerFactory(new AttestationStatementSupportManager([
            new NoneAttestationStatementSupport(),
        ])))->create();
    }

    private function b64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }
}
