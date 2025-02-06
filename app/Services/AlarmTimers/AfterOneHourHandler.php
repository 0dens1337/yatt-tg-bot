<?php

namespace App\Services\AlarmTimers;

use App\Models\Traits\SendAlarmMessage;
use DefStudio\Telegraph\Facades\Telegraph;

class AfterOneHourHandler
{
    use SendAlarmMessage;

    public function afterOneHour(int $chatId, int $messageId): void
    {
        Telegraph::chat($chatId)
            ->edit($messageId)
            ->message("Хорошо я напомню вам ровно через 1 час")
            ->send();

        sleep(3600);

        $this->sendAlarmMessage($chatId);
    }
}
