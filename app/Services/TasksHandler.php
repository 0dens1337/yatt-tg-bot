<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Http;

class TasksHandler
{
    public function sendPickTasks(int $chatId, int $messageId, int $projectId, int $page = 1): void
    {
        $accessToken = cache()->get("access_token_{$chatId}");

        $response = Http::withToken($accessToken)->get(config('yatt.projects_url') . "/projects/{$projectId}/tasks");

        if ($response->successful()) {
            $tasks = $response->json('data');
            $taskPerPage = 5;
            $totalPages = ceil(count($tasks) / $taskPerPage);
            $offset = ($page - 1) * $taskPerPage;
            $tasks = array_slice($tasks, $offset, $taskPerPage);

            $message = 'Выберите задачу:';
            $buttons = [];

            foreach ($tasks as $task) {
                $buttons[] = Button::make($task['name'])->action('editLast')->param('taskId', $task['id']);
                cache()->put("taskId_{$chatId}", $task['id'], now()->addMinutes(5));
            }

            if ($page > 1) {
                $buttons[] = Button::make('⬅️ Назад')->action('pickTask')->param('page', $page - 1);
            }
            if ($page < $totalPages) {
                $buttons[] = Button::make('➡️ Далее')->action('pickTask')->param('page', $page + 1);
            }

            Telegraph::chat($chatId)
                ->edit($messageId)
                ->message($message)
                ->keyboard(
                    Keyboard::make()->buttons($buttons)
                )->send();
        } else {
            Telegraph::chat($chatId)
                ->edit($messageId)
                ->message('Ошибка: ' . $response->json('message', 'Не удалось выполнить запрос.'))
                ->send();
        }
    }
}
