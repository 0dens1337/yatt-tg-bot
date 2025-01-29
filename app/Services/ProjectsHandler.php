<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Http;

class ProjectsHandler
{
    public function sendProjects(int $chatId, int $messageId): void
    {
        $accessToken = TelegraphChat::where('chat_id', $chatId)->value('access_token');

        $response = Http::withToken($accessToken)->get('https://yatt.framework.team/api/projects'); //https://yatt-dev.framework.team/api/projects

        if ($response->successful()) {
            $projects = $response->json('data');

            $message = "Проекты:\n";
            foreach ($projects as $project) {
                $message .= "🔹 {$project['name']}\n";
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
                ])
            )
            ->send();
    }

    public function sendPickProjects(int $chatId, int $messageId, int $page = 1): void
    {
        $accessToken = TelegraphChat::where('chat_id', $chatId)->value('access_token');

        $response = Http::withToken($accessToken)->get('https://yatt.framework.team/api/projects');

        if ($response->successful()) {
            $projects = $response->json('data');
            $projectsPerPage = 5;
            $totalPages = ceil(count($projects) / $projectsPerPage);
            $offset = ($page - 1) * $projectsPerPage;
            $projects = array_slice($projects, $offset, $projectsPerPage);

            $message = 'Выберите проект или создайте:';
            $buttons = [];

            foreach ($projects as $project) {
                $buttons[] = Button::make($project['name'])
                    ->action('pickTask')
                    ->param('projectId', $project['id']);
            }

            if ($page > 1) {
                $buttons[] = Button::make('⬅️ Назад')->action('pickProject')->param('page', $page - 1);
            }
            if ($page < $totalPages) {
                $buttons[] = Button::make('➡️ Далее')->action('pickProject')->param('page', $page + 1);
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
