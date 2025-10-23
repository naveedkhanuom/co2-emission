<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Proposal;

class ProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $proposal;
    public $subjectText;
    public $bodyText;
    public $pdf;

    public function __construct(Proposal $proposal, $subjectText, $bodyText, $pdf)
    {
        $this->proposal = $proposal;
        $this->subjectText = $subjectText;
        $this->bodyText = $bodyText;
        $this->pdf = $pdf;
    }

    public function build()
    {
        return $this->subject($this->subjectText)
                    ->html($this->bodyText)
                    ->attachData($this->pdf->output(), "proposal_{$this->proposal->id}.pdf");
    }
}
