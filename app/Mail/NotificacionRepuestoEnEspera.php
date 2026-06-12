<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionRepuestoEnEspera extends Mailable
{
    use Queueable, SerializesModels;

    public $visita;
    public $tecnico;
    public $repuestos;
    public $url;
    public $asesor;

    public function __construct($visita, $tecnico, $repuestos = [], $url = null, $asesor = null)
    {
        $this->visita = $visita;
        $this->tecnico = $tecnico;
        $this->repuestos = $repuestos;
        $this->url = $url;
        $this->asesor = $asesor;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Repuesto en espera - Visita técnica #' . ($this->visita->ID ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion-repuesto-en-espera',
            with: [
                'visita' => $this->visita,
                'tecnico' => $this->tecnico,
                'repuestos' => $this->repuestos,
                'url' => $this->url,
                'asesor' => $this->asesor,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
