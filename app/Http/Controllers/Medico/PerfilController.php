<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;

class PerfilController extends Controller
{
    /**
     * 01/10/2026 (plano novo do grupo): o médico só VÊ o perfil. Quem atualiza
     * dados, especialidades e convênios é a clínica (Clinica\MedicoController).
     * Aqui ele troca a senha (rota password.update, do Breeze) — inclusive a
     * provisória do primeiro acesso.
     */
    public function edit()
    {
        return view('medico.perfil', [
            'medico' => auth()->user()->medico->load('user', 'especialidades', 'convenios'),
        ]);
    }
}
