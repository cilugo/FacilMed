<?php

namespace Database\Seeders;

use App\Models\Convenio;
use App\Models\Disponibilidade;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MedicoSeeder extends Seeder
{
    /**
     * MEDICOS FICTICIOS. CRMs inventados - nenhum destes numeros
     * corresponde a profissional real. Por isso a maioria entra
     * como 'verificado' apenas para a demonstracao funcionar; num
     * sistema real, so o admin aprova depois de conferir no CFM.
     *
     * Reparem no Dr. Rafael Moreira: ele atende em DUAS clinicas,
     * com DUAS especialidades, e o preco muda em cada combinacao.
     * E o caso que justifica a tabela `precos`.
     */
    private const MEDICOS = [
        [
            'nome' => 'Dra. Helena Navarro', 'email' => 'helena@facilmed.test',
            'crm' => '112233', 'uf' => 'SP', 'status' => 'verificado', 'anos' => 14,
            'bio' => 'Cardiologista com atuacao em prevencao e acompanhamento de hipertensao.',
            'especialidades' => ['Cardiologia'],
            'unidades' => [
                ['Vida Plena - Centro',   ['Cardiologia' => 380.00]],
                ['Vida Plena - Aquarius', ['Cardiologia' => 420.00]],
            ],
            'convenios' => 2,
        ],
        [
            'nome' => 'Dr. Rafael Moreira', 'email' => 'rafael@facilmed.test',
            'crm' => '223344', 'uf' => 'SP', 'status' => 'verificado', 'anos' => 9,
            'bio' => 'Clinico geral e dermatologista, atendimento adulto.',
            'especialidades' => ['Clinica Geral', 'Dermatologia'],
            'unidades' => [
                // Mesma pessoa, precos diferentes por especialidade E por local.
                ['Vida Plena - Centro', ['Clinica Geral' => 220.00, 'Dermatologia' => 340.00]],
                ['Aurora - Vila Ema',   ['Clinica Geral' => 260.00, 'Dermatologia' => 390.00]],
            ],
            'convenios' => 3,
        ],
        [
            'nome' => 'Dra. Camila Reis', 'email' => 'camila@facilmed.test',
            'crm' => '334455', 'uf' => 'SP', 'status' => 'verificado', 'anos' => 18,
            'bio' => 'Pediatra, atendimento de recem-nascidos a adolescentes.',
            'especialidades' => ['Pediatria'],
            'unidades' => [['Aurora - Vila Ema', ['Pediatria' => 300.00]]],
            'convenios' => 2,
        ],
        [
            'nome' => 'Dr. Paulo Yamada', 'email' => 'paulo@facilmed.test',
            'crm' => '445566', 'uf' => 'SP', 'status' => 'verificado', 'anos' => 22,
            'bio' => 'Ortopedista, com foco em joelho e quadril.',
            'especialidades' => ['Ortopedia'],
            'unidades' => [['Santa Clara - Taubate', ['Ortopedia' => 450.00]]],
            'convenios' => 1,
        ],
        [
            'nome' => 'Dra. Beatriz Fontes', 'email' => 'beatriz@facilmed.test',
            'crm' => '556677', 'uf' => 'SP', 'status' => 'verificado', 'anos' => 11,
            'bio' => 'Ginecologista e obstetra.',
            'especialidades' => ['Ginecologia'],
            'unidades' => [['Santa Clara - Taubate', ['Ginecologia' => 350.00]]],
            'convenios' => 2,
        ],
        [
            // PENDENTE de proposito: serve para demonstrar que medico
            // nao verificado NAO aparece na busca (AGENTS.md secao 6).
            'nome' => 'Dr. Andre Loureiro', 'email' => 'andre@facilmed.test',
            'crm' => '667788', 'uf' => 'SP', 'status' => 'pendente', 'anos' => 3,
            'bio' => 'Neurologista.',
            'especialidades' => ['Neurologia'],
            'unidades' => [['Aurora - Vila Ema', ['Neurologia' => 400.00]]],
            'convenios' => 0,
        ],
    ];

    public function run(): void
    {
        $convenios = Convenio::orderBy('id')->get();

        foreach (self::MEDICOS as $d) {
            $user = User::updateOrCreate(
                ['email' => $d['email']],
                [
                    'name'     => $d['nome'],
                    'password' => Hash::make('facilmed2026'),
                    'tipo'     => User::TIPO_MEDICO,
                    'telefone' => '(12) 99' . rand(100, 999) . '-' . rand(1000, 9999),
                    'status'   => 'ativo',
                ]
            );

            $medico = Medico::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cpf'                   => $this->cpfFicticio(),
                    'crm'                   => $d['crm'],
                    'uf'                    => $d['uf'],
                    'status_verificacao'    => $d['status'],
                    'verificado_em'         => $d['status'] === 'verificado' ? now() : null,
                    'bio'                   => $d['bio'],
                    'telefone_profissional' => '(12) 3' . rand(100, 999) . '-' . rand(1000, 9999),
                    'anos_atuacao'          => $d['anos'],
                ]
            );

            $ids = Especialidade::whereIn('nome', $d['especialidades'])->pluck('id', 'nome');
            $medico->especialidades()->sync(
                $ids->mapWithKeys(fn ($id, $nome) => [$id => ['principal' => $nome === $d['especialidades'][0]]])->all()
            );

            if ($d['convenios'] > 0 && $convenios->isNotEmpty()) {
                $medico->convenios()->sync($convenios->take($d['convenios'])->pluck('id'));
            }

            foreach ($d['unidades'] as [$nomeLocal, $tabelaPrecos]) {
                $local = Local::where('nome', $nomeLocal)->first();
                if (! $local) {
                    continue;
                }

                $vinculo = Vinculo::updateOrCreate(
                    ['medico_id' => $medico->id, 'local_id' => $local->id],
                    [
                        'aceita_particular' => true,
                        'aceita_convenio'   => $d['convenios'] > 0,
                        'ativo'             => true,
                    ]
                );

                foreach ($tabelaPrecos as $esp => $valor) {
                    Preco::updateOrCreate(
                        ['vinculo_id' => $vinculo->id, 'especialidade_id' => $ids[$esp]],
                        ['valor' => $valor, 'ativo' => true]
                    );
                }

                // Manha e tarde em blocos separados: o buraco entre
                // 12h e 14h E o horario de almoco. Sem tabela extra.
                foreach (['segunda', 'terca', 'quarta', 'quinta', 'sexta'] as $dia) {
                    Disponibilidade::updateOrCreate(
                        ['vinculo_id' => $vinculo->id, 'dia_semana' => $dia, 'hora_inicio' => '08:00'],
                        ['hora_fim' => '12:00', 'duracao_consulta_minutos' => 30, 'ativo' => true]
                    );
                    Disponibilidade::updateOrCreate(
                        ['vinculo_id' => $vinculo->id, 'dia_semana' => $dia, 'hora_inicio' => '14:00'],
                        ['hora_fim' => '18:00', 'duracao_consulta_minutos' => 30, 'ativo' => true]
                    );
                }
            }
        }

        $this->command->info('6 medicos ficticios (1 pendente, de proposito). Senha: facilmed2026');
    }

    /** CPF ficticio - NAO passa em validacao de digito verificador. */
    private function cpfFicticio(): string
    {
        return sprintf('%03d.%03d.%03d-%02d', rand(100, 999), rand(100, 999), rand(100, 999), rand(10, 99));
    }
}
