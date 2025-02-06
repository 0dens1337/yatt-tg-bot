<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;

class MenuHandler
{
    public function sendMenu(int $chatId): void
    {
        $accessToken = cache()->get("access_token_{$chatId}");

        if ($accessToken) {
            Telegraph::chat($chatId)
                ->message('Меню:')
                ->keyboard(
                    Keyboard::make()->buttons([
                        Button::make('Начать')->action('startTime'),
                        Button::make('Закончить')->action('endTime'),
                        Button::make('Проекты')->action('projects'),
                        Button::make('Отрезки')->action('cuts'),
                        Button::make('Создать напоминание')->action('alarm')
                    ])
                )->send();
        } else {
            Telegraph::chat($chatId)
                ->message('Для доступа к меню необходимо авторизоваться.')
                ->send();
        }
    }
}
