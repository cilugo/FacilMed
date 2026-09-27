<?php

namespace App\Services;

use App\Models\BaseCarteirinha;
use App\Models\BaseCnpj;
use App\Models\BaseCrm;
use App\Support\Documento;

/**
 * Consulta às BASES SIMULADAS (decisão do grupo, 24/09/2026).
 *
 * Faz o papel do CFM, da Receita e das operadoras — que, num sistema de
 * verdade, seriam consultados por API. Aqui são tabelas do próprio
 * banco (migration 2026_09_24_000200), preenchidas pelo seeder.
 *
 * Todas as funções devolvem NULL quando está tudo certo, ou a frase de
 * erro que vai para a tela. Assim o mesmo método serve para a Rule de
 * validação e para o controller.
 *
 * ⚠ Na tela, NUNCA escreva "validado no CFM/Receita/operadora". O texto
 * correto é "conferido na base simulada do FacilMed" (AGENTS.md §6).
 */
class BaseSimulada
{
    public function conferirCrm(?string $crm, ?string $uf): ?string
    {
        $registro = BaseCrm::where('crm', Documento::digitos($crm))
            ->where('uf', mb_strtoupper((string) $uf))
            ->first();

        if (! $registro) {
            return 'Esse CRM não foi encontrado na base simulada do FacilMed para esse estado.';
        }

        if ($registro->situacao !== 'ativo') {
            return "Esse CRM está {$registro->situacao} na base simulada e não pode ser cadastrado.";
        }

        return null;
    }

    public function conferirCnpj(?string $cnpj): ?string
    {
        $registro = BaseCnpj::where('cnpj', Documento::digitos($cnpj))->first();

        if (! $registro) {
            return 'Esse CNPJ não foi encontrado na base simulada do FacilMed.';
        }

        if ($registro->situacao !== 'ativa') {
            return "Esse CNPJ está com situação \"{$registro->situacao}\" na base simulada.";
        }

        return null;
    }

    /**
     * Carteirinha: existe NESSE plano, é DESSA pessoa (CPF do
     * beneficiário), está ativa e dentro da validade.
     *
     * Devolve [erro, registro]. O registro volta junto para o controller
     * copiar a validade oficial — assim a validade gravada é a da base,
     * não a que o paciente digitou.
     *
     * @return array{0: ?string, 1: ?BaseCarteirinha}
     */
    public function conferirCarteirinha(int $planoId, ?string $numero, ?string $cpfPaciente): array
    {
        $registro = BaseCarteirinha::where('plano_id', $planoId)
            ->where('numero_carteirinha', Documento::digitos($numero))
            ->first();

        if (! $registro) {
            return ['Carteirinha não encontrada nesse plano na base simulada do convênio. Confira o número e o plano escolhido.', null];
        }

        if ($registro->beneficiario_cpf !== Documento::digitos($cpfPaciente)) {
            return ['Essa carteirinha está em nome de outra pessoa.', null];
        }

        if ($registro->situacao !== 'ativa') {
            return ['Essa carteirinha está cancelada no convênio.', null];
        }

        if ($registro->validade->isPast()) {
            return ['Essa carteirinha está vencida (validade ' . $registro->validade->format('d/m/Y') . ').', null];
        }

        return [null, $registro];
    }
}
