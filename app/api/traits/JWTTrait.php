<?php

namespace App\Api\Traits;

use Firebase\JWT\JWT;
use Think\Request;

trait JWTTrait
{
    /**
     * 通过JWT加密用户数据
     * @param null $data
     * @return string
     */
    protected function JWTEncode($data = null)
    {
        $key = config('app_key');

        $data = $this->getJWTToken($data);

        return JWT::encode($data, $key, 'HS256');
    }

    /**
     * 通过JWT解密用户数据
     * @param $token
     * @return object
     */
    protected function JWTDecode($token)
    {
        $key = config('app_key');

        $data = JWT::decode($token, $key, ['HS256']);

        return json_decode(json_encode($data), true);
    }

    /**
     * 设置JWT数据的有效期
     * @param null $data
     * @return array
     */
    protected function getJWTToken($data = null)
    {
        $token = config('jwt.');

        // Add Token expires 过期时间
        $token['exp'] = Carbon::now()->addDays($token['exp'])->timestamp;

        return array_merge($token, $data);
    }

    /**
     * 返回用户数据的属性
     * @param null $token
     * @param string $header
     * @param string $value
     * @return mixed
     */
    protected function authorization($token = null, $header = 'token', $value = 'user_id')
    {
        if (is_null($token)) {
            $token = Request::header($header);
        }

        $data = $this->JWTDecode($token);

        return $data[$value];
    }
}
