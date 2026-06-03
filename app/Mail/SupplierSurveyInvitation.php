<?php

namespace App\Mail;

use App\Models\SupplierSurvey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplierSurveyInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupplierSurvey $survey,
        public bool $isReminder = false
    ) {
    }

    public function envelope(): Envelope
    {
        $title = $this->survey->title ?: 'Supplier Emissions Survey';

        return new Envelope(
            subject: $this->isReminder ? "Reminder: {$title}" : $title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.supplier_survey',
            with: [
                'survey' => $this->survey,
                'isReminder' => $this->isReminder,
                'link' => route('supplier_portal.survey.show', $this->survey->public_token),
                'companyName' => $this->survey->company?->name,
                'supplierName' => $this->survey->supplier?->name,
                'dueDate' => $this->survey->due_date,
                'expiresAt' => $this->survey->public_token_expires_at,
            ],
        );
    }
}
