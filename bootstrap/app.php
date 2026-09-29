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
        // No Render o site fica atrás de um proxy que cuida do HTTPS. Sem
        // isto o Laravel acha que a visita é http:// e gera links de CSS/JS
        // em http, que o navegador bloqueia. No XAMPP não muda nada.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'ativa' => \App\Http\Middleware\GarantirContaAtiva::class,
            'tipo'  => \App\Http\Middleware\GarantirTipoUsuario::class,
        ]);

        // Conta bloqueada/inativa é deslogada em QUALQUER página, não só nas
        // rotas que lembram de pedir 'ativa' (routes/web.php conta com isto).
        $middleware->appendToGroup('web', \App\Http\Middleware\GarantirContaAtiva::class);

        // Médico com senha temporária (cadastrado pela clínica) troca antes de usar.
        $middleware->appendToGroup('web', \App\Http\Middleware\ExigirTrocaDeSenha::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
