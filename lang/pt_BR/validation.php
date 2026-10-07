<?php

/*
 * Mensagens de validação em português (24/09/2026).
 *
 * O Laravel só traz as mensagens em inglês. Sem este arquivo, toda regra
 * sem mensagem própria no FormRequest aparecia na tela em inglês
 * ("The selected convenios.0 is invalid.").
 *
 * Só funciona com APP_LOCALE=pt_BR no .env (ver README.md, seção 2).
 * As mensagens escritas nos FormRequests continuam tendo prioridade.
 */

return [
    'accepted'        => 'O campo :attribute precisa ser aceito.',
    'accepted_if'     => 'O campo :attribute precisa ser aceito.',
    'after'           => 'O campo :attribute deve ser uma data depois de :date.',
    'after_or_equal'  => 'O campo :attribute deve ser uma data igual ou depois de :date.',
    'array'           => 'O campo :attribute está em formato inválido.',
    'before'          => 'O campo :attribute deve ser uma data antes de :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou antes de :date.',
    'boolean'         => 'O campo :attribute deve ser sim ou não.',
    'confirmed'       => 'A confirmação de :attribute não confere.',
    'current_password'=> 'A senha está incorreta.',
    'date'            => 'O campo :attribute não é uma data válida.',
    'date_format'     => 'O campo :attribute não está no formato :format.',
    'different'       => 'Os campos :attribute e :other devem ser diferentes.',
    'digits'          => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between'  => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'email'           => 'O campo :attribute deve ser um e-mail válido.',
    'exists'          => 'O valor escolhido em :attribute não é válido.',
    'in'              => 'O valor escolhido em :attribute não é válido.',
    'integer'         => 'O campo :attribute deve ser um número inteiro.',
    'max'             => [
        'array'   => 'O campo :attribute não pode ter mais de :max itens.',
        'file'    => 'O arquivo :attribute não pode passar de :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string'  => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min'             => [
        'array'   => 'O campo :attribute precisa ter pelo menos :min itens.',
        'file'    => 'O arquivo :attribute precisa ter pelo menos :min kilobytes.',
        'numeric' => 'O campo :attribute precisa ser pelo menos :min.',
        'string'  => 'O campo :attribute precisa ter pelo menos :min caracteres.',
    ],
    'numeric'         => 'O campo :attribute deve ser um número.',
    'password'        => [
        'letters'       => 'A senha precisa ter pelo menos uma letra.',
        'mixed'         => 'A senha precisa ter letras maiúsculas e minúsculas.',
        'numbers'       => 'A senha precisa ter pelo menos um número.',
        'symbols'       => 'A senha precisa ter pelo menos um símbolo.',
        'uncompromised' => 'Essa senha apareceu em vazamentos de dados. Escolha outra.',
    ],
    'regex'           => 'O campo :attribute está em formato inválido.',
    'required'        => 'O campo :attribute é obrigatório.',
    'required_if'     => 'O campo :attribute é obrigatório.',
    'required_with'   => 'O campo :attribute é obrigatório.',
    'same'            => 'Os campos :attribute e :other precisam ser iguais.',
    'size'            => [
        'array'   => 'O campo :attribute precisa ter :size itens.',
        'numeric' => 'O campo :attribute precisa ser :size.',
        'string'  => 'O campo :attribute precisa ter :size caracteres.',
    ],
    'string'          => 'O campo :attribute deve ser um texto.',
    'unique'          => 'Esse :attribute já está em uso.',
    'url'             => 'O campo :attribute deve ser um link válido.',

    'attributes' => [
        'name'                  => 'nome',
        'email'                 => 'e-mail',
        'password'              => 'senha',
        'telefone'              => 'telefone',
        'data_nascimento'       => 'data de nascimento',
        'motivo'                => 'motivo',
        'observacoes'           => 'observações',
        'horario'               => 'horário',
        'data_consulta'         => 'data da consulta',
        'especialidade_id'      => 'especialidade',
        'usuário_plano_id'     => 'carteirinha',
        'forma_pagamento'       => 'forma de pagamento',
        'unidade_nome'          => 'nome da unidade',
        'unidade_cep'           => 'CEP',
        'unidade_endereco'      => 'endereço',
        'unidade_numero'        => 'número',
        'unidade_bairro'        => 'bairro',
        'unidade_cidade'        => 'cidade',
        'unidade_uf'            => 'UF',
        'razao_social'          => 'razão social',
        'nome_fantasia'         => 'nome fantasia',
    ],
];
