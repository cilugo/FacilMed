<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // 01/10/2026: a resposta é a MESMA com ou sem conta para esse e-mail -
        // senão a tela vira um jeito de descobrir quem tem conta no PointMed
        // (o login já faz assim: "E-mail ou senha incorretos"). Só o limite de
        // tentativas (um pedido por minuto por e-mail) aparece como erro.
        // Se o serviço de e-mail falhar (chave do Brevo errada, fora do ar), a
        // pessoa vê um aviso em vez de erro 500, e o motivo vai para o log.
        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.']);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return back()->with('status', 'Se esse e-mail tiver conta no PointMed, enviamos agora um link para criar uma senha nova. '
            . 'Confira a caixa de entrada e o spam. O link vale por 60 minutos.');
    }
}
