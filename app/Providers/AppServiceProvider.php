<?php

namespace App\Providers;

use App\Mail\BrevoTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 01/10/2026: "transporte" de e-mail pela API do Brevo (o Render grátis
        // bloqueia SMTP). Mail::extend ensina o Laravel um jeito novo de enviar;
        // config/mail.php diz quando usar ('brevo'). Ver App\Mail\BrevoTransport.
        Mail::extend('brevo', fn (array $config) => new BrevoTransport((string) ($config['key'] ?? '')));
    }
}
