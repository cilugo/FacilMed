<?php

namespace Database\Seeders;

/**
 * TODOS OS DADOS FICTÍCIOS DO POINTMED EM UM LUGAR SÓ (24/09/2026).
 *
 * Decisão do grupo: 3 clínicas, 3 hospitais, 2 usuários e (desde 01/10/2026)
 * 8 médicos,
 * com CPFs, CNPJs e CRMs que PASSAM nas regras de validação (dígito
 * verificador correto) e que existem nas BASES SIMULADAS.
 *
 * Os seeders (BaseSimulada, Clinica, Medico, Usuario) leem daqui.
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
 * Senha de todas as contas: facilmed2026 (médico não tem conta desde 01/10).
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
     * 8 médicos (eram 3; 01/10/2026: mais médicos para "Médicos bem
     * avaliados" na home, que mostra até 6). Médico é PERFIL, sem conta:
     * não tem e-mail nem senha. Cada um atende em DOIS lugares, com preço
     * por unidade E por especialidade — o usuário vê a faixa ($ a $$$$).
     * unidades = [nome da unidade, [especialidade => valor]]
     * (05/10/2026: o valor não é mais gravado — a Tabela de preços saiu; a
     * faixa é da unidade, em FAIXAS abaixo. Fica só para dizer onde atende.)
     */
    /**
     * 05/10/2026: faixa de preço ($ = 1 a $$$$ = 4) de cada unidade, escolhida
     * pela clínica (App\Support\FaixaDePreco).
     */
    public const FAIXAS = [
        'SpSaúde - Jardim Satélite' => 2,
        'Vida Plena - Centro'       => 3,
        'Aurora - Vila Ema'         => 2,
        'Santa Clara - Taubaté'     => 3,
        'São Lucas - Jacareí'       => 4,
        'Esperança - Caçapava'      => 1,
    ];

    public const MEDICOS = [
        [
            'nome' => 'Dra. Helena Navarro', 'foto' => 'imgs/medicos/medico3.jpeg', 'cpf' => '70120130106',
            'crm' => '112233', 'uf' => 'SP', 'anos' => 14,
            'bio' => 'Cardiologista com atuação em prevenção e acompanhamento de hipertensão.',
            'especialidades' => ['Cardiologia', 'Clínica Geral'],
            'unidades' => [
                ['Vida Plena - Centro',   ['Cardiologia' => 380.00, 'Clínica Geral' => 220.00]],
                ['Santa Clara - Taubaté', ['Cardiologia' => 450.00]],
            ],
            'convenios' => ['SpSaúde', 'Horizonte Med'],
        ],
        [
            'nome' => 'Dr. Rafael Moreira', 'foto' => 'imgs/medicos/medico2.jpeg', 'cpf' => '70120130289',
            'crm' => '223344', 'uf' => 'SP', 'anos' => 9,
            'bio' => 'Dermatologista e clínico geral, atendimento adulto.',
            'especialidades' => ['Dermatologia', 'Clínica Geral'],
            'unidades' => [
                ['SpSaúde - Jardim Satélite', ['Dermatologia' => 340.00, 'Clínica Geral' => 200.00]],
                ['São Lucas - Jacareí',       ['Dermatologia' => 390.00]],
            ],
            'convenios' => ['SpSaúde', 'Bem Viver Saúde'],
        ],
        [
            'nome' => 'Dra. Camila Reis', 'foto' => 'imgs/medicos/medico5.jpeg', 'cpf' => '70120130360',
            'crm' => '334455', 'uf' => 'SP', 'anos' => 18,
            'bio' => 'Pediatra, atendimento de recém-nascidos a adolescentes.',
            'especialidades' => ['Pediatria'],
            'unidades' => [
                ['Aurora - Vila Ema',    ['Pediatria' => 300.00]],
                ['Esperança - Caçapava', ['Pediatria' => 280.00]],
            ],
            'convenios' => ['Horizonte Med', 'Bem Viver Saúde'],
        ],
        [
            'nome' => 'Dra. Juliana Prado', 'foto' => 'imgs/medicos/medico1.jpeg', 'cpf' => '70120130440',
            'crm' => '556688', 'uf' => 'SP', 'anos' => 11,
            'bio' => 'Ginecologista, com foco em saúde da mulher e acompanhamento preventivo.',
            'especialidades' => ['Ginecologia'],
            'unidades' => [
                ['Vida Plena - Centro',  ['Ginecologia' => 320.00]],
                ['Esperança - Caçapava', ['Ginecologia' => 260.00]],
            ],
            'convenios' => ['SpSaúde', 'Bem Viver Saúde'],
        ],
        [
            'nome' => 'Dr. Marcelo Antunes', 'foto' => 'imgs/medicos/medico9.jpeg', 'cpf' => '70120130521',
            'crm' => '667788', 'uf' => 'SP', 'anos' => 26,
            'bio' => 'Ortopedista, atende lesões esportivas e dores na coluna.',
            'especialidades' => ['Ortopedia'],
            'unidades' => [
                ['Santa Clara - Taubaté', ['Ortopedia' => 420.00]],
                ['Aurora - Vila Ema',     ['Ortopedia' => 380.00]],
            ],
            'convenios' => ['Horizonte Med'],
        ],
        [
            'nome' => 'Dra. Beatriz Okada', 'foto' => 'imgs/medicos/medico7.jpeg', 'cpf' => '70120130602',
            'crm' => '778899', 'uf' => 'SP', 'anos' => 7,
            'bio' => 'Oftalmologista, exames de rotina e acompanhamento de glaucoma.',
            'especialidades' => ['Oftalmologia'],
            'unidades' => [
                ['SpSaúde - Jardim Satélite', ['Oftalmologia' => 260.00]],
                ['São Lucas - Jacareí',       ['Oftalmologia' => 300.00]],
            ],
            'convenios' => ['SpSaúde', 'Horizonte Med'],
        ],
        [
            'nome' => 'Dr. Thiago Nunes', 'foto' => 'imgs/medicos/medico6.jpeg', 'cpf' => '70120130793',
            'crm' => '889900', 'uf' => 'SP', 'anos' => 6,
            'bio' => 'Endocrinologista e clínico geral, acompanhamento de diabetes e tireoide.',
            'especialidades' => ['Endocrinologia', 'Clínica Geral'],
            'unidades' => [
                ['Aurora - Vila Ema',     ['Endocrinologia' => 330.00, 'Clínica Geral' => 180.00]],
                ['Santa Clara - Taubaté', ['Endocrinologia' => 410.00]],
            ],
            'convenios' => ['Bem Viver Saúde'],
        ],
        [
            'nome' => 'Dr. Lucas Ferreira', 'foto' => 'imgs/medicos/medico8.jpeg', 'cpf' => '70120130874',
            'crm' => '990011', 'uf' => 'SP', 'anos' => 15,
            'bio' => 'Neurologista, atende enxaqueca, epilepsia e distúrbios do sono.',
            'especialidades' => ['Neurologia'],
            'unidades' => [
                ['São Lucas - Jacareí', ['Neurologia' => 520.00]],
                ['Vida Plena - Centro', ['Neurologia' => 480.00]],
            ],
            'convenios' => [],
        ],
    ];

    /**
     * 2 usuários. 'carteirinha' = a que JÁ vem cadastrada na conta.
     * O Marcos entra sem nenhuma, de propósito: é com ele que se testa
     * o cadastro de carteirinha (ver CARTEIRINHAS abaixo).
     */
    public const USUARIOS = [
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
        ['556688', 'SP', 'Juliana Prado',   'ativo',    'médica do seed (01/10)'],
        ['667788', 'SP', 'Marcelo Antunes', 'ativo',    'médico do seed (01/10)'],
        ['778899', 'SP', 'Beatriz Okada',   'ativo',    'médica do seed (01/10)'],
        ['889900', 'SP', 'Thiago Nunes',    'ativo',    'médico do seed (01/10)'],
        ['990011', 'SP', 'Lucas Ferreira',  'ativo',    'médico do seed (01/10)'],
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
