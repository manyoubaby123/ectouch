<?php

namespace app\shop\controller;

class Pm extends Init
{
    public function index()
    {
        if (empty(session('user_id')) || $GLOBALS['_CFG']['integrate_code'] == 'ectouch') {
            return ecs_header('Location:./');
        }

        uc_call("uc_pm_location", [session('user_id')]);
    }
}
