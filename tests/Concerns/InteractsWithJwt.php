<?php

namespace Tests\Concerns;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

trait InteractsWithJwt
{
    private string $privateKeyPem = '';

    private string $kid = 'kunci-uji-tans-1';

    protected function siapkanJwksPalsu(): void
    {
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($res, $privatePem);
        $this->privateKeyPem = $privatePem;

        $details = openssl_pkey_get_details($res);
        $jwks = [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kid' => $this->kid,
                    'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
                    'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
                ],
            ],
        ];

        $jwksUrl = (string) config('services.oidc.jwks_url');
        Cache::forget('oidc_jwks_'.md5($jwksUrl));

        Http::fake([
            $jwksUrl => Http::response($jwks, 200),
        ]);
    }

    protected function buatToken(
        string $scope = 'antrean:baca antrean:tulis',
        string $clientId = 'kiosk',
        array $overrideClaims = [],
    ): string {
        if ($this->privateKeyPem === '') {
            $this->siapkanJwksPalsu();
        }

        $now = time();
        $claims = array_merge([
            'iss' => config('services.oidc.issuer'),
            'aud' => [config('services.oidc.audience')],
            'sub' => 'service-account-'.$clientId,
            'azp' => $clientId,
            'scope' => $scope,
            'iat' => $now,
            'exp' => $now + 300,
        ], $overrideClaims);

        return JWT::encode($claims, $this->privateKeyPem, 'RS256', $this->kid);
    }
}
