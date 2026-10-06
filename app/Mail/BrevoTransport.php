<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

/**
 * Envio de e-mail pela API do Brevo (01/10/2026).
 *
 * POR QUE ASSIM:
 *  - o Render grátis BLOQUEIA SMTP (portas 25, 465 e 587), então Gmail/SMTP
 *    direto não sai do site no ar. O Brevo recebe o e-mail por HTTPS, que o
 *    Render deixa passar;
 *  - o Brevo é grátis (300 e-mails por dia) e não pede domínio próprio: basta
 *    confirmar o e-mail remetente na conta;
 *  - em vez de instalar um pacote novo, esta classe fala direto com a API,
 *    usando o cliente HTTP que o Laravel já tem (Http::). Zero dependência nova.
 *
 * Como liga: MAIL_MAILER=brevo e BREVO_API_KEY=... (README §2.7). Sem a chave,
 * o config/mail.php volta sozinho para "log" - nada quebra.
 *
 * O Laravel monta o e-mail normalmente (Mailable, notificação de troca de
 * senha...); aqui só traduzimos para o formato que o Brevo pede.
 * API: POST https://api.brevo.com/v3/smtp/email, cabeçalho "api-key".
 */
class BrevoTransport extends AbstractTransport
{
    public const URL = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(private readonly string $chave)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $endereco = fn (Address $a) => array_filter(['email' => $a->getAddress(), 'name' => $a->getName()]);
        $lista = fn (array $enderecos) => array_values(array_map($endereco, $enderecos));

        $dados = array_filter([
            'sender'      => $endereco($envelope->getSender()),
            'to'          => $lista($email->getTo()),
            'cc'          => $lista($email->getCc()),
            'bcc'         => $lista($email->getBcc()),
            'replyTo'     => ($r = $email->getReplyTo()[0] ?? null) ? $endereco($r) : null,
            'subject'     => $email->getSubject() ?? '',
            'htmlContent' => $email->getHtmlBody(),
            'textContent' => $email->getTextBody(),
        ], fn ($valor) => $valor !== null && $valor !== []);

        $resposta = Http::withHeaders(['api-key' => $this->chave, 'accept' => 'application/json'])
            ->timeout(15)
            ->post(self::URL, $dados);

        if ($resposta->failed()) {
            // A mensagem aparece no log (laravel.log / aba Logs do Render).
            throw new TransportException(sprintf('O Brevo recusou o e-mail (HTTP %d): %s', $resposta->status(), $resposta->body()));
        }

        if ($id = $resposta->json('messageId')) {
            $message->setMessageId(trim($id, '<>'));
        }
    }

    public function __toString(): string
    {
        return 'brevo+api://api.brevo.com';
    }
}
