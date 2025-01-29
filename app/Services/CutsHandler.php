<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Http;

class CutsHandler
{
    public function sendCuts(int $chatId, int $messageId): void
    {
        $accessToken = TelegraphChat::where('chat_id', $chatId)->value('access_token');

        $response = Http::withToken($accessToken)->get('https://yatt.framework.team/api/times');

        if ($response->successful()) {
            $cuts = $response->json('data');

            $message = "Отрезки:\n";
            foreach ($cuts as $dateKey => $data) {
                $date = $data['date'] ?? $dateKey;
                $durationInSeconds = end($data['times'])['duration'] ?? 0;
                $duration = gmdate('H:i', $durationInSeconds);

                foreach ($data['times'] as $time) {
                    $projectName = $time['project']['name'] ?? 'Без проекта';
                    $taskName = $time['task']['name'] ?? 'Без задачи';

                    $message .= "🔹Дата: $date ➡️ Проект: $projectName\n🔴 Задача: $taskName ➡️ Длительность: $duration\n";
                }
            }
        } else {
            $message = "Ошибка: " . $response->json('message', 'Не удалось выполнить запрос.');
        }

        Telegraph::chat($chatId)
            ->edit($messageId)
            ->message($message)
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make('Назад')->action('menu'),
                    Button::make('Изменить')->action('edit'),
                    Button::make('Создать')->action('pickProject'),
                ])
            )
            ->send();
    }

    public function sendEditLast(int $chatId, int $messageId): void
    {
        $accessToken = TelegraphChat::where('chat_id', $chatId)->value('access_token');

        $response = Http::withToken($accessToken)->get('https://yatt.framework.team/api/times');

        if ($response->successful()) {
            $cuts = $response->json('data');

            if (!empty($cuts)) {
                $dates = array_keys($cuts);
                rsort($dates);

                $latestDate = $dates[0];
                $times = $cuts[$latestDate]['times'] ?? [];

                if (!empty($times)) {
                    $lastCut = $times[0];
                    $firstId = $lastCut['id'];
                    cache()->put("cutId_{$chatId}", $firstId, now()->addMinutes(5));

                    $message = "Опиши чем ты занимался!";
                } else {
                    $message = "Отрезки времени за дату {$latestDate} не найдены.";
                }
            } else {
                $message = "Не удалось найти данные о временных отрезках.";
            }
        } else {
            $message = 'Ошибка: ' . $response->json('message', 'Не удалось выполнить запрос.');
        }

        Telegraph::chat($chatId)
            ->edit($messageId)
            ->message($message)
            ->send();
    }
}
