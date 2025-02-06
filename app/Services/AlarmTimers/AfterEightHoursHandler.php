<?php

namespace App\Services\AlarmTimers;

use App\Models\Traits\SendAlarmMessage;
use DefStudio\Telegraph\Facades\Telegraph;

class AfterEightHoursHandler
{
    use SendAlarmMessage;

    public function afterEightHours(int $chatId, int $messageId): void
    {
        Telegraph::chat($chatId)
            ->edit($messageId)
            ->message('Окей, я напомню вам ровно через 8 часов')
            ->send();

        sleep(28800);

        $this->sendAlarmMessage($chatId);
    }
}
