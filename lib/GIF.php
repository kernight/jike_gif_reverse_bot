<?php
namespace lib;

use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\parse_response;

class QN_HTTP {
    CONST API_QINIU_TOKEN = 'http://upload.jike.ruguoapp.com/token?bucket=jike';
    CONST API_QINIU_UPLOAD = 'http://up.qiniup.com';
    CONST COMMON_GUZZLE_OPTIONS = [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36'
        ]
    ];

    protected $client;
    public function __construct()
    {
        $this->client = new Client();
    }

    /***
     * 上传文件
     * @param string $path 文件路径
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    public function uploadImage($path)
    {
        $token = $this->getQNToken();
        $response = $this->parse_response($this->client->request('post', self::API_QINIU_UPLOAD, array_merge([
            'multipart' => [
                [
                    'name'     => 'token',
                    'contents' => $token
                ],
                [
                    'name'     => 'file',
                    'contents' => fopen($path, 'r')
                ],
            ]
        ],self::COMMON_GUZZLE_OPTIONS)));
        return $response['key'];
    }

    /****
     * 获取七牛上传token
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    protected function getQNToken()
    {
        $response = $this->parse_response($this->client->request('get', self::API_QINIU_TOKEN, self::COMMON_GUZZLE_OPTIONS));
        return $response['uptoken'];
    }


    /***
     * 解析guzzle响应对象
     * @param \GuzzleHttp\Psr7\Response $response
     * @return mixed 返回解析后的数组
     * @throws \Exception
     */
    protected function parse_response(\GuzzleHttp\Psr7\Response $response)
    {
        if (200 != $response->getStatusCode()) {
            throw new \Exception("status code error:\t" . $response->getStatusCode());
        }
        $content = json_decode($response->getBody()->getContents(), true);
        if (!$content) {
            throw new \Exception("response content  error:\t" . $content);
        }

        return $content;
    }

}