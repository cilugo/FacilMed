<?php

namespace App\Support;

/**
 * Faixa de preço da consulta particular, de $ a $$$$ (01/10/2026).
 *
 * Decisão do grupo: o usuário não vê o valor exato, e sim uma faixa, como
 * as faixas de preço de restaurante do Google Maps.
 *
 * 05/10/2026: a "Tabela de preços" saiu. A CLÍNICA escolhe a faixa de cada
 * UNIDADE (locais.faixa_preco, 1 a 4) em Unidades. Os LIMITES abaixo são o
 * que cada faixa significa, mostrado para a clínica escolher e para o
 * usuário entender (ex.: $$ = R$ 200 a R$ 350).
 *
 * Para mudar os limites, mude só LIMITES: todas as telas leem daqui.
 */
final class FaixaDePreco
{
    /** [nível => valor máximo da faixa]. O último não tem teto. */
    public const LIMITES = [
        1 => 200.00,
        2 => 350.00,
        3 => 500.00,
        4 => null,
    ];

    /** Nível de 1 a 4 de um valor. Null se não há valor (só convênio). */
    public static function nivel(?float $valor): ?int
    {
        if ($valor === null) {
            return null;
        }

        foreach (self::LIMITES as $nivel => $teto) {
            if ($teto === null || $valor <= $teto) {
                return $nivel;
            }
        }

        return 4;
    }

    /**
     * Nível de um conjunto de valores (ex.: todos os preços de um local).
     * Usa a MÉDIA, como o documento de 01/10 pediu ("média de valores em $").
     */
    public static function nivelDaMedia(iterable $valores): ?int
    {
        $valores = collect($valores)->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);

        return $valores->isEmpty() ? null : self::nivel($valores->avg());
    }

    /**
     * Média de várias faixas (1 a 4), arredondada. 05/10/2026: a faixa é
     * escolhida pela clínica por unidade; o médico que atende em várias
     * unidades mostra a média delas na busca de médicos.
     */
    public static function mediaDasFaixas(iterable $niveis): ?int
    {
        $niveis = collect($niveis)->filter(fn ($n) => $n !== null && $n >= 1 && $n <= 4);

        return $niveis->isEmpty() ? null : (int) round($niveis->avg());
    }

    /** "$$" para o nível 2. */
    public static function simbolo(?int $nivel): ?string
    {
        return $nivel ? str_repeat('$', $nivel) : null;
    }

    /** "R$ 200 a R$ 350" para o nível 2; "acima de R$ 500" para o 4. */
    public static function descricao(?int $nivel): ?string
    {
        if (! $nivel || ! array_key_exists($nivel, self::LIMITES)) {
            return null;
        }

        $piso = $nivel === 1 ? null : self::LIMITES[$nivel - 1];
        $teto = self::LIMITES[$nivel];
        $r = fn (float $v) => 'R$ ' . number_format($v, 0, ',', '.');

        return match (true) {
            $piso === null => 'até ' . $r($teto),
            $teto === null => 'acima de ' . $r($piso),
            default        => $r($piso) . ' a ' . $r($teto),
        };
    }
}
