<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            // No interceptar excepciones de validación o autenticación
            if ($e instanceof \Illuminate\Validation\ValidationException || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null; // Dejar que Laravel lo maneje y devuelva los errores
            }

            // Solo interceptar peticiones Inertia
            if ($request->header('X-Inertia')) {
                $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException ? $e->getStatusCode() : 500;

                // No ocultar errores 500 en entorno local/debug para facilitar el desarrollo
                if ($status === 500 && config('app.debug')) {
                    return null;
                }

                if (in_array($status, [403, 404, 500])) {
                    return \Inertia\Inertia::render('Errores/'.$status, [
                        'codigo' => $status,
                        'mensaje' => match($status) {
                            403 => 'No tienes permisos para acceder a esta sección.',
                            404 => 'La página o recurso solicitado no existe.',
                            500 => 'Ocurrió un error interno del servidor. Intenta nuevamente o contacta al administrador.',
                            default => 'Ha ocurrido un error inesperado.'
                        },
                    ])->toResponse($request)->setStatusCode($status);
                }
            }
        });
    })->create();
