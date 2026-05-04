<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\EmailCampaign;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmailCampaign $campaign,
        public Customer $customer
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName    = Setting::get('smtp_from_name') ?: Setting::get('restaurant_name', config('app.name'));

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.campaign');
    }
}
