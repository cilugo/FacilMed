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

/** Tamanho mínimo de senha aceito no cadastro e na troca de senha. */
const SENHA_TAMANHO_MINIMO = 8;

/**
 * Valida os campos comuns aos cadastros de paciente e de médico.
 * Retorna a mensagem de erro, ou null se estiver tudo certo.
 *
 * Existe porque as validações estavam só no JavaScript — e JavaScript é
 * sugestão, não segurança: um POST enviado direto (sem abrir a página)
 * gravava nome vazio, e-mail inválido e senha de 1 caractere no banco.
 */
function erroNosDadosDeCadastro(
    string $nome,
    string $cpf,
    string $email,
    string $telefone,
    string $senha,
    string $confirmarSenha
): ?string {
    if ($nome === "" || $cpf === "" || $email === "" || $telefone === "" || $senha === "") {
        return "Preencha todos os campos.";
    }

    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 150) {
        return "Nome deve ter entre 3 e 150 caracteres.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "E-mail inválido.";
    }

    // Só conta os dígitos, então aceita com ou sem máscara (123.456.789-10)
    if (strlen(preg_replace("/\D/", "", $cpf)) !== 11) {
        return "CPF deve ter 11 dígitos.";
    }

    $digitosTelefone = strlen(preg_replace("/\D/", "", $telefone));
    if ($digitosTelefone < 10 || $digitosTelefone > 11) {
        return "Telefone deve ter DDD + número (10 ou 11 dígitos).";
    }

    // 72 bytes é o limite real do bcrypt — o que passar disso é ignorado
    if (strlen($senha) < SENHA_TAMANHO_MINIMO || strlen($senha) > 72) {
        return "A senha deve ter entre " . SENHA_TAMANHO_MINIMO . " e 72 caracteres.";
    }

    // Comparação estrita (!==). Com != o PHP converte strings numéricas em
    // número, e "1e2" passaria como igual a "100".
    if ($senha !== $confirmarSenha) {
        return "As senhas não coincidem.";
    }

    return null;
}

/**
 * Monta a lista de horários LIVRES de um médico em uma data.
 *
 * É a única fonte da verdade sobre horário disponível: a tela do paciente
 * (php/horariosdisponiveis.php) e a gravação da consulta
 * (php/agendarconsulta.php) chamam esta mesma função. Antes cada uma tinha
 * a sua própria regra — e a da gravação só conferia se o horário caía
 * "dentro" do bloco, então um POST com horario=08:07 passava, mesmo com
 * consultas de 30 em 30 minutos.
 *
 * Retorna horários no formato "HH:MM:SS", já sem os ocupados e, se a data
 * for hoje, sem os que já passaram.
 */
function horariosLivres(mysqli $conexao, int $medicoId, string $data): array
{
    $diaSemana = diaSemanaEnum($data);

    $stmt = $conexao->prepare(
        "SELECT hora_inicio, hora_fim, duracao_consulta_minutos
         FROM disponibilidades
         WHERE medico_id = ? AND dia_semana = ? AND ativo = 1"
    );
    $stmt->bind_param("is", $medicoId, $diaSemana);
    $stmt->execute();
    $blocos = $stmt->get_result();

    // Gera os horários exatos a partir de cada bloco semanal
    $slots = [];
    while ($bloco = $blocos->fetch_assoc()) {
        $inicio = strtotime($data . " " . $bloco["hora_inicio"]);
        $fim    = strtotime($data . " " . $bloco["hora_fim"]);
        $passo  = ((int) $bloco["duracao_consulta_minutos"]) * 60;

        if ($passo <= 0) {
            continue; // proteção contra duração zerada/corrompida (laço infinito)
        }

        for ($t = $inicio; $t + $passo <= $fim; $t += $passo) {
            $slots[] = date("H:i:s", $t);
        }
    }
    $stmt->close();

    // Blocos antigos podem estar sobrepostos; array_unique evita horário repetido
    $slots = array_unique($slots);
    sort($slots);

    if (empty($slots)) {
        return [];
    }

    // Remove os horários que já têm consulta marcada
    $stmtOcupados = $conexao->prepare(
        "SELECT horario FROM consultas
         WHERE medico_id = ? AND data_consulta = ? AND status <> 'Cancelada'"
    );
    $stmtOcupados->bind_param("is", $medicoId, $data);
    $stmtOcupados->execute();
    $resultado = $stmtOcupados->get_result();

    $ocupados = [];
    while ($row = $resultado->fetch_assoc()) {
        $ocupados[] = $row["horario"];
    }
    $stmtOcupados->close();

    $slots = array_values(array_diff($slots, $ocupados));

    // Se for hoje, remove o que já passou
    if ($data === date("Y-m-d")) {
        $agora = date("H:i:s");
        $slots = array_values(array_filter($slots, fn($h) => $h > $agora));
    }

    return $slots;
}

/**
 * Normaliza um horário vindo de formulário ("8:30", "08:30", "08:30:00")
 * para o formato "HH:MM:SS" usado no banco. Retorna null se não for um
 * horário válido.
 */
function normalizarHorario(string $horario): ?string
{
    if (!preg_match("/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/", trim($horario), $m)) {
        return null;
    }

    $h = (int) $m[1];
    $i = (int) $m[2];
    $s = isset($m[3]) ? (int) $m[3] : 0;

    if ($h > 23 || $i > 59 || $s > 59) {
        return null;
    }

    return sprintf("%02d:%02d:%02d", $h, $i, $s);
}

/**
 * Devolve o caminho-base da aplicação dentro do servidor web, sem barra
 * no final. Ex: no XAMPP, com o projeto em htdocs/FacilMed/, retorna
 * "/FacilMed"; se o projeto for a raiz do site, retorna "".
 *
 * Serve para montar redirecionamentos absolutos que funcionam de qualquer
 * pasta (paginas/, paginas/admin/, php/medico/...) sem precisar contar
 * quantos "../" são necessários em cada arquivo.
 */
function urlBase(): string
{
    // dirname(__DIR__) = .../FacilMed/php  ->  dirname() de novo = .../FacilMed
    $raizApp  = str_replace("\\", "/", dirname(__DIR__, 2));
    $raizWeb  = rtrim(str_replace("\\", "/", $_SERVER["DOCUMENT_ROOT"] ?? ""), "/");

    if ($raizWeb !== "" && strpos($raizApp, $raizWeb) === 0) {
        return rtrim(substr($raizApp, strlen($raizWeb)), "/");
    }

    return "";
}

/**
 * Manda o navegador para uma página do sistema e encerra o script.
 * O caminho é relativo à raiz do projeto, ex: "paginas/login.html".
 */
function redirecionar(string $caminho): void
{
    header("Location: " . urlBase() . "/" . ltrim($caminho, "/"));
    exit();
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
