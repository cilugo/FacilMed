<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Services\EstatisticasDeConsultas;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Medico;
use App\Http\Requests\Clinica\CadastrarMedicoPelaClinicaRequest;
use App\Models\Especialidade;
use App\Models\Vinculo;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    public function index()
    {
        $clinica = auth()->user()->clinica;

        return view('clinica.medicos', [
            'vinculos' => $clinica->vinculos()
                ->where('vinculos.ativo', true)
                ->with('medico.user', 'medico.especialidades', 'local', 'precos.especialidade')
                ->get()
                ->sortBy(fn ($v) => $v->medico->user->name)->values(),
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
     * A clinica cadastra o medico.
     *
     * O sistema gera SENHA TEMPORARIA e marca senha_temporaria = true;
     * o medico e obrigado a trocar no primeiro login. A clinica nunca
     * fica sabendo a senha definitiva do profissional.
     *
     * O medico entra como 'pendente' de verificacao de CRM, igual a
     * quem se cadastra sozinho - clinica nao verifica CRM.
     *
     * Se o medico JA existe no sistema (mesmo CRM+UF), nao crie outro:
     * crie so o vinculo com a unidade.
     */
    public function salvar(CadastrarMedicoPelaClinicaRequest $request)
    {
        $dados      = $request->validated();
        $existente  = $request->medicoExistente();
        $senhaTemp  = null;

        $vinculo = DB::transaction(function () use ($dados, $existente, $request, &$senhaTemp) {
            $medico = $existente;

            if (! $medico) {
                $senhaTemp = Str::password(10, symbols: false);

                $user = User::create([
                    'name'     => $dados['name'],
                    'email'    => $dados['email'],
                    'password' => $senhaTemp,
                    'tipo'     => User::TIPO_MEDICO,
                    'status'   => 'ativo',
                ]);

                $medico = Medico::create([
                    'user_id'            => $user->id,
                    'cpf'                => $dados['cpf'],
                    'crm'                => $dados['crm'],
                    'uf'                 => $dados['uf'],
                    'status_verificacao' => 'verificado',   // conferido na base simulada pelo FormRequest
                    'verificado_em'      => now(),
                    'anos_atuacao'       => 0,
                    'senha_temporaria'   => true,
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

        $nome = $vinculo->medico->user->name;
        $msg  = $existente
            ? "{$nome} já tinha conta no FacilMed e foi vinculado(a) à unidade."
            : "{$nome} cadastrado(a). CRM conferido na base simulada do FacilMed.";

        return redirect()->route('clinica.precos')
            ->with('sucesso', $msg . ' Agora defina os preços; os horários o próprio médico cadastra.')
            // Mostrada UMA vez para a clínica repassar. No primeiro login o
            // médico é obrigado a trocar (middleware ExigirTrocaDeSenha).
            ->with('senha_temporaria', $senhaTemp);
    }

    public function desvincular(Request $request, Vinculo $vinculo)
    {
        $this->authorize('delete', $vinculo);

        // O vínculo não é apagado (consultas antigas apontam para ele): fica inativo.
        $futuras = EstatisticasDeConsultas::aPartirDeAgora(
            $vinculo->consultas()->getQuery()->where('status', 'agendada')
        )->get();

        if ($futuras->isNotEmpty() && ! $request->boolean('cancelar_consultas')) {
            return back()->with('erro', "{$vinculo->medico->user->name} tem {$futuras->count()} " .
                ($futuras->count() === 1 ? 'consulta futura' : 'consultas futuras') . " em {$vinculo->local->nome}. " .
                'Confirme o desvínculo marcando "cancelar as consultas" (os pacientes são avisados).');
        }

        DB::transaction(function () use ($vinculo, $futuras, $request) {
            $futuras->each->cancelar($request->user()->id, 'O médico deixou de atender nesta unidade');
            $vinculo->update(['ativo' => false]);
        });

        return back()->with('sucesso', "{$vinculo->medico->user->name} não atende mais em {$vinculo->local->nome}.");
    }
}
