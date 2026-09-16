<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Formata o usuário para a resposta em JSON.
     * A senha nunca é exposta (já está em $hidden no Model, mas reforçamos aqui).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'cpf' => $this->cpf,
            'telefone' => $this->telefone,
            'data_nascimento' => $this->data_nascimento?->format('d/m/Y'),
            'tipo_usuario' => $this->tipo_usuario,
            'ativo' => $this->ativo,
            'criado_em' => $this->created_at?->format('d/m/Y H:i'),
            'atualizado_em' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
