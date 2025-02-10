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

    public function sendPickProjects(int $chatId, int $messageId): void
    {
        $projectId = 103; //103 - department
        $projectName = Project::where('project_id', $projectId)->value('name');

        $buttons = [
            Button::make($projectName)->action('pickTask')->param('projectId', $projectId)
        ];

        $message = "Выберите проект:";

        Telegraph::chat($chatId)
            ->edit($messageId)
            ->message($message)
            ->keyboard(
                Keyboard::make()->buttons($buttons)
            )->send();
    }

    public function saveProjects(array $projects): void
    {
        foreach ($projects as $projectData) {
            Project::updateOrCreate(
                ['project_id' => $projectData['id']],
                ['name' => $projectData['name']]
            );
        }
    }

//    public function sendPickProjects(int $chatId, int $messageId, int $page = 1): void
//    {
//        $accessToken = cache()->get("access_token_{$chatId}");
//
//        if (!Project::exists()) {
//            $response = Http::withToken($accessToken)->get(config('yatt-dev.dev_projects'));
//
//            if ($response->successful()) {
//                $projects = $response->json('data');
//                $this->saveProjects($projects);
//            }
//        }
//
//        logger($page);
//        $projectsPerPage = 10;
//        $allProjects = Project::all();
//        $totalProjects = $allProjects->count();
//        $totalPages = ceil($totalProjects / $projectsPerPage);
//
//        $projects = $allProjects->slice(($page - 1) * $projectsPerPage, $projectsPerPage);
//
//        $buttons = [];
//        foreach ($projects as $project) {
//            $buttons[] = Button::make($project->name)->action('pickTask')->param('projectId', $project->project_id);
//        }
//
//        if ($page > 1) {
//            $buttons[] = Button::make('⬅️ Назад')->action('pickProject')->param('page', $page - 1);
//        }
//
//        if ($page < $totalPages) {
//            $buttons[] = Button::make('➡️ Далее')->action('pickProject')->param('page', $page + 1);
//        }
//
//        $message = "Выберите проект:";
//
//        Telegraph::chat($chatId)
//            ->edit($messageId)
//            ->message($message)
//            ->keyboard(
//                Keyboard::make()->buttons($buttons)
//            )->send();
//    }
}
