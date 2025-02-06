<?php

namespace App\Services\AlarmTimers;

use App\Models\Traits\SendAlarmMessage;
use DefStudio\Telegraph\Facades\Telegraph;

class AfterOneMinuteHandler
{
    use SendAlarmMessage;

    public function afterOneMinute(int $chatId, int $messageId): void
    {
        Telegraph::chat($chatId)
            ->edit($messageId)
            ->message("Оки доки я напомню вам ровно через 10 секунд")
            ->send();

        sleep(10);

        $this->sendAlarmMessage($chatId);
    }
}
