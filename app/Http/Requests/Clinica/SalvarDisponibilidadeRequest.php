<?php

namespace App\Http\Requests\Medico;

use App\Models\Disponibilidade;
use App\Models\HorarioFuncionamento;
use App\Models\Vinculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Bloco de atendimento recorrente (ex.: toda segunda, 08:00-12:00, consultas de 30 min).
 *
 * Regras (24/09):
 * - o vínculo é DESTE médico e está ativo;
 * - fim depois do início, e cabe pelo menos uma consulta;
 * - o bloco cabe no horário de funcionamento do local naquele dia;
 * - não se sobrepõe a outro bloco do médico no mesmo dia — nem no mesmo
 *   endereço nem em OUTRO (ninguém está em dois lugares ao mesmo tempo).
 *
 * Almoço não tem campo: são dois blocos (08-12 e 14-18) e o buraco é o almoço.
 */
class SalvarDisponibilidadeRequest extends FormRequest
{
    public const DURACOES = [15, 20, 30, 40, 45, 60];

    public function authorize(): bool
    {
        return $this->user()?->ehMedico() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'hora_inicio' => substr((string) $this->input('hora_inicio'), 0, 5),
            'hora_fim'    => substr((string) $this->input('hora_fim'), 0, 5),
        ]);
    }

    public function rules(): array
    {
        return [
            'vinculo_id' => ['required', 'integer', Rule::exists('vinculos', 'id')
                ->where('medico_id', $this->user()->medico?->id)->where('ativo', true)],
            'dia_semana' => ['required', Rule::in(Disponibilidade::DIAS)],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim'    => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracao_consulta_minutos' => ['required', 'integer', Rule::in(self::DURACOES)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $inicio = $this->input('hora_inicio');
            $fim    = $this->input('hora_fim');
            $dia    = $this->input('dia_semana');
            $vinculo = Vinculo::with('local')->find($this->input('vinculo_id'));

            $minutos = (strtotime($fim) - strtotime($inicio)) / 60;
            if ($minutos < (int) $this->input('duracao_consulta_minutos')) {
                $v->errors()->add('hora_fim', 'O bloco é menor que a duração de uma consulta.');
                return;
            }

            $funcionamento = HorarioFuncionamento::where('local_id', $vinculo->local_id)->where('dia_semana', $dia)->first();
            if (! $funcionamento) {
                $v->errors()->add('dia_semana', "{$vinculo->local->nome} não abre nesse dia da semana.");
                return;
            }
            if ($inicio < substr($funcionamento->abre, 0, 5) || $fim > substr($funcionamento->fecha, 0, 5)) {
                $v->errors()->add('hora_inicio', sprintf('%s funciona das %s às %s nesse dia.',
                    $vinculo->local->nome, substr($funcionamento->abre, 0, 5), substr($funcionamento->fecha, 0, 5)));
                return;
            }

            $choque = Disponibilidade::query()
                ->whereIn('vinculo_id', Vinculo::where('medico_id', $vinculo->medico_id)->select('id'))
                ->where('dia_semana', $dia)
                ->where('ativo', true)
                ->where('hora_inicio', '<', $fim . ':00')
                ->where('hora_fim', '>', $inicio . ':00')
                ->with('vinculo.local')
                ->first();

            if ($choque) {
                $v->errors()->add('hora_inicio', sprintf('Choca com o bloco das %s às %s em %s.',
                    substr($choque->hora_inicio, 0, 5), substr($choque->hora_fim, 0, 5), $choque->vinculo->local->nome));
            }
        }];
    }

    public function messages(): array
    {
        return [
            'vinculo_id.exists' => 'Escolha um dos lugares onde você atende.',
            'hora_fim.after'    => 'O fim precisa ser depois do início.',
            'duracao_consulta_minutos.in' => 'Escolha uma duração da lista.',
        ];
    }

    public function attributes(): array
    {
        return ['dia_semana' => 'dia da semana', 'hora_inicio' => 'início', 'hora_fim' => 'fim', 'duracao_consulta_minutos' => 'duração'];
    }
}
