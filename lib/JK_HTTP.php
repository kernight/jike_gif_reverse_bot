<?php

namespace lib;

use GuzzleHttp\Client;

class JK_HTTP
{
    CONST API_UUID = 'https://app.jike.ruguoapp.com/sessions.create';
    CONST API_TOKEN = 'https://app.jike.ruguoapp.com/sessions.wait_for_confirmation';
    CONST API_TOKEN_CHECK = 'https://app.jike.ruguoapp.com/1.0/users/profile';
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
     * 根据uuid获取用户token
     * @param string $uuid
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    public function getUserToken($uuid)
    {
        $response = $this->parse_response($this->client->request('get', self::API_TOKEN . "?uuid={$uuid}", self::COMMON_GUZZLE_OPTIONS));
        return $response;
    }

    /****
     * 检测Token是否可用
     * @param array $token
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    public function checkToken($token)
    {
        $response = $this->parse_response($this->client->request('get', self::API_TOKEN_CHECK, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36',
                'x-jike-app-auth-jwt' => $token['token']
            ]
        ]));

        return true;
    }

    /***
     * 发送评论
     * @param $token
     * @param $picKeys
     * @param $msgData
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    public function sendComment($token, $msgData, $picKeys)
    {
        switch ($msgData['messageType']) {
            case 'PERSONAL_UPDATE_ORIGINAL_POST':
                $target_id = $msgData['personalUpdate']['id'];
                $target_type = 'ORIGINAL_POST';
                break;
            case 'NORMAL':
            default:
                $target_id = $msgData['id'];
                $target_type = 'OFFICIAL_MESSAGE';
                break;
        }

        $response = $this->parse_response($this->client->request('post', self::API_COMMENT, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36',
                'x-jike-app-auth-jwt' => $token['token'],
                'platform' => 'web',
                'app-version' => "4.1.0"
            ],
            'json' => [
                'targetId' => $target_id,
                'content' => 'BOT自动反转了图片',
                'pictureKeys' => $picKeys,
                'syncToPersonalUpdates' => true,
                'targetType' => $target_type,
            ]
        ]));

        return true;
    }


    /****
     * 根据话题id获取最新一条消息
     * @param $topic_id
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException | \Exception
     */
    public function getTopicMsg($topic_id)
    {
        $response = $this->parse_response($this->client->request('post', self::API_TOPIC_MSG, array_merge([
            'form_params' => [
                'topic' => $topic_id,
                'limit' => '1',
                'loadMoreKey' => '',
            ]
        ], self::COMMON_GUZZLE_OPTIONS)));

        return current($response['data']);
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