<?php

/**
 * NAVEGAÇÃO DO FACILMED — menus por tipo de usuário.
 *
 * Esta é a correção dos menus dos mockups. Tudo que aparecia no design
 * e NÃO tem suporte no sistema foi retirado daqui de propósito:
 *
 *   Exames · Receitas · Atestados · Prontuários · Medicamentos ·
 *   Meus documentos · Resumo/Status da saúde
 *
 * Nada disso existe no banco, e AGENTS.md §2 tira do escopo. Se algum
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

    'paciente' => [
        ['rota' => 'paciente.dashboard',  'label' => 'Início',           'icone' => 'home'],
        ['rota' => 'busca.index',         'label' => 'Agendar consulta', 'icone' => 'search'],
        ['rota' => 'paciente.consultas',  'label' => 'Minhas consultas', 'icone' => 'calendar'],
        ['rota' => 'paciente.planos',     'label' => 'Meu plano',        'icone' => 'card'],
        ['rota' => 'paciente.perfil',     'label' => 'Meu perfil',       'icone' => 'user'],
    ],

    'medico' => [
        ['rota' => 'medico.dashboard',       'label' => 'Início',          'icone' => 'home'],
        ['rota' => 'medico.agenda',          'label' => 'Minha agenda',    'icone' => 'calendar'],
        ['rota' => 'medico.disponibilidade', 'label' => 'Meus horários',   'icone' => 'clock'],
        ['rota' => 'medico.bloqueios',       'label' => 'Ausências',       'icone' => 'pause'],
        ['rota' => 'medico.locais',          'label' => 'Onde eu atendo',  'icone' => 'pin'],
        ['rota' => 'medico.precos',          'label' => 'Preços',          'icone' => 'money'],
        ['rota' => 'medico.avaliacoes',      'label' => 'Avaliações',      'icone' => 'star'],
        ['rota' => 'medico.perfil',          'label' => 'Meu perfil',      'icone' => 'user'],
    ],

    'clinica' => [
        ['rota' => 'clinica.dashboard',  'label' => 'Início',           'icone' => 'home'],
        ['rota' => 'clinica.agenda',     'label' => 'Agenda da clínica','icone' => 'calendar'],
        ['rota' => 'clinica.medicos',    'label' => 'Meus médicos',     'icone' => 'doctors'],
        ['rota' => 'clinica.unidades',   'label' => 'Unidades',         'icone' => 'building'],
        ['rota' => 'clinica.precos',     'label' => 'Tabela de preços', 'icone' => 'money'],
        ['rota' => 'clinica.convenios',  'label' => 'Convênios',        'icone' => 'shield'],
        ['rota' => 'clinica.avaliacoes', 'label' => 'Avaliações',       'icone' => 'star'],
        ['rota' => 'clinica.perfil',     'label' => 'Perfil da clínica','icone' => 'user'],
    ],

    /**
     * O menu do admin é o que mais se aproximou do mockup — aquele
     * desenho estava certo. A única coisa que saiu foi "Relatórios",
     * que virou parte do próprio dashboard em vez de seção separada.
     */
    'admin' => [
        ['rota' => 'admin.dashboard',      'label' => 'Início',              'icone' => 'home'],
        ['rota' => 'admin.usuarios',       'label' => 'Usuários',            'icone' => 'users'],
        ['rota' => 'admin.verificacoes',   'label' => 'Verificar CRM',       'icone' => 'badge'],
        ['rota' => 'admin.carteirinhas',   'label' => 'Conferir carteirinhas','icone' => 'card'],
        ['rota' => 'admin.clinicas',       'label' => 'Clínicas e hospitais','icone' => 'building'],
        ['rota' => 'admin.especialidades', 'label' => 'Especialidades',      'icone' => 'tag'],
        ['rota' => 'admin.convenios',      'label' => 'Convênios',           'icone' => 'shield'],
        ['rota' => 'admin.consultas',      'label' => 'Consultas',           'icone' => 'calendar'],
    ],

];
