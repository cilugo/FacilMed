<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinica\AtualizarMedicoPelaClinicaRequest;
use App\Http\Requests\Clinica\CadastrarMedicoPelaClinicaRequest;
use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\Vinculo;
use App\Support\FotoDePerfil;
use Illuminate\Support\Facades\DB;

/**
 * Clínica → Meus médicos.
 *
 * 01/10/2026 (documento de modificações): o médico não tem mais conta.
 * A clínica/hospital cadastra o PERFIL dele e o mantém (foto, bio, anos
 * de carreira, especialidades e convênios). O usuário vê esse perfil;
 * o médico não entra no sistema.
 */
class MedicoController extends Controller
{
    public function index()
    {
        $clinica = auth()->user()->clinica;

        return view('clinica.medicos', [
            'vinculos' => $clinica->vinculos()
                ->where('vinculos.ativo', true)
                ->with('medico.especialidades', 'local')
                ->get()
                ->sortBy(fn ($v) => $v->medico->nome)->values(),
            'unidades' => $clinica->locais()->where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function form()
    {
        return view('clinica.medicos-form', [
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'unidades'       => auth()->user()->clinica->locais()->where('ativo', true)->get(),
        ]);
    }

    /**
     * A clínica cadastra o médico.
     *
     * O CRM de médico NOVO é conferido na base simulada pelo FormRequest
     * (desde 24/09): se chegou aqui, bateu, e ele já nasce 'verificado'.
     *
     * Se o médico JÁ existe no sistema (mesmo CRM+UF), não crie outro:
     * crie só o vínculo com a unidade.
     */
    public function salvar(CadastrarMedicoPelaClinicaRequest $request)
    {
        $dados     = $request->validated();
        $existente = $request->medicoExistente();

        $vinculo = DB::transaction(function () use ($dados, $existente, $request) {
            $medico = $existente;

            if (! $medico) {
                $medico = Medico::create([
                    'nome'               => $dados['name'],
                    'cpf'                => $dados['cpf'],
                    'crm'                => $dados['crm'],
                    'uf'                 => $dados['uf'],
                    'status_verificacao' => 'verificado',   // conferido na base simulada pelo FormRequest
                    'verificado_em'      => now(),
                    'anos_atuacao'       => 0,
                ]);

                $medico->especialidades()->sync(
                    collect($dados['especialidades'])->values()
                        ->mapWithKeys(fn ($id, $i) => [$id => ['principal' => $i === 0]])->all()
                );
            }

            // Reativa vínculo antigo (desvinculado antes) em vez de duplicar - UNIQUE(medico, local).
            return Vinculo::updateOrCreate(
                ['medico_id' => $medico->id, 'local_id' => $dados['local_id']],
                [
                    'aceita_particular' => $request->has('aceita_particular') ? $request->boolean('aceita_particular') : true,
                    'aceita_convenio'   => $request->boolean('aceita_convenio'),
                    'ativo'             => true,
                ],
            );
        });

        $nome = $vinculo->medico->nome;
        $msg  = $existente
            ? "{$nome} já estava no PointMed e foi vinculado(a) à unidade."
            : "{$nome} cadastrado(a). CRM conferido na base simulada do PointMed.";

        // 05/10: sem Tabela de preços. Próximo passo: completar o perfil.
        return redirect()->route('clinica.medicos.editar', $vinculo->medico)
            ->with('sucesso', $msg . ' Complete a foto, a apresentação e os convênios dele.');
    }

    /** Editar o perfil do médico (01/10). Só médico que atende nesta clínica. */
    public function editar(Medico $medico)
    {
        $this->authorize('update', $medico);

        return view('clinica.medicos-editar', [
            'medico'         => $medico->load('especialidades', 'convenios'),
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'convenios'      => Convenio::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function atualizar(AtualizarMedicoPelaClinicaRequest $request, Medico $medico)
    {
        $dados = $request->validated();
        $novas = collect($dados['especialidades'])->map(fn ($id) => (int) $id)->unique()->values();
        $principal = (int) ($dados['principal'] ?? $novas->first());

        $fotoNova = $request->hasFile('foto') ? FotoDePerfil::salvar($request->file('foto')) : null;
        $fotoAntiga = $medico->foto;

        try {
            DB::transaction(function () use ($medico, $dados, $novas, $principal, $fotoNova, $request) {
                $medico->update([
                    'bio'                   => $dados['bio'] ?? null,
                    'anos_atuacao'          => (int) ($dados['anos_atuacao'] ?? 0),
                    'telefone_profissional' => $dados['telefone_profissional'] ?? null,
                ] + match (true) {
                    $fotoNova !== null                   => ['foto' => $fotoNova],
                    $request->boolean('remover_foto')    => ['foto' => null],
                    default                              => [],
                });

                $medico->especialidades()->sync($novas->mapWithKeys(fn ($id) => [$id => ['principal' => $id === $principal]])->all());
                $medico->convenios()->sync($dados['convenios'] ?? []);
            });
        } catch (\Throwable $e) {
            FotoDePerfil::apagar($fotoNova);   // a gravação falhou: a foto nova não fica órfã
            throw $e;
        }

        // A foto antiga só sai depois do commit (e nunca as fotos do seeder).
        if ($fotoNova !== null || $request->boolean('remover_foto')) {
            FotoDePerfil::apagar($fotoAntiga);
        }

        return redirect()->route('clinica.medicos')->with('sucesso', "Perfil de {$medico->nome} atualizado.");
    }

    public function desvincular(Vinculo $vinculo)
    {
        $this->authorize('delete', $vinculo);

        // O vínculo não é apagado (os preços ficam guardados): fica inativo.
        $vinculo->update(['ativo' => false]);

        return back()->with('sucesso', "{$vinculo->medico->nome} não atende mais em {$vinculo->local->nome}.");
    }
}
