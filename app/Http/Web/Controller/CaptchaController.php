<?php

namespace App\Http\Web\Controller;

use App\Libraries\Captcha;

class CaptchaController extends InitController
{
    public function index()
    {
        $img = new Captcha(public_path('data/captcha'), $GLOBALS['_CFG']['captcha_width'], $GLOBALS['_CFG']['captcha_height']);

        if (isset($_REQUEST['is_login'])) {
            $img->session_word = 'captcha_login';
        }

        return $img->generate_image();
    }
}
