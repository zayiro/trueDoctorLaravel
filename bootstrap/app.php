<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureDoctorContext;
use App\Http\Middleware\TenantMiddleware;
use App\Http\Middleware\BlockSqlInjection;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web', 'auth')
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/admin.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'role.redirect' => \App\Http\Middleware\RedirectByUserRole::class,
            'doctor.context' => EnsureDoctorContext::class,
            'tenant' => TenantMiddleware::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimiter::class,
            'api.validate.key' => \App\Http\Middleware\ValidateApiKey::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/zoom',
            'webhooks/zoom',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SeoOptimizationMiddleware::class,
            EnsureDoctorContext::class,
            TenantMiddleware::class,
            BlockSqlInjection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
