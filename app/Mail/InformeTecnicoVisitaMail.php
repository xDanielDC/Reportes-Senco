<?php

namespace App\Mail;

use App\Models\Senco360\VisitaEncab;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InformeTecnicoVisitaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VisitaEncab $visita,
        public string $pdfContenido,
        public string $nombreArchivo
    ) {
    }

    public function envelope(): Envelope
    {
        $esCapacitacion = str_contains(
            strtolower($this->visita->tipoServicio?->TIPO_SERVICIO ?? ''),
            'capacit'
        );

        return new Envelope(
            subject: ($esCapacitacion ? 'Informe de capacitación - Visita técnica #' : 'Informe de visita técnica - Visita técnica #')
                . ($this->visita->ID ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        $esCapacitacion = str_contains(
            strtolower($this->visita->tipoServicio?->TIPO_SERVICIO ?? ''),
            'capacit'
        );

        return new Content(
            view: $esCapacitacion
                ? 'emails.informe-capacitacion-visita'
                : 'emails.informe-tecnico-visita',
            with: [
                'visita' => $this->visita,
                'capacitacion' => $this->visita->detalle->first(),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContenido, $this->nombreArchivo)
                ->withMime('application/pdf'),
        ];
    }
}
