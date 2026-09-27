<?php
/*=========================================================
                VALIDAÇÃO DO CRM
                     FacilMed
=========================================================*/

/*
    Esta função verifica:

    ✔ CRM informado
    ✔ Apenas números
    ✔ Quantidade mínima de caracteres
    ✔ UF válida

    Futuramente poderá consultar
    uma API oficial do CFM/CRM.
*/

/**
 * Todas as 27 UFs do Brasil. Fica como constante para o formulário de
 * cadastro montar o <select> a partir DESTA lista — antes o select tinha
 * só SP, RJ, MG e PR, enquanto a validação aqui aceitava as 27, então a
 * tela era mais restrita que a regra.
 */
const UFS_BRASIL = [
    "AC","AL","AP","AM","BA","CE","DF","ES","GO",
    "MA","MT","MS","MG","PA","PB","PR","PE","PI",
    "RJ","RN","RS","RO","RR","SC","SP","SE","TO",
];

function validarCRM($crm, $uf){

    // Remove espaços

    $crm = trim($crm);

    // Apenas números

    if(!preg_match("/^[0-9]+$/", $crm)){

        return false;

    }

    // Tamanho mínimo

    if(strlen($crm) < 4){

        return false;

    }

    // UF existe?

    if(!in_array($uf, UFS_BRASIL, true)){

        return false;

    }

    return true;

}
?>