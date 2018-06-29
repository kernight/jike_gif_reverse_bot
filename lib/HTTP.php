<?php
namespace lib;

use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\parse_response;

class HTTP {
    CONST API_UUID = 'https://app.jike.ruguoapp.com/sessions.create';
    CONST API_TOKEN = 'https://app.jike.ruguoapp.com/sessions.wait_for_confirmation';
    CONST API_TOPIC_MSG = 'https://app.jike.ruguoapp.com/1.0/messages/history';
    CONST API_QINIU_TOKEN = 'http://upload.jike.ruguoapp.com/token?bucket=jike';
    CONST API_COMMENT = 'https://app.jike.ruguoapp.com/1.0/comments/add';
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

    /****
     * 获取uuid
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    public function getUUID()
    {
        $response = $this->parse_response($this->client->request('get', self::API_UUID, self::COMMON_GUZZLE_OPTIONS));
        return $response['uuid'];
    }

    /****
     * 根据uuid获取用户token
     * @param $uuid
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    public function getUserToken($uuid)
    {
        $response = $this->parse_response($this->client->request('get', self::API_TOKEN . "?uuid={$uuid}", self::COMMON_GUZZLE_OPTIONS));
        return $response['token'];
    }



    /****
     * 根据话题id获取最新一条消息
     * @param $topic_id
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    public function getTopicMsg($topic_id){
        $response = $this->parse_response($this->client->request('post', self::API_TOPIC_MSG, array_merge([
            'form_params' => [
                'topic' => $topic_id,
                'limit' => '1',
                'loadMoreKey' => '',
            ]
        ], self::COMMON_GUZZLE_OPTIONS)));

       return current($response['data']);
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