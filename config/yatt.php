<?php

return [
    'projects_url' => env('YATT_GET_PROJECTS_URL', "https://yatt.framework.team/api/projects"),
    'cuts_url' => env('YATT_GET_CUTS_URL', "https://yatt.framework.team/api/times"),
    'cut_start' => env('YATT_CUT_START', 'https://yatt.framework.team/api/times/start'),
    'cut_stop' => env('YATT_CUT_STOP', 'https://yatt.framework.team/api/times/stop'),
    'login_url' => env('YATT_LOGIN_URL', "https://yatt.framework.team/api/login")
];
