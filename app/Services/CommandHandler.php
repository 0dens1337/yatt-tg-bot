<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;

class CommandHandler
{
    private StartHandler $startHandler;
    private AuthHandler $authHandler;
    private MenuHandler $menuHandler;

    public function __construct()
    {
        $this->startHandler = new StartHandler();
        $this->authHandler = new AuthHandler();
        $this->menuHandler = new MenuHandler();
    }

    public function commands(int $chatId, string $command): void
    {
        match ($command) {
            '/start' => $this->startHandler->sendStartMessage($chatId),
            '/auth' => $this->authHandler->sendAuthMessage($chatId),
            '/menu' => $this->menuHandler->sendMenu($chatId),
            default => Telegraph::chat($chatId)
                ->message("Неизвестная команда: $command")
                ->send(),
        };
    }
}
