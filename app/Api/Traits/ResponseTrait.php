<?php

namespace App\Api\Traits;

trait ResponseTrait
{
    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @return int
     */
    protected function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param $statusCode
     * @return $this
     */
    protected function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * 返回封装后的API数据到客户端
     * @param mixed|string $data 要返回的数据
     * @return string
     */
    protected function succeed($data)
    {
        return $this->setStatusCode(200)->resp([
            'status' => 'success',
            'data' => $data,
            'time' => time(),
        ]);
    }

    /**
     * 返回异常数据到客户端
     * @param string $message 要返回的错误消息
     * @return string
     */
    protected function failed($message)
    {
        return $this->setStatusCode(100)->resp([
            'status' => 'failed',
            'errors' => [
                'code' => $this->getStatusCode(),
                'message' => $message,
            ],
            'time' => time(),
        ]);
    }

    /**
     * 返回 Json 数据格式
     * @param $data
     * @return string
     */
    protected function resp($data)
    {
        $code = $this->getStatusCode();

        return $this->response($data, 'json', $code);
    }
}
