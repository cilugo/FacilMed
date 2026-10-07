<?php

namespace App\Services;

use App\Models\BaseCarteirinha;
use App\Models\BaseCnpj;
use App\Models\BaseCrm;
use App\Support\Documento;
use Illuminate\Support\Str;

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
 * correto é "conferido na base simulada do PointMed" (AGENTS.md §6).
 */
class BaseSimulada
{
    /**
     * CRM: existe NESSE estado, está ativo e é DESSA pessoa (nome).
     *
     * 28/09 (3ª revisão): o nome passou a ser conferido. Antes, qualquer
     * um se cadastrava com o CRM de outra pessoa - bastava o número e o
     * estado. É a mesma ideia da carteirinha, que confere o CPF do titular.
     * Quem chama sem nome (null) confere só número, estado e situação.
     */
    public function conferirCrm(?string $crm, ?string $uf, ?string $nome = null): ?string
    {
        $registro = BaseCrm::where('crm', Documento::digitos($crm))
            ->where('uf', mb_strtoupper((string) $uf))
            ->first();

        if (! $registro) {
            return 'Esse CRM não foi encontrado na base simulada do PointMed para esse estado.';
        }

        if ($registro->situacao !== 'ativo') {
            return "Esse CRM está {$registro->situacao} na base simulada e não pode ser cadastrado.";
        }

        if (trim((string) $nome) !== '' && self::nomeComparavel($nome) !== self::nomeComparavel($registro->nome)) {
            return 'Esse CRM está registrado em nome de outra pessoa na base simulada do PointMed. '
                . 'Confira se o nome completo está igual ao do CRM.';
        }

        return null;
    }

    /**
     * Nome pronto para comparar: sem "Dr."/"Dra." no começo, sem acento,
     * sem pontuação e tudo minúsculo. "Dra. Helena Navarro" e "helena
     * navarro" são o mesmo nome; "Helena Souza" não é.
     */
    public static function nomeComparavel(?string $nome): string
    {
        $n = Str::lower(Str::ascii((string) $nome));
        $n = preg_replace('/[^a-z]+/', ' ', $n);
        $n = preg_replace('/^\s*(dr|dra|doutor|doutora)\s+/', '', $n);

        return trim(preg_replace('/\s+/', ' ', $n));
    }

    public function conferirCnpj(?string $cnpj): ?string
    {
        $registro = BaseCnpj::where('cnpj', Documento::digitos($cnpj))->first();

        if (! $registro) {
            return 'Esse CNPJ não foi encontrado na base simulada do PointMed.';
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
     * não a que o usuário digitou.
     *
     * @return array{0: ?string, 1: ?BaseCarteirinha}
     */
    public function conferirCarteirinha(int $planoId, ?string $numero, ?string $cpfUsuario): array
    {
        $registro = BaseCarteirinha::where('plano_id', $planoId)
            ->where('numero_carteirinha', Documento::digitos($numero))
            ->first();

        if (! $registro) {
            return ['Carteirinha não encontrada nesse plano na base simulada do convênio. Confira o número e o plano escolhido.', null];
        }

        if ($registro->beneficiario_cpf !== Documento::digitos($cpfUsuario)) {
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
