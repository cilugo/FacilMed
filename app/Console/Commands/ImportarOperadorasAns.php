<?php

namespace App\Console\Commands;

use App\Models\OperadoraAns;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa as operadoras de plano de saude ATIVAS a partir do CSV de
 * dados abertos da ANS.
 *
 * E a unica validacao externa REAL deste projeto: garante que nao
 * existe convenio inventado no sistema. Por isso o comando le um
 * arquivo oficial e nunca gera registro proprio.
 *
 * COMO OBTER O ARQUIVO
 * --------------------
 * Baixe o CSV de "Operadoras de planos de saude ativas" no Portal de
 * Dados Abertos (dados.gov.br, busque por "operadoras ativas ANS") e
 * salve em: database/data/operadoras_ans.csv
 *
 * USO
 * ---
 *   php artisan facilmed:importar-operadoras
 *   php artisan facilmed:importar-operadoras --arquivo=caminho/outro.csv
 *   php artisan facilmed:importar-operadoras --limite=50
 *
 * O arquivo da ANS usa ponto e virgula como separador e vem em
 * ISO-8859-1 (latin1). O comando detecta e converte.
 */
class ImportarOperadorasAns extends Command
{
    protected $signature = 'facilmed:importar-operadoras
                            {--arquivo= : Caminho do CSV (padrao: database/data/operadoras_ans.csv)}
                            {--limite=0 : Importar no maximo N operadoras (0 = todas)}';

    protected $description = 'Importa operadoras ativas do CSV de dados abertos da ANS';

    /** Nomes de coluna que a ANS ja usou, em minusculas. */
    private const COLUNAS = [
        'registro_ans'  => ['registro_ans', 'registro ans', 'registro_operadora'],
        'cnpj'          => ['cnpj'],
        'razao_social'  => ['razao_social', 'razao social'],
        'nome_fantasia' => ['nome_fantasia', 'nome fantasia'],
        'modalidade'    => ['modalidade'],
        'uf'            => ['uf'],
    ];

    public function handle(): int
    {
        $caminho = $this->option('arquivo') ?: database_path('data/operadoras_ans.csv');

        if (! is_readable($caminho)) {
            $this->error("Arquivo nao encontrado: {$caminho}");
            $this->line('');
            $this->line('Baixe o CSV de "Operadoras de planos de saude ativas" no portal de');
            $this->line('dados abertos do governo (dados.gov.br) e salve nesse caminho.');

            return self::FAILURE;
        }

        $limite = (int) $this->option('limite');
        $handle = fopen($caminho, 'r');

        // A ANS usa ponto e virgula; alguns exports usam virgula.
        $primeiraLinha = fgets($handle);
        $separador = substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',') ? ';' : ',';
        rewind($handle);

        $cabecalho = $this->normalizar(fgetcsv($handle, 0, $separador));
        $mapa = $this->mapearColunas($cabecalho);

        if ($mapa['registro_ans'] === null || $mapa['razao_social'] === null) {
            $this->error('O CSV nao tem as colunas "Registro_ANS" e "Razao_Social".');
            $this->line('Colunas encontradas: ' . implode(', ', $cabecalho));
            fclose($handle);

            return self::FAILURE;
        }

        $importadas = 0;
        $ignoradas = 0;

        DB::transaction(function () use ($handle, $separador, $mapa, $limite, &$importadas, &$ignoradas) {
            while (($linha = fgetcsv($handle, 0, $separador)) !== false) {
                if ($limite > 0 && $importadas >= $limite) {
                    break;
                }

                $registro = $this->valor($linha, $mapa['registro_ans']);

                if ($registro === '' || ! ctype_digit(preg_replace('/\D/', '', $registro))) {
                    $ignoradas++;
                    continue;
                }

                OperadoraAns::updateOrCreate(
                    ['registro_ans' => $registro],
                    [
                        'cnpj'          => $this->valor($linha, $mapa['cnpj']) ?: null,
                        'razao_social'  => $this->valor($linha, $mapa['razao_social']),
                        'nome_fantasia' => $this->valor($linha, $mapa['nome_fantasia']) ?: null,
                        'modalidade'    => $this->valor($linha, $mapa['modalidade']) ?: null,
                        'uf'            => substr($this->valor($linha, $mapa['uf']), 0, 2) ?: null,
                    ]
                );

                $importadas++;
            }
        });

        fclose($handle);

        $this->info("{$importadas} operadoras importadas. {$ignoradas} linhas ignoradas.");
        $this->line('Agora rode: php artisan db:seed --class=Database\\Seeders\\ConvenioSeeder');

        return self::SUCCESS;
    }

    private function normalizar(array $linha): array
    {
        return array_map(
            fn ($c) => strtolower(trim($this->paraUtf8((string) $c), " \t\n\r\0\x0B\"'")),
            $linha
        );
    }

    private function mapearColunas(array $cabecalho): array
    {
        $mapa = [];

        foreach (self::COLUNAS as $campo => $possiveis) {
            $mapa[$campo] = null;

            foreach ($possiveis as $nome) {
                $i = array_search($nome, $cabecalho, true);
                if ($i !== false) {
                    $mapa[$campo] = $i;
                    break;
                }
            }
        }

        return $mapa;
    }

    private function valor(array $linha, ?int $indice): string
    {
        if ($indice === null || ! isset($linha[$indice])) {
            return '';
        }

        return trim($this->paraUtf8((string) $linha[$indice]));
    }

    /** O arquivo da ANS costuma vir em ISO-8859-1. */
    private function paraUtf8(string $texto): string
    {
        return mb_check_encoding($texto, 'UTF-8')
            ? $texto
            : mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
    }
}
