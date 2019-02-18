<?php

namespace app\shop\controller;

use app\libraries\Captcha as BaseCaptcha;

class Captcha extends Init
{
    public function index()
    {
        $img = new BaseCaptcha(public_path('data/captcha'), $GLOBALS['_CFG']['captcha_width'], $GLOBALS['_CFG']['captcha_height']);

        if (isset($_REQUEST['is_login'])) {
            $img->session_word = 'captcha_login';
        }

        return $img->generate_image();
    }
}
