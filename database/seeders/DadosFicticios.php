<?php

namespace Database\Seeders;

/**
 * TODOS OS DADOS FICTÍCIOS DO FACILMED EM UM LUGAR SÓ (24/09/2026).
 *
 * Decisão do grupo: 3 médicos, 3 clínicas, 3 hospitais, 2 pacientes,
 * com CPFs, CNPJs e CRMs que PASSAM nas regras de validação (dígito
 * verificador correto) e que existem nas BASES SIMULADAS.
 *
 * Os seeders (BaseSimulada, Clinica, Medico, Paciente) leem daqui.
 * Mudou um dado? Mude AQUI — assim o médico do seed e o registro dele
 * no "CFM simulado" nunca ficam diferentes.
 *
 * Documentos só com dígitos (convenção dos FormRequests). A máscara é
 * só na tela (App\Support\Documento).
 *
 * ⚠ Como o dígito verificador é válido, um desses números PODE, por
 * coincidência, pertencer a alguém de verdade. Nunca use estes dados
 * fora do ambiente de desenvolvimento/apresentação.
 *
 * Senha de todas as contas: facilmed2026
 */
final class DadosFicticios
{
    public const SENHA = 'facilmed2026';

    /**
     * 3 clínicas + 3 hospitais. Cada um é uma conta de "clínica" com UMA
     * unidade; o que muda é o tipo da unidade (clinica/hospital).
     * [email, razao_social, nome_fantasia, cnpj, telefone, descricao, unidade]
     * unidade = [nome, tipo, cep, endereco, numero, bairro, cidade, uf]
     */
    public const ESTABELECIMENTOS = [
        ['contato@clinicaspsaude.test', 'SpSaúde Serviços Médicos LTDA', 'Clínica SpSaúde', '41100001000195', '1239504000',
            'Rede própria do convênio SpSaúde, com atendimento de especialidades.',
            ['SpSaúde - Jardim Satélite', 'clinica', '12230000', 'Av. Andrômeda', '2000', 'Jardim Satélite', 'São José dos Campos', 'SP']],
        ['contato@vidaplena.test', 'Vida Plena Serviços Médicos LTDA', 'Clínica Vida Plena', '41100002000130', '1239211000',
            'Clínica de bairro com foco em atendimento de rotina e acompanhamento.',
            ['Vida Plena - Centro', 'clinica', '12210100', 'Rua Quinze de Novembro', '480', 'Centro', 'São José dos Campos', 'SP']],
        ['contato@aurora.test', 'Centro Médico Aurora S/A', 'Centro Médico Aurora', '41100003000184', '1239452000',
            'Centro médico com corpo clínico multidisciplinar e agenda estendida.',
            ['Aurora - Vila Ema', 'clinica', '12243700', 'Rua República do Iraque', '90', 'Vila Ema', 'São José dos Campos', 'SP']],
        ['contato@santaclara.test', 'Hospital Santa Clara de Taubaté LTDA', 'Hospital Santa Clara', '42200001000120', '1236323000',
            'Hospital geral com ambulatório de especialidades.',
            ['Santa Clara - Taubaté', 'hospital', '12020270', 'Av. Tiradentes', '1500', 'Centro', 'Taubaté', 'SP']],
        ['contato@saolucasdovale.test', 'Hospital São Lucas do Vale LTDA', 'Hospital São Lucas do Vale', '42200002000174', '1239531000',
            'Hospital com pronto atendimento e ambulatório.',
            ['São Lucas - Jacareí', 'hospital', '12307000', 'Av. Siqueira Campos', '800', 'Centro', 'Jacareí', 'SP']],
        ['contato@hospitalesperanca.test', 'Associação Hospitalar Esperança', 'Hospital Esperança', '42200003000119', '1236524000',
            'Hospital filantrópico com ambulatório pediátrico.',
            ['Esperança - Caçapava', 'hospital', '12280000', 'Rua Capitão Carlos de Moura', '300', 'Centro', 'Caçapava', 'SP']],
    ];

