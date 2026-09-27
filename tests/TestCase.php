<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base de todos os testes do FacilMed.
 *
 * RefreshDatabase + $seed: o banco de teste é recriado UMA vez com os dados
 * fictícios (DatabaseSeeder) e cada teste roda dentro de uma transação que é
 * desfeita no fim. Então todo teste começa com os mesmos médicos, clínicas,
 * pacientes e bases simuladas do README §3.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** Loga com uma das contas do seed pelo e-mail (senha não importa aqui). */
    protected function comoUsuario(string $email): static
    {
        return $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    protected function comoPaciente(string $email = 'ana@facilmed.test'): static
    {
        return $this->comoUsuario($email);
    }

    protected function comoMedico(string $email = 'helena@facilmed.test'): static
    {
        return $this->comoUsuario($email);
    }

    protected function comoClinica(string $email = 'contato@vidaplena.test'): static
    {
        return $this->comoUsuario($email);
    }

    protected function comoAdmin(): static
    {
        return $this->comoUsuario('admin@facilmed.test');
    }
}
