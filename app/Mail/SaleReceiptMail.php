<?php

namespace App\Mail;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Comprobante de venta enviado al cliente por correo, con el PDF adjunto. Se encola para no
 * bloquear la venta.
 */
class SaleReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Sale $sale) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Comprobante de venta '.$this->sale->sale_code,
        );
    }

    public function content(): Content
    {
        $this->cargar();

        return new Content(markdown: 'emails.sale_receipt', with: ['sale' => $this->sale]);
    }

    public function attachments(): array
    {
        $this->cargar();

        $sale = $this->sale;
        $company = $sale->branch?->company;

        return [
            Attachment::fromData(
                // La arrow fn solo captura variables usadas literalmente; con compact('sale')
                // no las capturaría, así que se pasan como arreglo explícito.
                fn () => Pdf::loadView('sales.pdf', ['sale' => $sale, 'company' => $company])->output(),
                'Comprobante-'.$sale->sale_code.'.pdf'
            )->withMime('application/pdf'),
        ];
    }

    private function cargar(): void
    {
        $this->sale->loadMissing(['details.product', 'details.unit', 'customer', 'branch.company', 'user']);
    }
}
