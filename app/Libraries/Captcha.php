<?php

namespace App\Libraries;

class Captcha
{
    /**
     * 背景图片所在目录
     *
     * @var string $folder
     */
    public $folder = 'data/captcha';

    /**
     * 图片的文件类型
     *
     * @var string $img_type
     */
    public $img_type = 'png';

    /*------------------------------------------------------ */
    //-- 存在session中的名称
    /*------------------------------------------------------ */
    public $session_word = 'captcha_word';

    /**
     * 背景图片以及背景颜色
     *
     * 0 => 背景图片的文件名
     * 1 => Red, 2 => Green, 3 => Blue
     * @var array $themes
     */
    public $themes_jpg = [
        1 => ['captcha_bg1.jpg', 255, 255, 255],
        2 => ['captcha_bg2.jpg', 0, 0, 0],
        3 => ['captcha_bg3.jpg', 0, 0, 0],
        4 => ['captcha_bg4.jpg', 255, 255, 255],
        5 => ['captcha_bg5.jpg', 255, 255, 255],
    ];

    public $themes_gif = [
        1 => ['captcha_bg1.gif', 255, 255, 255],
        2 => ['captcha_bg2.gif', 0, 0, 0],
        3 => ['captcha_bg3.gif', 0, 0, 0],
        4 => ['captcha_bg4.gif', 255, 255, 255],
        5 => ['captcha_bg5.gif', 255, 255, 255],
    ];

    /**
     * 图片的宽度
     *
     * @var integer $width
     */
    public $width = 130;

    /**
     * 图片的高度
     *
     * @var integer $height
     */
    public $height = 20;

    /**
     * 构造函数
     *
     * @access  public
     * @param   string $folder 背景图片所在目录
     * @param   integer $width 图片宽度
     * @param   integer $height 图片高度
     * @return  bool
     */
    public function __construct($folder = '', $width = 145, $height = 20)
    {
        if (!empty($folder)) {
            $this->folder = $folder . '/';
        }

        $this->width = $width;
        $this->height = $height;

        /* 检查是否支持 GD */
        return (function_exists('imagecreatetruecolor') || function_exists('imagecreate'));
    }

    /**
     * 检查给出的验证码是否和session中的一致
     *
     * @access  public
     * @param   string $word 验证码
     * @return  bool
     */
    public function check_word($word)
    {
        $recorded = session()->has($this->session_word) ? base64_decode(session($this->session_word)) : '';
        $given = $this->encrypts_word(strtoupper($word));

        return (preg_match("/$given/", $recorded));
    }

    /**
     * 生成图片并输出到浏览器
     * @param bool $word
     * @return bool|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function generate_image($word = false)
    {
        if (!$word) {
            $word = $this->generate_word();
        }

        /* 记录验证码到session */
        $this->record_word($word);

        /* 验证码长度 */
        $letters = strlen($word);

        /* 选择一个随机的方案 */
        mt_srand((double)microtime() * 1000000);

        if (function_exists('imagecreatefromjpeg') && ((imagetypes() & IMG_JPG) > 0)) {
            $theme = $this->themes_jpg[mt_rand(1, count($this->themes_jpg))];
        } else {
            $theme = $this->themes_gif[mt_rand(1, count($this->themes_gif))];
        }

        if (!file_exists($this->folder . $theme[0])) {
            return false;
        } else {
            $img_bg = (function_exists('imagecreatefromjpeg') && ((imagetypes() & IMG_JPG) > 0)) ?
                imagecreatefromjpeg($this->folder . $theme[0]) : imagecreatefromgif($this->folder . $theme[0]);
            $bg_width = imagesx($img_bg);
            $bg_height = imagesy($img_bg);

            $img_org = ((function_exists('imagecreatetruecolor'))) ?
                imagecreatetruecolor($this->width, $this->height) : imagecreate($this->width, $this->height);

            /* 将背景图象复制原始图象并调整大小 */
            imagecopyresampled($img_org, $img_bg, 0, 0, 0, 0, $this->width, $this->height, $bg_width, $bg_height);
            imagedestroy($img_bg);

            $clr = imagecolorallocate($img_org, $theme[1], $theme[2], $theme[3]);

            /* 获得验证码的高度和宽度 */
            $x = ($this->width - (imagefontwidth(5) * $letters)) / 2;
            $y = ($this->height - imagefontheight(5)) / 2;
            imagestring($img_org, 5, $x, $y, $word, $clr);

            ob_clean();
            ob_start();
            imagepng($img_org);
            imagedestroy($img_org);

            $content = ob_get_clean();
            return response($content, 200, [
                'Content-Type' => 'image/png',
            ]);
        }
    }

    /**
     * 对需要记录的串进行加密
     *
     * @access  private
     * @param   string $word 原始字符串
     * @return  string
     */
    private function encrypts_word($word)
    {
        return substr(md5($word), 1, 10);
    }

    /**
     * 将验证码保存到session
     *
     * @access  private
     * @param   string $word 原始字符串
     * @return  void
     */
    private function record_word($word)
    {
        session($this->session_word, base64_encode($this->encrypts_word($word)));
    }

    /**
     * 生成随机的验证码
     *
     * @access  private
     * @param   integer $length 验证码长度
     * @return  string
     */
    private function generate_word($length = 4)
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        for ($i = 0, $count = strlen($chars); $i < $count; $i++) {
            $arr[$i] = $chars[$i];
        }

        mt_srand((double)microtime() * 1000000);
        shuffle($arr);

        return substr(implode('', $arr), 5, $length);
    }
}
