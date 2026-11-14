<?php

use App\Exceptions\SlotTerisi;
use App\Http\Middleware\CheckJwtScope;
use App\Http\Middleware\VerifyJwtToken;
use App\Http\Problem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => VerifyJwtToken::class,
            'scope' => CheckJwtScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // 422 -> data permintaan tidak sesuai kontrak
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return Problem::response($request, 422, 'validasi-gagal', 'Validasi Gagal', $e->getMessage(), [
                    'errors' => $e->errors(),
                ]);
            }
        });

        // 404 -> sumber daya tidak ada (termasuk kode reservasi yang tidak ditemukan)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return Problem::response($request, 404, 'tidak-ditemukan', 'Sumber Daya Tidak Ditemukan', 'Sumber daya yang diminta tidak ditemukan.');
            }
        });

        // 409 -> konflik aturan bisnis: slot sudah terisi oleh pelanggan lain
        $exceptions->render(function (SlotTerisi $e, Request $request) {
            if ($request->is('api/*')) {
                return Problem::response($request, 409, 'slot-terisi', 'Slot Sudah Terisi', $e->getMessage());
            }
        });

        // 429 -> batas laju permintaan per klien terlampaui
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return Problem::response(
                    $request,
                    429,
                    'terlalu-banyak-permintaan',
                    'Terlalu Banyak Permintaan',
                    'Batas laju permintaan per menit untuk klien ini telah terlampaui.',
                )->withHeaders($e->getHeaders());
            }
        });
    })
    ->booted(function (): void {
        RateLimiter::for('api', function (Request $request) {
            $clientId = (string) $request->attributes->get('jwt_client_id', $request->ip());

            return Limit::perMinute(60)->by($clientId);
        });
    })
    ->create();
