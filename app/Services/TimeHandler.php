<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Http;

class TimeHandler
{
    public function sendStartTime(int $chatId, int $messageId): void
    {
        $accessToken = cache()->get("access_token_{$chatId}");

        $response = Http::withToken($accessToken)->patch('https://yatt.framework.team/api/times/start');

        if ($response->successful()) {
            Telegraph::chat($chatId)
                ->edit($messageId)
                ->message('Отрезок начат')
                ->keyboard(
                    Keyboard::make()->buttons([
                        Button::make('Закончить')->action('endTime'),
                        Button::make('Создать напоминание?')->action('alarm'),
                        Button::make('Назад')->action('menu'),
                    ])
                )
                ->send();
        } else {
            Telegraph::chat($chatId)
                ->edit($messageId)
                ->message('Че та пошло не так, наверное ты не авторизован либо уже начал отрезок')
                ->send();
        }
    }

    public function sendEndTime(int $chatId, int $messageId): void
    {
        $accessToken = cache()->get("access_token_{$chatId}");

        $response = Http::withToken($accessToken)->patch('https://yatt.framework.team/api/times/stop');

        if ($response->successful()) {
            Telegraph::chat($chatId)
                ->edit($messageId)
                ->message('Отрезок завершен')
                ->keyboard(
                    Keyboard::make()->buttons([
                        Button::make('Начать заново?')->action('startTime'),
                        Button::make('Редактировать отрезок')->action('pickProject'),
                        Button::make('Назад')->action('menu'),
                    ])
                )
                ->send();
        } else {
            Telegraph::chat($chatId)
                ->edit($messageId)
                ->message('Че та пошло не так, наверное ты не авторизован либо не начал отрезок')
                ->send();
        }
    }
}
