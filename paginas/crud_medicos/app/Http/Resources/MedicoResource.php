<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicoResource extends JsonResource
{
    /**
     * Formata o médico (dados de conta + relacionamentos) para a resposta em JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crm' => $this->crm,
            'uf_crm' => $this->uf_crm,
            'ativo' => $this->ativo,
            'nome' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'cpf' => $this->whenLoaded('user', fn () => $this->user->cpf),
            'telefone' => $this->whenLoaded('user', fn () => $this->user->telefone),
            'especialidades' => $this->whenLoaded('especialidades', fn () => $this->especialidades->pluck('nome')),
            'estabelecimentos' => $this->whenLoaded('estabelecimentos', fn () => $this->estabelecimentos->pluck('nome')),
            'convenios' => $this->whenLoaded('convenios', fn () => $this->convenios->pluck('nome')),
            'criado_em' => $this->created_at?->format('d/m/Y H:i'),
            'atualizado_em' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
