<?php

namespace App\Models\Traits;

use App\Enums\RandomSentenceEnum;
use DefStudio\Telegraph\Facades\Telegraph;

trait SendAlarmMessage
{
    public function sendAlarmMessage($chatId): void
    {
        $sentences = RandomSentenceEnum::cases();
        $randomSentence = $sentences[array_rand($sentences)]->value;

        Telegraph::chat($chatId)
            ->message($randomSentence)
            ->send();
    }
}
