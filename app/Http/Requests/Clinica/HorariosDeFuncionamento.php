<?php

namespace App\Http\Requests\Clinica;

use App\Models\Disponibilidade;

/**
 * Regras do campo horarios[dia][abre|fecha], usadas no cadastro de
 * unidade e na edição de horários. Dia sem os dois campos = fechado.
 */
final class HorariosDeFuncionamento
{
    public static function regras(): array
    {
        $regras = [];
        foreach (Disponibilidade::DIAS as $dia) {
            $regras["horarios.$dia.abre"]  = ['nullable', 'date_format:H:i', "required_with:horarios.$dia.fecha"];
            $regras["horarios.$dia.fecha"] = ['nullable', 'date_format:H:i', "required_with:horarios.$dia.abre", "after:horarios.$dia.abre"];
        }

        return $regras;
    }

    public static function mensagens(): array
    {
        return [
            'horarios.*.fecha.after'         => 'O horário de fechar precisa ser depois do de abrir.',
            'horarios.*.abre.required_with'  => 'Preencha o horário de abrir.',
            'horarios.*.fecha.required_with' => 'Preencha o horário de fechar.',
        ];
    }

    /** Só os dias preenchidos: ['segunda' => ['abre' => '08:00', 'fecha' => '18:00'], ...] */
    public static function preenchidos(?array $horarios): array
    {
        return collect($horarios ?? [])
            ->filter(fn ($h, $dia) => in_array($dia, Disponibilidade::DIAS, true) && ! empty($h['abre']) && ! empty($h['fecha']))
            ->map(fn ($h) => ['abre' => $h['abre'], 'fecha' => $h['fecha']])
            ->all();
    }
}
