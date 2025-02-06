<?php

namespace App\Services;

use App\Models\Project;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Http;

class ProjectsHandler
{
    public function sendProjects(int $chatId, int $messageId): void
    {
        $accessToken = cache()->get("access_token_{$chatId}");

        $response = Http::withToken($accessToken)->get(config('yatt.projects_url')); //https://yatt-dev.framework.team/api/projects

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
        $accessToken = cache()->get("access_token_{$chatId}");

        $response = Http::withToken($accessToken)->get('https://yatt.framework.team/api/projects');

        if ($response->successful())
        {
            $projects = $response->json('data');
            $this->saveProjects($projects);
        }

        $projects = Project::orderBy('project_id', 'desc')->get();

        $projectsPerPage = 99;
        $totalPages = ceil($projects->count() / $projectsPerPage);
        $offset = ($page - 1) * $projectsPerPage;
        $projects = $projects->slice($offset, $projectsPerPage);

        $message = 'Выберите проект:';
        $buttons = [];

        foreach ($projects as $project) {
            $buttons[] = Button::make($project->name)
                ->action('pickTask')
                ->param('projectId', $project->project_id);
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
    }

    public function saveProjects(array $projects): void
    {

        foreach ($projects as $projectData)
        {
            Project::query()->updateOrCreate(['name' => $projectData['name'], 'project_id' => $projectData['id']]);
        }
    }

}
