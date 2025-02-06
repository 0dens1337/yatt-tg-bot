<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

class AlarmHandler
{
    public function alarm(int $chatId, int $messageId): void
    {
        Telegraph::chat($chatId)
            ->edit($messageId)
            ->message('Выберете когда вас упомянуть')
            ->keyboard(
                Keyboard::make()->buttons([
//                    Button::make('Через минуту (тест)')->action('afterOneMinute'),
                    Button::make('Через час')->action('afterOneHour'),
                    Button::make('Через 8 часов')->action('afterEightHours'),
                    Button::make('Выбрать время самому (пока не работает)')->action('selfAlarm'),
                    Button::make('Назад')->action('menu')
                ])
            )->send();
    }
}
