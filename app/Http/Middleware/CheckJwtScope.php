<?php

namespace App\Http\Middleware;

use App\Http\Problem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckJwtScope
{
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        $claims = $request->attributes->get('jwt_claims', []);
        $grantedScopes = array_filter(explode(' ', (string) ($claims['scope'] ?? '')));

        foreach ($requiredScopes as $scope) {
            if (! in_array($scope, $grantedScopes, true)) {
                return Problem::response(
                    $request,
                    403,
                    'akses-ditolak',
                    'Akses Ditolak',
                    "Token tidak memiliki scope yang diperlukan: {$scope}.",
                );
            }
        }

        return $next($request);
    }
}
