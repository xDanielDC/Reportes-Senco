<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionSolicitudCotizacion extends Mailable
{
    use Queueable, SerializesModels;

    public $visita;
    public $asesor;
    public $repuestos;
    public $url;
    public $tecnicoNombre;

    /**
     * Create a new message instance.
     */
    public function __construct($visita, $asesor, $repuestos = [], $url = null, $tecnicoNombre = null)
    {
        $this->visita = $visita;
        $this->asesor = $asesor;
        $this->repuestos = $repuestos;
        $this->url = $url;
        $this->tecnicoNombre = $tecnicoNombre;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de cotización - Visita técnica #' . $this->visita->ID,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion-solicitud-cotizacion',
            with: [
                'visita' => $this->visita,
                'asesor' => $this->asesor,
                'repuestos' => $this->repuestos,
                'url' => $this->url,
                'tecnicoNombre' => $this->tecnicoNombre,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
