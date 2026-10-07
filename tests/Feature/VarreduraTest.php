<?php
namespace Tests\Feature;
use App\Models\{Avaliacao, Medico, Clinica, Especialidade, UsuarioPlano, Local, User, Vinculo, Convenio, Plano};
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
/**
 * Varredura (28/09/2026): abre TODAS as páginas GET do sistema com as quatro
 * visões (visitante, usuário, clínica, admin — médico não tem conta desde 01/10) e falha se alguma der
 * erro 500. 403, 404 e redirecionamento são respostas normais — o que não
 * pode é a página quebrar. Rode antes de apresentar: php artisan test --filter Varredura
 */
class VarreduraTest extends TestCase
{
    public function test_nenhuma_pagina_da_500(): void
    {
        $ids = [
            'vinculo' => Vinculo::first()->id, 'avaliacao' => Avaliacao::first()->id,
            'medico' => Medico::first()->id, 'clinica' => Clinica::first()->id, 'especialidade' => Especialidade::first()->slug,
            'usuarioPlano' => UsuarioPlano::first()->id, 'local' => Local::first()->id, 'user' => User::first()->id,
            'convenio' => Convenio::first()->id, 'plano' => Plano::first()->id, 'token' => 'x', 'id' => 1, 'hash' => 'x',
        ];
        $contas = [null, 'ana@facilmed.test', 'contato@vidaplena.test', 'admin@facilmed.test'];
        $erros = []; $total = 0;
        foreach (Route::getRoutes() as $r) {
            if (! in_array('GET', $r->methods())) continue;
            $uri = $r->uri();
            if (str_starts_with($uri, '_') || str_contains($uri, 'storage') || $uri === 'up') continue;
            $url = '/' . preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $ids[$m[1]] ?? 1, $uri);
            foreach ($contas as $email) {
                $this->app['auth']->forgetGuards();
                $req = $email ? $this->actingAs(User::where('email', $email)->first()) : $this;
                $st = $req->get($url)->status(); $total++;
                if ($st >= 500) $erros[] = "$st $url como " . ($email ?? 'visitante');
                $this->flushSession();
            }
        }
        $this->assertSame([], $erros, "Páginas com erro 500 (de $total abertas):\n" . implode("\n", $erros));
    }
}
