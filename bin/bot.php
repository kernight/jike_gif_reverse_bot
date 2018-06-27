<?php

namespace bin;

use GuzzleHttp\Client;
use PHPQRCode\Constants;
use PHPQRCode\QRcode;

require_once "../vendor/autoload.php";

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

try {
    /* 第一步 获取uuid */
    $client = new Client();
    $response = parse_response($client->request('get', API_UUID, COMMON_GUZZLE_OPTIONS));
    $uuid = $response['uuid'];
    echo "uuid: {$uuid}\n";

    /* 第二步 拼接扫码字符串 */
    $code_link = "jike://page.jk/web?url=https%3a%2f%2fruguoapp.com%2faccount%2fscan%3fuuid%3d{$uuid}";
    echo "qr code link:: {$code_link}\n";
    build_qr_code($code_link);

    /* 第三步 等待登陆，获取token */
    $step = 60;
    while ($step --){
        try{
            $response = parse_response($client->request('get', API_TOKEN."?uuid={$uuid}", COMMON_GUZZLE_OPTIONS));
            $token = $response['token'];
            echo "login success!\n";
            break;
        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            echo "wait login……\n";
        }
        sleep(1);
    }
    if(!$step){
        echo "login fail.……\n";
    }

    /* 第四步 进入定时步骤，每5s刷新一次,执行逻辑 */
    while(true){
        {
            $response = parse_response($client->request('post', API_TOPIC_MSG, array_merge([
                'form_params'=>[
                    'topic'=>'5a65faf09704e800116cab9c',
                    'limit'=>'1',
                    'loadMoreKey'=>'',
                ]
            ],COMMON_GUZZLE_OPTIONS)));

            $msgData = current($response['data']);
            echo "now id: {$msgData['id']} \n";
        }
        sleep(5);
    }

} catch (\GuzzleHttp\Exception\GuzzleException $e) {
    echo "guzzle error:\t" . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "guzzle error:\t" . $e->getMessage() . "\n";
}

/***
 * 解析guzzle响应对象
 * @param \GuzzleHttp\Psr7\Response $response
 * @return mixed 返回解析后的数组
 * @throws \Exception
 */
function parse_response(\GuzzleHttp\Psr7\Response $response)
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

function build_qr_code($text){
    $code_arr = QRcode::text($text);
    $height = count($code_arr);
    $width = strlen(current($code_arr));
    $black = "\033[40m  \033[0m";
    $white = "\033[47m  \033[0m";
    for ($i = 0; $i < $width; $i++) {
        echo $white;
    }
    echo "\n";
    for ($i = 0; $i < $height; $i++) {
        echo $white;
        for ($j = 0; $j < $width; $j++) {
            if ($code_arr[$i][$j]) {
                echo $black;
            } else {
                echo $white;
            }
        }
        echo $white;
        echo "\n";
    }
    for ($i = 0; $i < $width; $i++) {
        echo $white;
    }
    echo "\n";
}