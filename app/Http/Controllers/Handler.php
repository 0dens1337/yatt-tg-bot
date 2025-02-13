<?php

namespace App\Http\Controllers;

use App\Services\AlarmHandler;
use App\Services\AlarmTimers\AfterEightHoursHandler;
use App\Services\AlarmTimers\AfterOneHourHandler;
use App\Services\AlarmTimers\AfterOneMinuteHandler;
use App\Services\AuthHandler;
use App\Services\CutsHandler;
use App\Services\MenuHandler;
use App\Services\ProjectsHandler;
use App\Services\StartHandler;
use App\Services\TasksHandler;
use App\Services\TimeHandler;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Facades\Http;

class Handler extends WebhookHandler
{
    private StartHandler $startHandler;
    private AuthHandler $authHandler;
    private MenuHandler $menuHandler;

    public function __construct()
    {
        parent::__construct();
        $this->startHandler = new StartHandler();
        $this->authHandler = new AuthHandler();
        $this->menuHandler = new MenuHandler();
    }

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
        (new MenuHandler())->sendMenu($this->message ? $this->getChatId() : ($this->getCallbackChatId()), $this->message ? $this->getMessageId() : $this->getCallbackMessageId());
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
        (new ProjectsHandler())->sendPickProjects($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function pickTask(int $projectId): void
    {
        $chatId = $this->getCallbackChatId();

        cache()->put("projectId_{$chatId}", $projectId, now()->addMinutes(5));

        app(TasksHandler::class)->sendPickTasks($chatId, $this->getCallbackMessageId(), $projectId);
    }


    public function editLast(int $taskId): void
    {
        $chatId = $this->getCallbackChatId();

        cache()->put("taskId_{$chatId}", $taskId, now()->addMinutes(5));

        app(CutsHandler::class)->sendEditLast($chatId, $this->getCallbackMessageId());
    }

    public function alarm(): void
    {
        (new AlarmHandler())->alarm($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function afterOneMinute(): void
    {
        app(AfterOneMinuteHandler::class)->afterOneMinute($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function afterOneHour(): void
    {
        app(AfterOneHourHandler::class)->afterOneHour($this->getCallbackChatId(), $this->getCallbackMessageId());
    }

    public function afterEightHours(): void
    {
        app(AfterEightHoursHandler::class)->afterEightHours($this->getCallbackChatId(), $this->getCallbackMessageId());
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
        } else{
            $this->handleEditLastMessage();
        }
    }

    protected function handleCommand(string|\Illuminate\Support\Stringable $command): void
    {
        match ($command) {
            '/start' => $this->startHandler->sendStartMessage($this->getChatId()),
            '/auth' => $this->authHandler->sendAuthMessage($this->getChatId()),
            '/menu' => $this->menuHandler->sendMenu($this->getChatId()),
            default => parent::handleCommand($command),
        };
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
            $accessToken = cache()->get("access_token_{$chatId}");

            $response = Http::withToken($accessToken)->patch("https://yatt.framework.team/api/times/{$cutId}/update-current", [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'description' => $description,
            ]);

            match ($response->successful())
            {
                true => $message = "Отрезок успешно обновлен!",
                false => $message = "Ошибка обновления: " . $response->json('message', 'Не удалось выполнить запрос.'),

            };

            Telegraph::chat($chatId)
                ->message($message)
                ->send();

            $this->menu();

        } else {
            parent::handleMessage();
        }
    }
}
