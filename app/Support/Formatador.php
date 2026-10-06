<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Funções pequenas de formatação usadas pelos painéis.
 *
 * POR QUE ISSO EXISTE
 * -------------------
 * Tela não é lugar de regra (AGENTS.md §7). Então o controller entrega
 * pro Blade valores JÁ FORMATADOS ("1.248", "Terça-feira, 11 de agosto
 * de 2026", "+12%") e o Blade só imprime. Assim a mesma formatação vale
 * para os quatro painéis e mudar o jeito de mostrar uma data é mexer em
 * um arquivo só.
 *
 * As datas e os meses são escritos à mão de propósito: não dependem de
 * o locale do Laravel estar em pt_BR nem da extensão intl do PHP.
 */
final class Formatador
{
    public const MESES = [
        'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    public const MESES_CURTOS = [
        'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
        'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez',
    ];

    // Índice = Carbon::dayOfWeek (0 = domingo), o mesmo de HorarioFuncionamento::DIAS.
    public const DIAS = [
        'Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira',
        'Quinta-feira', 'Sexta-feira', 'Sábado',
    ];

    public const DIAS_CURTOS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

    /** Paleta dos gráficos: azul, laranja, verde, roxo, azul-claro, cinza. */
    public const CORES = [
        '#1f6feb', '#f6a23c', '#19b58a', '#7c5cf0', '#6aa8f7', '#a9bcd4',
    ];

    public const PAPEIS = [
        'usuario' => 'Usuário',
        'clinica'  => 'Clínica',
        'admin'    => 'Administrador',
    ];

    public static function numero(int|float $n, int $casas = 0): string
    {
        return number_format($n, $casas, ',', '.');
    }

    /** "Terça-feira, 11 de agosto de 2026" */
    public static function dataExtensa(CarbonInterface $d): string
    {
        return self::DIAS[$d->dayOfWeek]
            . ', ' . $d->day
            . ' de ' . self::MESES[$d->month - 1]
            . ' de ' . $d->year;
    }

    /** "11/08/2026" */
    public static function dataCurta(CarbonInterface $d): string
    {
        return $d->format('d/m/Y');
    }

    /** "09:00:00" (coluna TIME) vira "09:00". */
    /** (12) 3921-1000 / (12) 99999-9999. Guarda-se só dígito; formata na tela. */
    public static function telefone(?string $valor): string
    {
        $d = preg_replace('/\D/', '', (string) $valor);

        return match (strlen($d)) {
            10 => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6)),
            11 => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7)),
            default => (string) $valor,
        };
    }

    public static function hora(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '--:--';
    }

    public static function cor(int $posicao): string
    {
        return self::CORES[$posicao % count(self::CORES)];
    }

    /** Cores dos avatares com iniciais (as mesmas da home do grupo). Todas com contraste para texto branco. */
    public const CORES_AVATAR = ['#1b9ce4', '#183e9f', '#1a8a9a', '#155b80', '#2f6fd0'];

    public static function corAvatar(int $posicao): string
    {
        return self::CORES_AVATAR[$posicao % count(self::CORES_AVATAR)];
    }

    /**
     * Variação entre dois períodos, pronta para o card.
     *
     * @param bool $maisEhBom  true: subir é bom (consultas realizadas);
     *                         false: subir é ruim (cancelamentos, faltas).
     *                         Decide só a COR — a seta sempre acompanha o número.
     *
     * Retorna null quando não há base de comparação (período anterior
     * zerado): mostrar "+100%" ou "+∞%" seria número sem sentido.
     */
    public static function variacao(int $atual, int $anterior, bool $maisEhBom = true): ?array
    {
        if ($anterior === 0) {
            return null;
        }

        $pct = (int) round((($atual - $anterior) / $anterior) * 100);

        if ($pct === 0) {
            return ['sentido' => 'flat', 'texto' => '0%', 'tom' => 'neutro'];
        }

        $subiu = $pct > 0;

        return [
            'sentido' => $subiu ? 'up' : 'down',
            'texto'   => ($subiu ? '+' : '') . $pct . '%',
            'tom'     => ($subiu === $maisEhBom) ? 'bom' : 'ruim',
        ];
    }

    /** Diferença absoluta entre dois números ("+2"). O "em relação a..." vai na nota do card. */
    public static function diferenca(int $atual, int $anterior, bool $maisEhBom = true): array
    {
        $dif = $atual - $anterior;

        if ($dif === 0) {
            return ['sentido' => 'flat', 'texto' => '0', 'tom' => 'neutro'];
        }

        $subiu = $dif > 0;

        return [
            'sentido' => $subiu ? 'up' : 'down',
            'texto'   => ($subiu ? '+' : '') . $dif,
            'tom'     => ($subiu === $maisEhBom) ? 'bom' : 'ruim',
        ];
    }

    /**
     * "Dra. Helena Navarro" -> "Dra. Helena"; "Ana Souza" -> "Ana".
     * O nome do médico já vem com o tratamento no cadastro.
     */
    public static function saudacao(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome)) ?: [];

        if ($partes === [] || $partes[0] === '') {
            return '';
        }

        $ehTratamento = in_array(mb_strtolower($partes[0]), ['dr.', 'dra.', 'dr', 'dra'], true);

        return ($ehTratamento && isset($partes[1]))
            ? $partes[0] . ' ' . $partes[1]
            : $partes[0];
    }

    /** Iniciais para o avatar: "Dra. Helena Navarro" -> "HN". */
    /**
     * "Ana Beatriz Lima" → "Ana L." Usado nas avaliações públicas (05/10/2026):
     * quem lê sabe que é uma pessoa real, sem o nome completo exposto.
     */
    public static function nomeCurto(?string $nome): string
    {
        $partes = preg_split('/\s+/', trim((string) $nome), -1, PREG_SPLIT_NO_EMPTY);

        if (! $partes || $nome === 'Conta excluída') {
            return 'Usuário';
        }

        return count($partes) === 1 ? $partes[0] : $partes[0] . ' ' . mb_substr(end($partes), 0, 1) . '.';
    }

    public static function iniciais(string $nome): string
    {
        $partes = array_values(array_filter(
            preg_split('/\s+/', trim($nome)) ?: [],
            fn ($p) => $p !== '' && ! in_array(mb_strtolower($p), ['dr.', 'dra.', 'dr', 'dra'], true)
        ));

        if ($partes === []) {
            return '?';
        }

        $primeira = mb_substr($partes[0], 0, 1);
        $ultima   = count($partes) > 1 ? mb_substr($partes[count($partes) - 1], 0, 1) : '';

        return mb_strtoupper($primeira . $ultima);
    }

    /** Rótulo e cor da carteirinha do plano de saúde. */
    public static function statusCarteirinha(string $status): array
    {
        return match ($status) {
            'ativa'    => ['rotulo' => 'Ativa',          'tom' => 'verde'],
            'pendente' => ['rotulo' => 'Em conferência', 'tom' => 'ambar'],
            'recusada' => ['rotulo' => 'Recusada',       'tom' => 'rosa'],
            default    => ['rotulo' => ucfirst($status), 'tom' => 'cinza'],
        };
    }

    /**
     * Atalhos do painel = os mesmos itens do menu (config/navegacao.php),
     * menos o "Início". Um lugar só para mudar: se o menu muda, os atalhos
     * acompanham, e nenhum atalho aponta para rota que não existe.
     */
    public static function atalhos(string $tipo): array
    {
        $itens = [];

        foreach (config('navegacao.' . $tipo, []) as $item) {
            if (str_ends_with($item['rota'], '.dashboard') || ! \Illuminate\Support\Facades\Route::has($item['rota'])) {
                continue;
            }

            $itens[] = [
                'icone'  => $item['icone'],
                'rotulo' => $item['label'],
                'url'    => route($item['rota']),
            ];
        }

        return $itens;
    }
}
