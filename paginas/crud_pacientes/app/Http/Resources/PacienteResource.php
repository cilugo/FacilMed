<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PacienteResource extends JsonResource
{
    /**
     * Formata o paciente (junto com os dados de conta) para a resposta em JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_carteirinha' => $this->numero_carteirinha,
            'nome' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'cpf' => $this->whenLoaded('user', fn () => $this->user->cpf),
            'telefone' => $this->whenLoaded('user', fn () => $this->user->telefone),
            'data_nascimento' => $this->whenLoaded('user', fn () => $this->user->data_nascimento?->format('d/m/Y')),
            'ativo' => $this->whenLoaded('user', fn () => $this->user->ativo),
            'criado_em' => $this->created_at?->format('d/m/Y H:i'),
            'atualizado_em' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
