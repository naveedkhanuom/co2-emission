<?php

namespace App\Mail;

use App\Models\ScheduledReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers a generated scheduled report to its recipients with the file(s)
 * attached. $files is a list of ['data' => binary, 'name' => string, 'mime' => string].
 */
class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ScheduledReport $report,
        public array $summary,
        public array $files = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Scheduled GHG Report: ' . $this->report->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scheduled_report',
            with: [
                'report'  => $this->report,
                'summary' => $this->summary,
            ],
        );
    }

    public function attachments(): array
    {
        return collect($this->files)
            ->map(fn ($f) => Attachment::fromData(fn () => $f['data'], $f['name'])->withMime($f['mime']))
            ->all();
    }
}
