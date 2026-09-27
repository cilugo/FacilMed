<?php

namespace App\Services;

use App\Mail\AvisoDeConsulta;
use App\Models\Consulta;
use App\Models\NotificacaoEnviada;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * TODO e-mail do FacilMed sai por aqui (24/09/2026).
 *
 * Regra do AGENTS.md §6: nenhum e-mail sai sem gravar em
 * notificacoes_enviadas. A linha é gravada ANTES de enviar: o
 * UNIQUE (consulta_id, tipo) funciona como "trava" — se duas execuções
 * tentarem o mesmo aviso ao mesmo tempo, só uma consegue gravar e só
 * ela envia. Depois do envio, a linha recebe sucesso/erro.
 *
 * Em desenvolvimento MAIL_MAILER=log: o e-mail cai em
 * storage/logs/laravel.log e nada sai da máquina (AGENTS.md §7.1).
 *
 * Tipos (enum da tabela): confirmacao, lembrete_24h, cancelamento, remarcacao.
 */
class Notificador
{
    public const TIPOS = ['confirmacao', 'lembrete_24h', 'cancelamento', 'remarcacao'];

    /**
     * @param  'paciente'|'medico'  $para
     * @return bool true se enviou agora; false se já tinha sido enviado ou falhou.
     */
    public function enviar(Consulta $consulta, string $tipo, string $para = 'paciente'): bool
    {
        $consulta->loadMissing('paciente.user', 'medico.user', 'especialidade', 'vinculo.local');

        $destinatario = $para === 'medico' ? $consulta->medico->user : $consulta->paciente->user;

        if (! $destinatario?->email) {
            return false;
        }

        try {
            $registro = NotificacaoEnviada::create([
                'consulta_id'  => $consulta->id,
                'tipo'         => $tipo,
                'destinatario' => $destinatario->email,
                'sucesso'      => false,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return false;   // já enviado (ou sendo enviado agora): o UNIQUE barrou
            }
            throw $e;
        }

        try {
            Mail::to($destinatario->email, $destinatario->name)
                ->send(new AvisoDeConsulta($consulta, $tipo, $para));

            $registro->update(['sucesso' => true, 'enviada_em' => now()]);

            return true;
        } catch (Throwable $e) {
            // Falha de envio NÃO derruba a ação do usuário (a consulta já foi
            // gravada/cancelada). Fica registrada para alguém olhar.
            $registro->update(['erro' => mb_substr($e->getMessage(), 0, 1000)]);
            Log::warning("E-mail '{$tipo}' da consulta {$consulta->id} falhou: {$e->getMessage()}");

            return false;
        }
    }
}
