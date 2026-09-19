<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacao do POST de agendamento.
 *
 * O QUE ESTA AQUI E O QUE NAO ESTA
 * --------------------------------
 * Aqui esta so o FORMATO: os campos existem, sao do tipo certo e
 * apontam para linhas que existem no banco.
 *
 * O que NAO esta aqui, de proposito:
 *   - se o horario esta livre  -> CalculadoraDeHorarios
 *   - quanto custa             -> calculado no controller
 *   - se o medico esta ativo   -> controller
 *   - se a carteirinha e do paciente logado -> controller
 *
 * Motivo: FormRequest roda antes de qualquer coisa e nao tem o
 * contexto do agendamento inteiro. Misturar regra de negocio aqui
 * faz a mesma regra existir em dois lugares - que foi exatamente o
 * problema do prototipo antigo.
 *
 * ⚠ O CAMPO `valor` NAO EXISTE NESTA LISTA, E ISSO E INTENCIONAL.
 * No prototipo antigo o preco vinha do POST, e dava para agendar por
 * R$ 0,00 editando o HTML. Como o controller usa validated(), um
 * `valor` enviado escondido no formulario simplesmente nao chega la.
 */
class AgendarConsultaRequest extends FormRequest
{
    /** Quem garante que e paciente logado e o middleware 'tipo:paciente'. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Aceita "10:00" e "10:00:00", normaliza para "10:00".
        $this->merge([
            'horario' => substr((string) $this->input('horario'), 0, 5),
        ]);
    }

    public function rules(): array
    {
        return [
            'vinculo_id' => [
                'required', 'integer',
                Rule::exists('vinculos', 'id')->where('ativo', true),
            ],

            'especialidade_id' => [
                'required', 'integer',
                Rule::exists('especialidades', 'id')->where('ativo', true),
            ],

            // after_or_equal:today e o piso grosseiro. A antecedencia
            // real (24h) e da calculadora - aqui so barra data passada,
            // que nem faz sentido chegar no controller.
            'data_consulta' => ['required', 'date', 'after_or_equal:today'],

            'horario' => ['required', 'date_format:H:i'],

            'forma_pagamento' => ['required', Rule::in(['particular', 'convenio'])],

            // So exigido quando for convenio. Se a pessoa escolheu
            // convenio e nao mandou carteirinha, nao da para seguir.
            'paciente_plano_id' => [
                'nullable',
                'required_if:forma_pagamento,convenio',
                'integer',
                Rule::exists('paciente_planos', 'id'),
            ],

            'observacoes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'vinculo_id.required'       => 'Escolha onde a consulta vai acontecer.',
            'vinculo_id.exists'         => 'Esse local de atendimento nao esta mais disponivel.',
            'especialidade_id.required' => 'Escolha a especialidade.',
            'data_consulta.required'    => 'Escolha uma data.',
            'data_consulta.after_or_equal' => 'Nao da para marcar em data que ja passou.',
            'horario.required'          => 'Escolha um horario.',
            'horario.date_format'       => 'Horario invalido.',
            'forma_pagamento.required'  => 'Escolha como a consulta sera paga.',
            'paciente_plano_id.required_if' => 'Escolha qual carteirinha voce vai usar.',
        ];
    }
}
