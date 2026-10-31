<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDisciplineCase extends Notification
{
    use Queueable;

    public function __construct(
        public int $caseId,
        public string $caseNumber,
        public string $studentName,
        public string $violationCode,
        public string $violationName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'case_id' => $this->caseId,
            'case_number' => $this->caseNumber,
            'student_name' => $this->studentName,
            'violation_code' => $this->violationCode,
            'violation_name' => $this->violationName,
            'action_url' => route('kasus-pelanggaran.show', $this->caseId),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}