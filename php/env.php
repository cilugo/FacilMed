<?php
/*=========================================================
    CARREGADOR DE VARIÁVEIS DE AMBIENTE (.env)
                        FacilMed
=========================================================

Lê o arquivo .env na raiz do projeto (se existir) e registra
as variáveis com putenv(), para que getenv() funcione em
qualquer parte do código (ex: SimpleMailer::fromEnv()).

Não sobrescreve variáveis já definidas no ambiente real do
servidor — o .env é só um fallback para desenvolvimento local.
Nunca versione o seu .env de verdade (use o .env.example como
modelo e mantenha o .env no .gitignore).
*/

function carregarEnv(string $caminhoArquivo): void
{
    if (!is_readable($caminhoArquivo)) {
        return;
    }

    $linhas = file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        if ($linha === "" || str_starts_with($linha, "#")) {
            continue;
        }

        if (!str_contains($linha, "=")) {
            continue;
        }

        [$chave, $valor] = explode("=", $linha, 2);
        $chave = trim($chave);
        $valor = trim($valor);

        // Remove aspas simples ou duplas envolvendo o valor, se houver
        if (strlen($valor) >= 2) {
            $primeiro = $valor[0];
            $ultimo = $valor[strlen($valor) - 1];
            if (($primeiro === '"' && $ultimo === '"') || ($primeiro === "'" && $ultimo === "'")) {
                $valor = substr($valor, 1, -1);
            }
        }

        // Não sobrescreve variáveis já definidas de verdade no ambiente
        if (getenv($chave) === false) {
            putenv("$chave=$valor");
        }
    }
}

carregarEnv(__DIR__ . "/../.env");
