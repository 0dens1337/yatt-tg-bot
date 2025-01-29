<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthHandler
{
    public function sendAuthMessage(int $chatId): void
    {
        Telegraph::chat($chatId)
            ->message("Введи свои данные в формате login:password чтобы авторизоваться")
            ->send();
    }
}
