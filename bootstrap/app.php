<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
       if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: ['*']);
       }

       $middleware->alias([
            'super_admin'   => \App\Http\Middleware\SuperAdmin::class,
            'company_admin' => \App\Http\Middleware\CompanyAdmin::class,
            'permission'    => \App\Http\Middleware\CheckPermission::class,
            'screen_unlocked' => \App\Http\Middleware\EnsureScreenUnlocked::class,
            'crm_access' => \App\Http\Middleware\EnsureCrmAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (app()->environment('testing') || $request->expectsJson() || !$request->is('admin/*')) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = trim($e->getMessage()) ?: 'Something went wrong while processing this request.';

            return back()
                ->withInput()
                ->with('error', $message)
                ->with('error_details', [
                    'title' => $status === 422 ? 'Unable to save this entry' : 'Request failed',
                    'status' => $status,
                    'message' => $message,
                    'file' => config('app.debug') ? $e->getFile() : null,
                    'line' => config('app.debug') ? $e->getLine() : null,
                ]);
        });
    })->create();
