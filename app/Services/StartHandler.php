<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;

class StartHandler
{
    public function sendStartMessage(int $chatId): void
    {
        Telegraph::chat($chatId)
            ->message("Краткое описание моих возможностей:\n/auth - авторизация\n/menu - меню.\nПринцип действия таков, Запускаете ятт, потоп останавливаете в любое удобное для вас время рабочего дня, нажимаете редактировать отрезок и выбираете проект, задачу и описываете чем занимались.\nЭто все! Приятного пользования.")
            ->send();
    }
}
