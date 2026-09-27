<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Services\EstatisticasDeConsultas;
use App\Models\Consulta;
use App\Models\Especialidade;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class EspecialidadeController extends Controller
{
    public function index()
    {
        return view('admin.especialidades', [
            'especialidades' => Especialidade::withCount('medicos')->orderBy('nome')->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        $dados = $request->validate([
            'nome'     => ['required', 'string', 'max:100', 'unique:especialidades,nome'],
            'icone'    => ['nullable', 'string', 'max:60'],
            'destaque' => ['boolean'],
        ]);

        Especialidade::create([...$dados, 'slug' => Str::slug($dados['nome']), 'ativo' => true]);

        return back()->with('sucesso', 'Especialidade criada.');
    }

    /**
     * `destaque` controla os cards da home. Marcar aqui faz o card
     * aparecer na home na hora - sem mexer em codigo.
     */
    public function atualizar(Request $request, Especialidade $especialidade)
    {
        $dados = $request->validate([
            'nome'     => ['required', 'string', 'max:100', Rule::unique('especialidades', 'nome')->ignore($especialidade->id)],
            'icone'    => ['nullable', 'string', 'max:60'],
            'destaque' => ['nullable', 'boolean'],
            'ativo'    => ['nullable', 'boolean'],
        ]);

        $ativo = $request->has('ativo') ? $request->boolean('ativo') : $especialidade->ativo;

        // Desativar com consulta futura marcada deixaria o paciente com uma
        // consulta de especialidade que "não existe" mais.
        if ($especialidade->ativo && ! $ativo) {
            $futuras = EstatisticasDeConsultas::aPartirDeAgora(
                Consulta::query()->where('especialidade_id', $especialidade->id)->where('status', 'agendada')
            )->count();

            if ($futuras > 0) {
                return back()->with('erro', "Não dá para desativar {$especialidade->nome}: há {$futuras} " .
                    ($futuras === 1 ? 'consulta futura marcada' : 'consultas futuras marcadas') . '.');
            }
        }

        // O slug NÃO muda: é o que está nos links (/buscar?especialidade=...).
        $especialidade->update([
            'nome'     => $dados['nome'],
            'icone'    => $dados['icone'] ?? $especialidade->icone,
            'destaque' => $request->has('destaque') ? $request->boolean('destaque') : $especialidade->destaque,
            'ativo'    => $ativo,
        ]);

        return back()->with('sucesso', "Especialidade {$especialidade->nome} atualizada.");
    }
}
