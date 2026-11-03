<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Notifications\Concerns\PatientChannels;
use App\Support\AppointmentRules;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification
{
    use PatientChannels;

    private string $when;
    private string $doctor;
    private string $dept;
    private string $who;

    public function __construct(public Appointment $appointment)
    {
        $this->when   = AppointmentRules::slotAt($appointment)?->format('D, d M Y \a\t g:i A') ?? '';
        $this->doctor = $appointment->doctor?->displayName() ?? 'your doctor';
        $this->dept   = $appointment->doctor?->department?->name ?? '';
        $this->who    = $appointment->dependent ? $appointment->dependent->label() : 'you';
    }

    public function via($notifiable): array
    {
        return $this->patientChannels($notifiable);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Appointment reminder — ' . $this->when)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("This is a reminder of the appointment for {$this->who} with {$this->doctor}" . ($this->dept ? " ({$this->dept})" : '') . '.')
            ->line('Time: ' . $this->when)
            ->line('Please check in when you arrive at the hospital.')
            ->action('View my appointments', route('patient.appointments'))
            ->line('Need to change it? You can reschedule or cancel up to ' . AppointmentRules::CUTOFF_HOURS . ' hours before.');
    }

    public function toSms($notifiable): string
    {
        return "Reminder: appointment with {$this->doctor} on {$this->when}. Please check in on arrival. - " . config('app.name');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'           => 'appointment_reminder',
            'title'          => 'Appointment tomorrow',
            'message'        => "Appointment for {$this->who} with {$this->doctor} on {$this->when}.",
            'appointment_id' => $this->appointment->id,
            'url'            => route('patient.appointments'),
        ];
    }
}