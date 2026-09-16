<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdministradorResource extends JsonResource
{
    /**
     * Formata o administrador para a resposta em JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cargo' => $this->cargo,
            'ativo' => $this->ativo,
            'nome' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'cpf' => $this->whenLoaded('user', fn () => $this->user->cpf),
            'telefone' => $this->whenLoaded('user', fn () => $this->user->telefone),
            'criado_em' => $this->created_at?->format('d/m/Y H:i'),
            'atualizado_em' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
