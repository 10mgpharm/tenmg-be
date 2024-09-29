<?php

namespace App\Services;

use App\Repositories\RepaymentScheduleRepository;
use App\Services\NotificationService;
use Carbon\Carbon;

class RepaymentReminderService
{
    protected $repaymentScheduleRepository;
    protected $notificationService;

    public function __construct(RepaymentScheduleRepository $repaymentScheduleRepository, NotificationService $notificationService)
    {
        $this->repaymentScheduleRepository = $repaymentScheduleRepository;
        $this->notificationService = $notificationService;
    }

    /**
     * Send reminders for repayments due in 7, 5, 3, or 1 day.
     */
    public function sendReminders()
    {
        $days = [7, 5, 3, 1];

        foreach ($days as $day) {
            $dueDate = Carbon::now()->addDays($day);
            $repayments = $this->repaymentScheduleRepository->getRepaymentsDueOnDate($dueDate);

            foreach ($repayments as $repayment) {
                $this->notificationService->sendRepaymentReminder($repayment);
            }
        }
    }
}
