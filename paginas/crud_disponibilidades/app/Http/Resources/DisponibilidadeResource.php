<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisponibilidadeResource extends JsonResource
{
    /**
     * Formata a disponibilidade para a resposta em JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medico' => $this->whenLoaded('medico', fn () => [
                'id' => $this->medico->id,
                'nome' => $this->medico->user?->name,
            ]),
            'estabelecimento' => $this->whenLoaded('estabelecimento', fn () => $this->estabelecimento?->nome),
            'dia_semana' => $this->dia_semana,
            'data' => $this->data?->format('d/m/Y'),
            'hora_inicio' => $this->hora_inicio,
            'hora_fim' => $this->hora_fim,
            'duracao_consulta_minutos' => $this->duracao_consulta_minutos,
            'tipo_atendimento' => $this->tipo_atendimento,
            'convenio' => $this->whenLoaded('convenio', fn () => $this->convenio?->nome),
            'bloqueado' => $this->bloqueado,
            'motivo_bloqueio' => $this->motivo_bloqueio,
            'ativo' => $this->ativo,
            'criado_em' => $this->created_at?->format('d/m/Y H:i'),
            'atualizado_em' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