    /**
     * 3 médicos. Cada um atende em DOIS lugares: de MANHÃ no primeiro,
     * à TARDE no segundo (a mesma pessoa não pode estar em dois lugares
     * ao mesmo tempo). Preço por unidade E por especialidade.
     */
    public const MEDICOS = [
        [
            'nome' => 'Dra. Helena Navarro', 'email' => 'helena@facilmed.test', 'cpf' => '70120130106',
            'crm' => '112233', 'uf' => 'SP', 'anos' => 14,
            'bio' => 'Cardiologista com atuação em prevenção e acompanhamento de hipertensão.',
            'especialidades' => ['Cardiologia', 'Clínica Geral'],
            'unidades' => [
                ['Vida Plena - Centro',   'manha', ['Cardiologia' => 380.00, 'Clínica Geral' => 220.00]],
                ['Santa Clara - Taubaté', 'tarde', ['Cardiologia' => 450.00]],
            ],
            'convenios' => ['SpSaúde', 'Horizonte Med'],
        ],
        [
            'nome' => 'Dr. Rafael Moreira', 'email' => 'rafael@facilmed.test', 'cpf' => '70120130289',
            'crm' => '223344', 'uf' => 'SP', 'anos' => 9,
            'bio' => 'Dermatologista e clínico geral, atendimento adulto.',
            'especialidades' => ['Dermatologia', 'Clínica Geral'],
            'unidades' => [
                ['SpSaúde - Jardim Satélite', 'manha', ['Dermatologia' => 340.00, 'Clínica Geral' => 200.00]],
                ['São Lucas - Jacareí',       'tarde', ['Dermatologia' => 390.00]],
            ],
            'convenios' => ['SpSaúde', 'Bem Viver Saúde'],
        ],
        [
            'nome' => 'Dra. Camila Reis', 'email' => 'camila@facilmed.test', 'cpf' => '70120130360',
            'crm' => '334455', 'uf' => 'SP', 'anos' => 18,
            'bio' => 'Pediatra, atendimento de recém-nascidos a adolescentes.',
            'especialidades' => ['Pediatria'],
            'unidades' => [
                ['Aurora - Vila Ema',    'manha', ['Pediatria' => 300.00]],
                ['Esperança - Caçapava', 'tarde', ['Pediatria' => 280.00]],
            ],
            'convenios' => ['Horizonte Med', 'Bem Viver Saúde'],
        ],
    ];

    /** Turnos: o intervalo 12h-14h fica livre (almoço/deslocamento). */
    public const TURNOS = [
        'manha' => ['08:00', '12:00'],
        'tarde' => ['14:00', '18:00'],
    ];

    /**
     * 2 pacientes. 'carteirinha' = a que JÁ vem cadastrada na conta.
     * O Marcos entra sem nenhuma, de propósito: é com ele que se testa
     * o cadastro de carteirinha (ver CARTEIRINHAS abaixo).
     */
    public const PACIENTES = [
        ['nome' => 'Ana Beatriz Lima', 'email' => 'ana@facilmed.test', 'cpf' => '80230140130',
            'nascimento' => '1992-04-17', 'sexo' => 'Feminino', 'telefone' => '12982001000',
            'carteirinha' => '100000000001'],
        ['nome' => 'Marcos Vinicius Alves', 'email' => 'marcos@facilmed.test', 'cpf' => '80230140211',
            'nascimento' => '1985-11-02', 'sexo' => 'Masculino', 'telefone' => '12982001001',
            'carteirinha' => null],
    ];

    // =================================================================
    // BASES SIMULADAS — incluem registros "errados" de propósito, para
    // mostrar na apresentação que o sistema RECUSA o que não bate.
    // =================================================================

    /** "CFM simulado": [crm, uf, nome, situacao, para que serve] */
    public const CRMS = [
        ['112233', 'SP', 'Helena Navarro',  'ativo',    'médica do seed'],
        ['223344', 'SP', 'Rafael Moreira',  'ativo',    'médico do seed'],
        ['334455', 'SP', 'Camila Reis',     'ativo',    'médica do seed'],
        ['445566', 'SP', 'Paulo Yamada',    'ativo',    'LIVRE: testar cadastro de médico que dá certo'],
        ['998877', 'SP', 'Carlos Menezes',  'cassado',  'testar recusa: CRM cassado'],
        ['556677', 'RJ', 'Beatriz Fontes',  'suspenso', 'testar recusa: CRM suspenso'],
    ];

    /** "Receita simulada": [cnpj, razao_social, nome_fantasia, situacao, para que serve] */
    public const CNPJS_EXTRAS = [
        ['43300001000164', 'Bem Estar Jacareí Clínica Médica LTDA', 'Clínica Bem Estar', 'ativa',   'LIVRE: testar cadastro de clínica que dá certo'],
        ['43300002000109', 'Horizonte Azul Serviços de Saúde LTDA', 'Clínica Horizonte Azul', 'baixada', 'testar recusa: CNPJ baixado'],
    ];

    /**
     * "Operadora simulada": [plano, numero, beneficiario, cpf, validade (em meses a partir de hoje), situacao, para que serve]
     */
    public const CARTEIRINHAS = [
        ['SpSaúde Família',       '100000000001', 'Ana Beatriz Lima',      '80230140130', 12, 'ativa',     'já cadastrada na conta da Ana'],
        ['Horizonte Empresarial', '200000000006', 'Ana Beatriz Lima',      '80230140130', 12, 'ativa',     'LIVRE: Ana testa uma segunda carteirinha'],
        ['Bem Viver Individual',  '300000000002', 'Marcos Vinicius Alves', '80230140211', 12, 'ativa',     'LIVRE: Marcos testa carteirinha que dá certo'],
        ['Horizonte Essencial',   '200000000003', 'Marcos Vinicius Alves', '80230140211', -1, 'ativa',     'testar recusa: vencida'],
        ['Horizonte Família',     '200000000004', 'Ana Beatriz Lima',      '80230140130', 12, 'cancelada', 'testar recusa: cancelada'],
        ['SpSaúde Individual',    '100000000005', 'Juliana Prado',         '80230140300', 12, 'ativa',     'testar recusa: é de outra pessoa'],
    ];
}
