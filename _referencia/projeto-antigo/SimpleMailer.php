<?php
/*=========================================================
    CLIENTE SMTP SIMPLES (sem dependências externas)
                        FacilMed
=========================================================

Implementa o protocolo SMTP na mão (EHLO/STARTTLS/AUTH LOGIN/
MAIL FROM/RCPT TO/DATA), porque este ambiente não tem acesso
para instalar bibliotecas via Composer (ex: PHPMailer).

Se no seu servidor você tiver Composer disponível, pode trocar
esta classe pelo PHPMailer sem alterar recuperarsenha.php —
só manter o mesmo método send($to, $toNome, $assunto, $corpoHtml).
*/

class SimpleMailerException extends Exception {}

class SimpleMailer
{
    private string $host;
    private int $port;
    private string $secure; // "tls" (STARTTLS, porta 587) | "ssl" (SMTPS, porta 465) | "" (sem criptografia)
    private string $usuario;
    private string $senha;
    private string $remetenteEmail;
    private string $remetenteNome;
    private int $timeout = 15;

    public function __construct(
        string $host,
        int $port,
        string $secure,
        string $usuario,
        string $senha,
        string $remetenteEmail,
        string $remetenteNome
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->secure = strtolower($secure);
        $this->usuario = $usuario;
        $this->senha = $senha;
        $this->remetenteEmail = $remetenteEmail;
        $this->remetenteNome = $remetenteNome;
    }

    /**
     * Monta um SimpleMailer a partir de variáveis de ambiente.
     * Retorna null se a configuração obrigatória não estiver presente
     * (assim quem chama pode cair num modo de desenvolvimento).
     */
    public static function fromEnv(): ?self
    {
        $host = getenv("SMTP_HOST") ?: "";
        $usuario = getenv("SMTP_USER") ?: "";
        $senha = getenv("SMTP_PASS") ?: "";
        $remetenteEmail = getenv("SMTP_FROM_EMAIL") ?: "";

        if ($host === "" || $usuario === "" || $senha === "" || $remetenteEmail === "") {
            return null;
        }

        $port = (int) (getenv("SMTP_PORT") ?: 587);
        $secure = getenv("SMTP_SECURE") ?: "tls";
        $remetenteNome = getenv("SMTP_FROM_NAME") ?: "FacilMed";

        return new self($host, $port, $secure, $usuario, $senha, $remetenteEmail, $remetenteNome);
    }

    public function send(string $paraEmail, string $paraNome, string $assunto, string $corpoHtml): bool
    {
        $transporte = $this->secure === "ssl" ? "ssl://" : "";
        $socket = @stream_socket_client(
            $transporte . $this->host . ":" . $this->port,
            $errno,
            $errstr,
            $this->timeout
        );

        if (!$socket) {
            throw new SimpleMailerException("Falha ao conectar no servidor SMTP: $errstr ($errno)");
        }

        stream_set_timeout($socket, $this->timeout);

        $this->esperarResposta($socket, 220);
        $this->comando($socket, "EHLO " . $this->hostLocal(), 250);

        if ($this->secure === "tls") {
            $this->comando($socket, "STARTTLS", 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new SimpleMailerException("Falha ao iniciar TLS com o servidor SMTP.");
            }
            // Depois do STARTTLS o handshake reinicia, precisa mandar EHLO de novo
            $this->comando($socket, "EHLO " . $this->hostLocal(), 250);
        }

        $this->comando($socket, "AUTH LOGIN", 334);
        $this->comando($socket, base64_encode($this->usuario), 334);
        $this->comando($socket, base64_encode($this->senha), 235);

        $this->comando($socket, "MAIL FROM:<{$this->remetenteEmail}>", 250);
        $this->comando($socket, "RCPT TO:<{$paraEmail}>", [250, 251]);
        $this->comando($socket, "DATA", 354);

        $mensagem = $this->montarMensagem($paraEmail, $paraNome, $assunto, $corpoHtml);
        // Linha só com "." encerra o corpo — precisamos escapar linhas que comecem com "."
        $mensagemEscapada = preg_replace('/^\./m', '..', $mensagem);

        fwrite($socket, $mensagemEscapada . "\r\n.\r\n");
        $this->esperarResposta($socket, 250);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    }

    private function montarMensagem(string $paraEmail, string $paraNome, string $assunto, string $corpoHtml): string
    {
        $assuntoCodificado = "=?UTF-8?B?" . base64_encode($assunto) . "?=";
        $remetenteNomeCodificado = "=?UTF-8?B?" . base64_encode($this->remetenteNome) . "?=";

        $cabecalhos = [
            "From: {$remetenteNomeCodificado} <{$this->remetenteEmail}>",
            "To: {$paraNome} <{$paraEmail}>",
            "Subject: {$assuntoCodificado}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit",
            "Date: " . date("r"),
        ];

        return implode("\r\n", $cabecalhos) . "\r\n\r\n" . $corpoHtml;
    }

    private function hostLocal(): string
    {
        return $_SERVER["SERVER_NAME"] ?? "localhost";
    }

    private function esperarResposta($socket, $codigoEsperado): string
    {
        $resposta = "";
        while ($linha = fgets($socket, 515)) {
            $resposta .= $linha;
            // A última linha de uma resposta multi-linha tem um espaço após o código (não hífen)
            if (isset($linha[3]) && $linha[3] === " ") {
                break;
            }
        }

        $codigo = (int) substr($resposta, 0, 3);
        $codigosOk = is_array($codigoEsperado) ? $codigoEsperado : [$codigoEsperado];

        if (!in_array($codigo, $codigosOk, true)) {
            throw new SimpleMailerException("Resposta inesperada do servidor SMTP: " . trim($resposta));
        }

        return $resposta;
    }

    private function comando($socket, string $comando, $codigoEsperado): string
    {
        fwrite($socket, $comando . "\r\n");
        return $this->esperarResposta($socket, $codigoEsperado);
    }
}
