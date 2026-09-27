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

    // Lista de estados

    $ufs = [

        "AC","AL","AP","AM","BA",

        "CE","DF","ES","GO","MA",

        "MT","MS","MG","PA","PB",

        "PR","PE","PI","RJ","RN",

        "RS","RO","RR","SC","SP",

        "SE","TO"

    ];

    // UF existe?

    if(!in_array($uf,$ufs)){

        return false;

    }

    return true;

}
?>