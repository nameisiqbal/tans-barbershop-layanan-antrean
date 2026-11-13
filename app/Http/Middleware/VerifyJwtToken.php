<?php

namespace App\Http\Middleware;

use App\Http\Problem;
use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyJwtToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized($request, 'Token Bearer wajib disertakan pada header Authorization.');
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return $this->unauthorized($request, 'Token Bearer tidak boleh kosong.');
        }

        try {
            $jwksUrl = (string) config('services.oidc.jwks_url');
            $ttl = (int) config('services.oidc.jwks_cache_ttl', 3600);

            $jwks = Cache::remember('oidc_jwks_'.md5($jwksUrl), $ttl, function () use ($jwksUrl) {
                return Http::timeout(5)->get($jwksUrl)->throw()->json();
            });

            $keySet = JWK::parseKeySet($jwks);
            $payload = (array) JWT::decode($token, $keySet);

            $expectedIssuer = rtrim((string) config('services.oidc.issuer'), '/');
            $tokenIssuer = rtrim((string) ($payload['iss'] ?? ''), '/');
            if ($expectedIssuer !== '' && $tokenIssuer !== $expectedIssuer) {
                return $this->unauthorized($request, 'Issuer token tidak sesuai.');
            }

            $expectedAudience = (string) config('services.oidc.audience');
            $audiences = (array) ($payload['aud'] ?? []);
            if ($expectedAudience !== '' && ! in_array($expectedAudience, $audiences, true)) {
                return $this->unauthorized($request, 'Audience token tidak sesuai.');
            }

            $request->attributes->set('jwt_claims', $payload);
            $request->attributes->set('jwt_client_id', $payload['azp'] ?? ($payload['sub'] ?? 'unknown'));
        } catch (Throwable $e) {
            return $this->unauthorized($request, 'Token tidak valid atau sudah kedaluwarsa.');
        }

        return $next($request);
    }

    private function unauthorized(Request $request, string $detail): Response
    {
        return Problem::response(
            $request,
            401,
            'tidak-terautentikasi',
            'Tidak Terautentikasi',
            $detail,
        )->withHeaders(['WWW-Authenticate' => 'Bearer realm="layanan-antrean"']);
    }
}
