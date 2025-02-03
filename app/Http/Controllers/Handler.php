<?php

namespace App\Http\Controllers;

use App\Services\AuthHandler;
use App\Services\CommandHandler;
use App\Services\CutsHandler;
use App\Services\MenuHandler;
use App\Services\ProjectsHandler;
use App\Services\StartHandler;
use App\Services\TasksHandler;
use App\Services\TimeHandler;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Http;

class Handler extends WebhookHandler
{

    public function getChatId(): string
    {
        return $this->message->chat()->id();
    }

    public function getCallbackChatId(): string
    {
        return $this->callbackQuery->message()->chat()->id();
    }

    public function getMessageId(): int
    {
        return $this->message->id();
    }

    public function getCallbackMessageId(): int
    {
        return $this->callbackQuery->message()->id();
    }

    public function start(): void
    {
        (new StartHandler())->sendStartMessage($this->message ? $this->getChatId() : $this->getCallbackMessageId());
    }

    public function auth(): void
    {
        (new AuthHandler())->sendAuthMessage($this->message ? $this->getChatId() : $this->getCallbackMessageId());
    }

    public function menu(): void
    {
        (new MenuHandler())->sendMenu($this->message ? $this->getChatId() : ($this->getCallbackChatId()));
    }

    public function projects(): void
    {
        (new ProjectsHandler())->sendProjects($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function cuts(): void
    {
        (new CutsHandler())->sendCuts($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function startTime(): void
    {
        (new TimeHandler())->sendStartTime($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function endTime(): void
    {
        (new TimeHandler())->sendEndTime($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function pickProject(): void
    {
        (new ProjectsHandler())->sendPickProjects(
            $this->getCallbackChatId(),
            $this->getCallbackMessageId()
        );
    }

    public function pickTask(int $projectId): void
    {
        $chatId = $this->getCallbackChatId();

        cache()->put("projectId_{$chatId}", $projectId, now()->addMinutes(5));

        app(TasksHandler::class)->sendPickTasks($chatId, $this->getCallbackMessageId(), $projectId);
    }


    public function editLast(): void
    {
        app(CutsHandler::class)->sendEditLast($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function rememberMe(): void
    {
        $chatId = $this->getCallbackChatId();
        $messageId = $this->getCallbackMessageId();
        $login = cache()->get("login_{$chatId}");
        $password = cache()->get("password_{$chatId}");

        Telegraph::where('chat_id', $chatId)->update(['login' => $login, 'password' => $password]);

        Telegraph::chat($chatId)
            ->edit($messageId)
            ->message("Данные сохранены")
            ->send();


    }

    public function handleMessage(): void
    {
        $chatId = $this->message->chat()->id();
        $messageText = $this->message->text();

        if (str_starts_with($messageText, '/')) {
            $this->handleCommand($messageText);
            return;
        }

        if (preg_match('/^[^:]+:[^:]+$/', $messageText)) {
            [$email, $password] = explode(':', $messageText);
            cache()->put("login_{$chatId}", $email, now()->addMinutes(5));
            cache()->put("password_{$chatId}", $password, now()->addMinutes(5));

            $response = Http::post(config('yatt.login_url'), [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $accessToken = $response->json('data.accessToken');
                cache()->put("access_token_{$chatId}", $accessToken, now()->addMinutes(60));

                $message = "Авторизация прошла успешно! Добро пожаловать, $email!\nПиши /menu чтобы попасть в меню";

                Telegraph::chat($chatId)
                    ->message($message)
                    ->send();

            } else {
                $message = "Ошибка авторизации: " . $response->json('message', 'Не удалось выполнить запрос.');

                Telegraph::chat($chatId)
                    ->message($message)
                    ->send();
            }

            Telegraph::chat($chatId)
                ->sticker('CAACAgIAAxkBAAIDk2eXnrR0uCBGKNQcGgy1JJXw0-YjAAIKGQACsd_gS38AAdwhgrIKAAE2BA')
                ->send();
        } else {
            $this->handleEditLastMessage();
        }
    }

    protected function handleCommand(string|\Illuminate\Support\Stringable $command): void
    {
        $chatId = $this->getChatId();
        app(CommandHandler::class)->commands($chatId, $command);
    }

    public function handleEditLastMessage(): void
    {
        $chatId = $this->callbackQuery ? $this->getCallbackChatId() : $this->getChatId();
        $messageId = $this->callbackQuery ? $this->getCallbackMessageId() : $this->getMessageId();
        $messageText = $this->message ? $this->message->text() : ($this->callbackQuery?->message()->text());

        if ($messageText) {
            $description = trim($messageText);
            $projectId = cache()->get("projectId_{$chatId}");
            $taskId = cache()->get("taskId_{$chatId}");
            $cutId = cache()->get("cutId_{$chatId}");
            $accessToken = TelegraphChat::where('chat_id', $chatId)->value('access_token');

            $response = Http::withToken($accessToken)->patch("https://yatt.framework.team/api/times/{$cutId}/update-current", [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'description' => $description,
            ]);

            if ($response->successful()) {
                $message = "Отрезок успешно обновлен!";
            } else {
                $message = "Ошибка обновления: " . $response->json('message', 'Не удалось выполнить запрос.');
            }

            Telegraph::chat($chatId)
                ->message($message)
                ->send();
        } else {
            parent::handleMessage();
        }
    }
}
