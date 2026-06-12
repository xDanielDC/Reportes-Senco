<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionRepuestoDespachado extends Mailable
{
    use Queueable, SerializesModels;

    public $visita;
    public $destinatario;
    public $repuestos;
    public $url;
    public $asesor;
    public $tecnico;

    public function __construct($visita, $destinatario, $repuestos = [], $url = null, $asesor = null, $tecnico = null)
    {
        $this->visita = $visita;
        $this->destinatario = $destinatario;
        $this->repuestos = $repuestos;
        $this->url = $url;
        $this->asesor = $asesor;
        $this->tecnico = $tecnico;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Repuesto despachado - Visita técnica #' . ($this->visita->ID ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion-repuesto-despachado',
            with: [
                'visita' => $this->visita,
                'destinatario' => $this->destinatario,
                'repuestos' => $this->repuestos,
                'url' => $this->url,
                'asesor' => $this->asesor,
                'tecnico' => $this->tecnico,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
