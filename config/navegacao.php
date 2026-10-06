<?php

/**
 * NAVEGAÇÃO DO POINTMED — menus por tipo de usuário.
 *
 * Esta é a correção dos menus dos mockups. Tudo que aparecia no design
 * e NÃO tem suporte no sistema foi retirado daqui de propósito:
 *
 *   Exames · Receitas · Atestados · Prontuários · Medicamentos ·
 *   Meus documentos · Resumo/Status da saúde
 *
 * Nada disso existe no banco, e AGENTS.md §1 tira do escopo. Se algum
 * desses itens voltar para cá, o sistema passa a prometer uma tela que
 * não existe — ou pior, no caso do resumo de saúde, passa a exibir
 * conteúdo clínico, que AGENTS.md §6 proíbe.
 *
 * Antes de acrescentar item novo, confira se existe rota E tabela.
 * Item de menu sem tela atrás é link morto, e é a primeira coisa que
 * um avaliador clica.
 *
 * Formato: ['rota' => nome da rota, 'label' => texto, 'icone' => slug]
 * O componente <x-sidebar /> renderiza a partir daqui.
 */

return [

    /**
     * 01/10/2026: sem agendamento. O usuário busca locais e médicos,
     * avalia, e guarda o plano para usar como filtro.
     */
    'usuario' => [
        ['rota' => 'usuario.dashboard',  'label' => 'Início',              'icone' => 'home'],
        ['rota' => 'busca.locais',        'label' => 'Perto de você',       'icone' => 'pin'],
        ['rota' => 'busca.index',         'label' => 'Encontrar médicos',   'icone' => 'search'],
        ['rota' => 'usuario.avaliacoes', 'label' => 'Minhas avaliações',   'icone' => 'star'],
        ['rota' => 'usuario.planos',     'label' => 'Meu plano',           'icone' => 'card'],
        ['rota' => 'usuario.perfil',     'label' => 'Meu perfil',          'icone' => 'user'],
    ],

    /**
     * 01/10: a clínica cadastra os médicos E as especialidades. Agenda e
     * "Consultas realizadas" saíram com o agendamento.
     */
    'clinica' => [
        ['rota' => 'clinica.dashboard',      'label' => 'Início',           'icone' => 'home'],
        ['rota' => 'clinica.medicos',        'label' => 'Meus médicos',     'icone' => 'doctors'],
        ['rota' => 'clinica.especialidades', 'label' => 'Especialidades',   'icone' => 'tag'],
        ['rota' => 'clinica.unidades',       'label' => 'Unidades',         'icone' => 'building'],
        ['rota' => 'clinica.convenios',      'label' => 'Convênios',        'icone' => 'shield'],
        ['rota' => 'clinica.avaliacoes',     'label' => 'Avaliações',       'icone' => 'star'],
        ['rota' => 'clinica.perfil',         'label' => 'Perfil da clínica','icone' => 'user'],
    ],

    /**
     * 01/10: saíram "Conferir carteirinhas" (é conferida sozinha no
     * cadastro) e "Consultas"; "Verificar CRM" virou "Verificar CNPJ".
     */
    'admin' => [
        ['rota' => 'admin.dashboard',      'label' => 'Início',              'icone' => 'home'],
        ['rota' => 'admin.usuarios',       'label' => 'Usuários',            'icone' => 'users'],
        ['rota' => 'admin.cnpjs',          'label' => 'Verificar CNPJ',      'icone' => 'badge'],
        ['rota' => 'admin.clinicas',       'label' => 'Clínicas e hospitais','icone' => 'building'],
        ['rota' => 'admin.especialidades', 'label' => 'Especialidades',      'icone' => 'tag'],
        ['rota' => 'admin.convenios',      'label' => 'Convênios',           'icone' => 'shield'],
        ['rota' => 'admin.perfil',         'label' => 'Meu perfil',          'icone' => 'user'],
    ],

];
