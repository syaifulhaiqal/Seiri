<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for every app notification: always shown in the app's bell menu, and emailed when the user
 * has email notifications on.
 *
 * The in-app copy stores the untranslated message and its parameters, so it shows in whatever language
 * the reader picks later. The email is queued; the in-app copy is written straight away.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    /**
     * The message with :placeholders, written in English and translated when shown.
     */
    abstract protected function message(): string;

    /**
     * @return array<string, string|int>
     */
    abstract protected function parameters(): array;

    abstract protected function url(): string;

    /**
     * Bootstrap icon name, e.g. bi-calendar2-check.
     */
    abstract protected function icon(): string;

    /**
     * Bootstrap colour for the icon.
     */
    protected function context(): string
    {
        return 'primary';
    }

    /**
     * The email subject, with the same :placeholders as the message, plus :app for the app name.
     */
    abstract protected function subject(): string;

    protected function actionText(): string
    {
        return 'Open :app';
    }

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->email_notifications && $notifiable->hasVerifiedEmail() ? ['database', 'mail'] : ['database'];
    }

    /**
     * The in-app copy is written in the request; only the email waits for the queue worker.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__($this->subject(), [...$this->parameters(), 'app' => config('app.name')]))
            ->greeting(__('Hi :name,', ['name' => str($notifiable->name)->before(' ')->toString()]))
            ->line(__($this->message(), $this->parameters()))
            ->action(__($this->actionText(), ['app' => config('app.name')]), $this->url())
            ->line(__('You can turn these emails off on your Profile page.'));
    }

    /**
     * @return array{message: string, parameters: array<string, string|int>, url: string, icon: string, context: string}
     */
    public function toArray(User $notifiable): array
    {
        return [
            'message' => $this->message(),
            'parameters' => $this->parameters(),
            'url' => $this->url(),
            'icon' => $this->icon(),
            'context' => $this->context(),
        ];
    }
}
