<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportSubmittedConfirmation extends Notification
{
    public function __construct(public Report $report) {}

    public function via(object $notifiable): array
    {
        return config('jiacrs.mail_notifications') ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'Report received: ' . $this->report->tracking_number,
            'message' => 'Your report was received and is waiting for review.',
            'url'     => route('my.reports.show', $this->report),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('JIACRS: we received your report ' . $this->report->tracking_number)
            ->line('Your report was received and will be reviewed.')
            ->action('View report', route('my.reports.show', $this->report));
    }
}
