<?php

namespace App\Jobs;

use App\Enums\InAppNotificationType;
use App\Services\InAppNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;

class SendInAppNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public InAppNotificationType $type,
        public Collection | User | null $users = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new InAppNotificationService();

        if ($this->users instanceof Collection) {
            $service->forUsers($this->users);
        } elseif ($this->users instanceof User) {
            $service->forUser($this->users);
        }

        $service->notify($this->type);
    }
}
