<?php

/**
 * REGRAS DE AGENDAMENTO DO FACILMED.
 *
 * Os numeros que definem "quando da para marcar" moram aqui, e so
 * aqui. A CalculadoraDeHorarios le deste arquivo. Mudou a regra?
 * Muda o numero e vale para a tela, para a gravacao e para a busca
 * de uma vez.
 */

return [

    /**
     * ANTECEDENCIA MINIMA, EM HORAS.
     *
     * Horario que comeca antes de agora + este intervalo nao e
     * oferecido. Decisao do grupo em 19/09/2026: 24 horas.
     *
     * EFEITO COLATERAL CONHECIDO, ACEITO NA DECISAO:
     * o sistema permite cancelar faltando menos de 24h (marcando
     * cancelamento_tardio), com a justificativa de que cancelar
     * devolve o horario para outro paciente. Com antecedencia de
     * 24h, esse horario devolvido NAO pode ser ocupado por ninguem -
     * nao sobra tempo habil. Na pratica o cancelamento tardio vira
     * so registro estatistico, nao recuperacao de vaga.
     *
     * Se um dia quiserem que o cancelamento tardio realmente devolva
     * a vaga, baixe este numero para 3. Nada mais muda no codigo.
     */
    'antecedencia_minima_horas' => 24,

    /**
     * JANELA MAXIMA, EM DIAS.
     *
     * Ate quando da para marcar, contando de hoje. Decisao do grupo:
     * 180 dias.
     *
     * Por que existe um teto: disponibilidade e bloco recorrente
     * semanal ("toda terca, 8h as 12h"), entao sem limite ela se
     * projeta para sempre - o calendario da tela nao teria onde
     * parar de desenhar, e a busca por "proximo horario livre"
     * procuraria indefinidamente num medico sem vaga.
     */
    'janela_maxima_dias' => 180,

    /**
     * Duracao padrao da consulta, em minutos.
     *
     * So entra em cena se o bloco de disponibilidade nao trouxer a
     * propria duracao - o normal e cada bloco definir a dele, porque
     * varia por especialidade.
     */
    'duracao_padrao_minutos' => 30,

];
