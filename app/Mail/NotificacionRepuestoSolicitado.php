<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionRepuestoSolicitado extends Mailable
{
    use Queueable, SerializesModels;

    public array $repuestos;
    public string $url;
    public ?string $solicitante;
    public $visita;
    public $asesor;
    public $asistenteDestinatario;
    public $tecnico;

    public function __construct(array $repuestos, string $url, ?string $solicitante = null, $visita = null, $asesor = null, $asistenteDestinatario = null, $tecnico = null)
    {
        $this->repuestos = $repuestos;
        $this->url = $url;
        $this->solicitante = $solicitante;
        $this->visita = $visita;
        $this->asesor = $asesor;
        $this->asistenteDestinatario = $asistenteDestinatario;
        $this->tecnico = $tecnico;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo repuesto solicitado - Visita técnica #' . ($this->visita->ID ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion-repuesto-solicitado',
            with: [
                'repuestos' => $this->repuestos,
                'url' => $this->url,
                'solicitante' => $this->solicitante,
                'visita' => $this->visita,
                'asesor' => $this->asesor,
                'asistente' => $this->asistenteDestinatario,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
