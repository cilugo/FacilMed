<?php

namespace App\Mail;

use App\Models\Consulta;
use App\Support\Formatador;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Um Mailable só para os quatro avisos de consulta (confirmação,
 * lembrete, cancelamento, remarcação). O texto de cada um está em
 * resources/views/emails/consulta.blade.php.
 *
 * Nada de conteúdo clínico no e-mail (AGENTS.md §6): quem, quando,
 * onde e a situação. As observações do paciente NÃO vão no e-mail.
 */
class AvisoDeConsulta extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Consulta $consulta,
        public string $tipo,
        public string $para = 'paciente',
    ) {
    }

    public function envelope(): Envelope
    {
        $quando = Formatador::dataCurta($this->consulta->data_consulta) . ' às ' . Formatador::hora($this->consulta->horario);

        return new Envelope(subject: match ($this->tipo) {
            'confirmacao'  => "Consulta agendada — {$quando}",
            'lembrete_24h' => "Lembrete: sua consulta é amanhã, {$quando}",
            'cancelamento' => "Consulta cancelada — {$quando}",
            'remarcacao'   => "Consulta remarcada — o horário de {$quando} foi liberado",
            default        => "FacilMed — consulta de {$quando}",
        });
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.consulta', with: [
            'c'         => $this->consulta,
            'tipo'      => $this->tipo,
            'para'      => $this->para,
            'quando'    => Formatador::dataExtensa($this->consulta->data_consulta) . ', às ' . Formatador::hora($this->consulta->horario),
            'url'       => $this->para === 'medico'
                ? route('medico.agenda', ['data' => $this->consulta->data_consulta->toDateString()])
                : route('paciente.consultas.show', $this->consulta),
        ]);
    }
}
