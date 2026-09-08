<?php

namespace App\Mail;

use App\Models\DailyClosure;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyZReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DailyClosure $closure) {}

    /**
     * Build email envelope with subject
     */
    public function envelope(): Envelope
    {
        $date = $this->closure->closed_at->format('d/m/Y');
        $formattedTotal = number_format($this->closure->total_ttc, 2);

        return new Envelope(
            subject: "📊 Z-Report #{$this->closure->z_number} Sealed ({$date}) — €{$formattedTotal}",
        );
    }

    /**
     * Build email content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily_z_report',
            with: [
                'closure' => $this->closure,
            ],
        );
    }

    /**
     * Attach the PDF Z-Report document
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.z_report', [
            'closure' => $this->closure,
        ]);

        $fileName = "Z-Report-{$this->closure->z_number}-{$this->closure->closed_at->format('Ymd')}.pdf";

        return [
            Attachment::fromData(fn () => $pdf->output(), $fileName)
                ->withMime('application/pdf'),
        ];
    }
}