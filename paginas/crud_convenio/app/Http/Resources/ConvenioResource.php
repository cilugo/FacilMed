<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConvenioResource extends JsonResource
{
    /**
     * Formata o convênio para a resposta em JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'cnpj' => $this->cnpj,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'ativo' => $this->ativo,
            'total_medicos' => $this->whenCounted('medicos'),
            'criado_em' => $this->created_at?->format('d/m/Y H:i'),
            'atualizado_em' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
