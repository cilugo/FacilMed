<?php
/*=========================================================
                        FacilMed
                       helpers.php

    Funções pequenas usadas em mais de um arquivo, para não
    repetir a mesma lógica em cada página (lookup do médico
    pelo usuário logado, nomes dos dias da semana usados em
    disponibilidades/agendamento).
==========================================================*/

/**
 * Dias da semana na mesma ordem/nome do ENUM dia_semana do banco,
 * com o rótulo em português para exibição.
 */
const DIAS_SEMANA_LABEL = [
    "segunda" => "Segunda-feira",
    "terca"   => "Terça-feira",
    "quarta"  => "Quarta-feira",
    "quinta"  => "Quinta-feira",
    "sexta"   => "Sexta-feira",
    "sabado"  => "Sábado",
    "domingo" => "Domingo",
];

/** Mesma lista, na ordem de domingo (0) a sábado (6) — ordem de date("w"). */
const DIAS_SEMANA_POR_INDICE = [
    "domingo", "segunda", "terca", "quarta", "quinta", "sexta", "sabado",
];

/**
 * Converte uma data (Y-m-d ou timestamp) para o valor do ENUM
 * dia_semana correspondente (ex: "segunda").
 */
function diaSemanaEnum(string $data): string
{
    return DIAS_SEMANA_POR_INDICE[(int) date("w", strtotime($data))];
}

/**
 * Busca o id da linha em `medicos` correspondente a um usuario_id
 * logado. Retorna null se o usuário não tiver perfil de médico.
 */
function getMedicoIdByUsuario(mysqli $conexao, int $usuarioId): ?int
{
    $stmt = $conexao->prepare("SELECT id FROM medicos WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $stmt->bind_result($medicoId);
    $encontrado = $stmt->fetch();
    $stmt->close();

    return $encontrado ? (int) $medicoId : null;
}
