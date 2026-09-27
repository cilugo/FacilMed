<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Vinculo;
use App\Models\Local;
use App\Models\HorarioFuncionamento;
use App\Http\Requests\Medico\SalvarConsultorioRequest;
use Illuminate\Http\Request;

class LocalController extends Controller
{
    /**
     * Onde o medico atende.
     *
     * Dois casos:
     *  - consultorio proprio (locais.medico_id preenchido): ele cria
     *    e edita aqui;
     *  - unidade de clinica: quem vincula e a clinica. O medico so ve.
     */
    public function index()
    {
        return view('medico.locais', [
            'vinculos' => auth()->user()->medico
                ->vinculos()->with('local.clinica', 'precos')->get(),
        ]);
    }

    /** Consultório próprio + vínculo + horário de funcionamento, tudo junto. */
    public function salvar(SalvarConsultorioRequest $request)
    {
        $medico = $request->user()->medico;
        $dados  = $request->validated();

        DB::transaction(function () use ($medico, $dados) {
            $local = Local::create([
                'medico_id'   => $medico->id,
                'clinica_id'  => null,
                'nome'        => $dados['nome'],
                'tipo'        => 'consultorio',
                'cep'         => $dados['cep'],
                'endereco'    => $dados['endereco'],
                'numero'      => $dados['numero'],
                'complemento' => $dados['complemento'] ?? null,
                'bairro'      => $dados['bairro'],
                'cidade'      => $dados['cidade'],
                'uf'          => $dados['uf'],
                'telefone'    => ($dados['telefone'] ?? '') ?: null,
                'ativo'       => true,
            ]);

            $horarios = collect($dados['horarios'] ?? [])->filter(fn ($h) => ! empty($h['abre']) && ! empty($h['fecha']));
            if ($horarios->isEmpty()) {
                $horarios = collect(['segunda', 'terca', 'quarta', 'quinta', 'sexta'])
                    ->mapWithKeys(fn ($d) => [$d => ['abre' => '08:00', 'fecha' => '18:00']]);
            }
            foreach ($horarios as $dia => $h) {
                HorarioFuncionamento::create(['local_id' => $local->id, 'dia_semana' => $dia, 'abre' => $h['abre'], 'fecha' => $h['fecha']]);
            }

            Vinculo::create([
                'medico_id'         => $medico->id,
                'local_id'          => $local->id,
                'aceita_particular' => true,
                'aceita_convenio'   => (bool) ($dados['aceita_convenio'] ?? false),
                'ativo'             => true,
            ]);
        });

        return redirect()->route('medico.precos')->with('sucesso',
            'Consultório cadastrado. Agora defina os preços e depois os seus horários de atendimento.');
    }
}
