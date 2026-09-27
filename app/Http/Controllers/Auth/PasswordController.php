<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Rules\SenhaPadrao;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', SenhaPadrao::regra(), 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'A senha nova precisa ser diferente da atual.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Senha temporária (médico cadastrado pela clínica) deixa de valer.
        $request->user()->medico?->update(['senha_temporaria' => false]);

        return back()->with('status', 'password-updated');
    }
}
