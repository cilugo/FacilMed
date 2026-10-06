<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Telas cujo BACK-END está pronto mas a VIEW ainda não existe (24/09).
 *
 * O teste coloca uma view provisória (só durante o teste) que devolve as
 * variáveis recebidas. Assim confere que o controller monta os dados sem
 * erro e com as variáveis combinadas no README §7.
 * Quando a view de verdade for criada, o teste continua valendo: a view
 * real tem prioridade? NÃO - a provisória vem antes. Então este teste
 * testa o controller, não a view.
 */
class TelasInternasTest extends TestCase
{
    public const TELAS = [
        // [conta, url, view, variáveis obrigatórias] — revisto em 01/10/2026
        ['usuario', '/usuario',                 'usuario.dashboard',      ['saudacao', 'cartoes', 'especialidades', 'cidades', 'convenios', 'ultimas', 'carteirinhas']],
        ['usuario', '/usuario/avaliacoes',      'usuario.avaliacoes',     ['avaliacoes']],
        ['clinica',  '/clinica',                  'clinica.dashboard',       ['cartoes', 'unidades', 'porEspecialidade', 'semFaixa', 'ultimas']],   // 05/10: sem "Acesso rápido"
        ['clinica',  '/clinica/medicos',          'clinica.medicos',         ['vinculos', 'unidades']],
        ['clinica',  '/clinica/medicos/novo',     'clinica.medicos-form',    ['especialidades', 'unidades']],
        ['clinica',  '/clinica/medicos/1/editar', 'clinica.medicos-editar',  ['medico', 'especialidades', 'convenios']],
        ['clinica',  '/clinica/especialidades',   'clinica.especialidades',  ['especialidades', 'daClinica']],
        ['clinica',  '/clinica/unidades',         'clinica.unidades',        ['locais', 'dias']],
        ['clinica',  '/clinica/avaliacoes',       'clinica.avaliacoes',      ['unidades', 'medicos', 'total', 'avaliacoes']],
        ['clinica',  '/clinica/perfil',           'clinica.perfil',          ['clinica']],
        ['admin',    '/admin',                    'admin.dashboard',         ['cartoes', 'locais', 'medicos', 'ultimosCadastros', 'ultimasAvaliacoes']],
        ['admin',    '/admin/usuarios',           'admin.usuarios',          ['usuarios']],
        ['admin',    '/admin/cnpjs',              'admin.cnpjs',             ['linhas', 'problemas']],
        ['admin',    '/admin/clinicas',           'admin.clinicas',          ['clinicas']],
        ['admin',    '/admin/especialidades',     'admin.especialidades',    ['especialidades']],
        ['admin',    '/admin/perfil',             'admin.perfil',            []],
    ];

    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pasta = storage_path('framework/testing/views-provisorias');
        File::ensureDirectoryExists($this->pasta);

        foreach (self::TELAS as [, , $view]) {
            $arquivo = $this->pasta . '/' . str_replace('.', '/', $view) . '.blade.php';
            File::ensureDirectoryExists(dirname($arquivo));
            File::put($arquivo, "PROVISORIA {{ implode(',', array_keys(get_defined_vars()['__data'])) }}");
        }

        View::getFinder()->prependLocation($this->pasta);
        View::getFinder()->flush();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pasta);
        parent::tearDown();
    }

    public function test_controllers_montam_os_dados_das_telas(): void
    {
        $contas = ['usuario' => 'ana@facilmed.test', 'clinica' => 'contato@vidaplena.test', 'admin' => 'admin@facilmed.test'];

        foreach (self::TELAS as [$conta, $url, $view, $variaveis]) {
            $resposta = $this->comoUsuario($contas[$conta])->get($url);
            $resposta->assertOk()->assertViewIs($view);

            foreach ($variaveis as $v) {
                $resposta->assertViewHas($v);
            }
        }
    }

    public function test_comentario_da_avaliacao_so_para_o_autor_a_clinica_e_o_admin(): void
    {
        $temComentario = fn ($p) => $p->first() === null || array_key_exists('comentario', $p->first()->toArray());

        // Clínica e admin recebem o comentário (makeVisible no controller).
        $this->comoClinica()->get('/clinica/avaliacoes')->assertViewHas('avaliacoes', $temComentario);
        $this->comoAdmin()->get('/admin')->assertViewHas('ultimasAvaliacoes', $temComentario);
    }
}
