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
        $middleware->alias([
            'ativa' => \App\Http\Middleware\GarantirContaAtiva::class,
            'tipo' => \App\Http\Middleware\GarantirTipoUsuario::class,
        ]);

        // Toda página autenticada bloqueia conta suspensa; 'tipo:x' nas
        // rotas restringe por papel além disso. Ver routes/web.php.
        $middleware->web(append: [
            \App\Http\Middleware\GarantirContaAtiva::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
