<?php

namespace Database\Seeders;

use App\Models\Feriado;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * FERIADOS NACIONAIS.
 *
 * Nao vem de arquivo nem de API: sao calculados. Os fixos estao na
 * lista abaixo; os moveis derivam todos da Pascoa, e a Pascoa e
 * calculada pelo algoritmo de Gauss (ver calcularPascoa()).
 *
 * Por que calcular em vez de digitar: uma lista digitada vence. Se
 * o TCC for apresentado em 2027 com feriados de 2026 no banco, a
 * agenda abre no Natal. O calculo funciona para qualquer ano.
 *
 * O CONSCIENCIA NEGRA (20/11) e feriado nacional desde a Lei
 * 14.759/2023 - antes disso era municipal em algumas cidades. Vale
 * conferir se nao mudou de novo antes da entrega.
 *
 * CARNAVAL e CORPUS CHRISTI entram como 'facultativo', nao como
 * nacional: legalmente sao ponto facultativo, nao feriado. Na
 * pratica quase tudo fecha, entao cada local decide se segue.
 */
class FeriadoSeeder extends Seeder
{
    /** Ano atual e os dois seguintes - cobre a janela de 180 dias com folga. */
    private const ANOS_A_GERAR = 3;

    private const FIXOS = [
        '01-01' => 'Confraternizacao Universal',
        '04-21' => 'Tiradentes',
        '05-01' => 'Dia do Trabalho',
        '09-07' => 'Independencia do Brasil',
        '10-12' => 'Nossa Senhora Aparecida',
        '11-02' => 'Finados',
        '11-15' => 'Proclamacao da Republica',
        '11-20' => 'Dia Nacional de Zumbi e da Consciencia Negra',
        '12-25' => 'Natal',
    ];

    public function run(): void
    {
        $primeiroAno = (int) date('Y');

        for ($ano = $primeiroAno; $ano < $primeiroAno + self::ANOS_A_GERAR; $ano++) {
            foreach (self::FIXOS as $diaMes => $nome) {
                $this->registrar("{$ano}-{$diaMes}", $nome, 'nacional');
            }

            $pascoa = $this->calcularPascoa($ano);

            // Sexta-feira Santa e feriado nacional por lei.
            $this->registrar(
                $pascoa->copy()->subDays(2)->toDateString(),
                'Sexta-feira Santa',
                'nacional'
            );

            // Carnaval e Corpus Christi: ponto facultativo. Cada
            // local escolhe se segue.
            $this->registrar(
                $pascoa->copy()->subDays(47)->toDateString(),
                'Carnaval',
                'facultativo'
            );

            $this->registrar(
                $pascoa->copy()->addDays(60)->toDateString(),
                'Corpus Christi',
                'facultativo'
            );
        }

        $this->command?->info('Feriados nacionais gerados ate ' . ($primeiroAno + self::ANOS_A_GERAR - 1) . '.');
    }

    private function registrar(string $data, string $nome, string $abrangencia): void
    {
        Feriado::updateOrCreate(
            ['data' => $data, 'nome' => $nome, 'cidade' => null],
            ['abrangencia' => $abrangencia, 'uf' => null, 'ativo' => true]
        );
    }

    /**
     * Domingo de Pascoa pelo algoritmo de Gauss.
     *
     * A Pascoa e o primeiro domingo depois da primeira lua cheia
     * apos o equinocio de marco - por isso a data anda todo ano, e
     * por isso Carnaval, Sexta-feira Santa e Corpus Christi andam
     * junto: os tres sao contados a partir dela.
     *
     * Nao usa a funcao easter_date() do PHP de proposito: ela
     * depende da extensao `calendar`, que nem sempre vem habilitada
     * no XAMPP - e a gente descobriria isso na hora errada.
     */
    private function calcularPascoa(int $ano): Carbon
    {
        $a = $ano % 19;
        $b = intdiv($ano, 100);
        $c = $ano % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);

        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($ano, $mes, $dia)->startOfDay();
    }
}
