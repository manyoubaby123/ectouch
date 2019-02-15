<?php

namespace App\Proxy;

use Carbon\Carbon;
use GuzzleHttp\Client;

/**
 * Class ShippingProxy
 * @package App\Proxies
 */
class ShippingProxy
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $queryExpressUrl = 'aHR0cDovL3NwMC5iYWlkdS5jb20vOV9RNHNqVzkxUWgzb3RxYnBwbk4yREp2L3BhZS9jaGFubmVsL2RhdGEvYXN5bmNxdXJ5P2FwcGlkPTQwMDEmY29tPSVzJm51PSVz';

    /**
     * ShippingProxy constructor.
     * @param $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $com
     * @param string $num
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getExpress($com = '', $num = '')
    {
        $url = sprintf(base64_decode($this->queryExpressUrl), $com, $num);

        $cache_id = md5($url);
        $result = cache($cache_id);

        if (is_null($result)) {
            $response = $this->client->get($url, $this->defaultOptions());
            $result = json_decode($response->getBody(), true);
            cache([$cache_id => $result], Carbon::now()->addHours(1));
        }

        if ($result['error_code'] === '0') {
            return ['error' => 0, 'data' => $result['data']['info']];
        } else {
            return ['error' => 403, 'data' => $result['msg']];
        }
    }

    /**
     * 默认参数
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.' . time(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'Cookie' => 'BAIDUID=751A380F4F4F8FB7F348EB4E64E9FACF:FG=1', // TODO 获取BAIDUID
                'Host' => 'sp0.baidu.com',
                'Pragma' => 'no-cache',
                'Upgrade-Insecure-Requests' => '1',
            ]
        ];
    }
}
