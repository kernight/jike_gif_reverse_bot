<?php

namespace bin;

use GuzzleHttp\Client;
use lib\HTTP;
use lib\QNUploader;
use PHPQRCode\QRcode;

require_once "vendor/autoload.php";

spl_autoload_register(function ($class){
    $path = str_replace("/",DIRECTORY_SEPARATOR,$class);
    $path = str_replace("\\",DIRECTORY_SEPARATOR,$class);
    $path .= ".php";

    require_once $path;
});
$config = require_once "config/config.php";

try {
    $http_client = new HTTP();
    $qn_client = new QNUploader();

    /**检测旧有的uuid**/
    $uuid = '';
    $uuid_path = "runtime/uuid";
    if(is_file($uuid_path)){
        $uuid = file_get_contents($uuid_path);
        try {
            $token = $http_client->getUserToken($uuid);
            echo "login success!\n";
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $uuid = '';
        }
    }

    /*获取新的uuid和token */
    if(!$uuid){
        $uuid = $http_client->getUUID();
        file_put_contents($uuid_path,$uuid);
        echo "uuid: {$uuid}\n";

        /*拼接扫码字符串 */
        $code_link = "jike://page.jk/web?url=https%3a%2f%2fruguoapp.com%2faccount%2fscan%3fuuid%3d{$uuid}";
        echo "qr code link:: {$code_link}\n";
        build_qr_code($code_link);

        /*等待登陆，获取token */
        $step = 60;
        echo "wait login……\n";
        while (--$step) {
            try {
                $token = $http_client->getUserToken($uuid);
                echo "login success!\n";
                break;
            } catch (\Exception $e) {
            }
            sleep(1);
        }

        if (!$step) {
            echo "login fail.……\n";
            die();
        }
    }


    /*进入定时步骤，每5s刷新一次,执行逻辑 */
    while (true) {
        {
            foreach ($config['topic'] as $topic){
                $msgData = $http_client->getTopicMsg($topic['id']);
                echo "now topic: {$topic['name']} \n";
                foreach ($msgData['pictureUrls'] as $picUrl) {
                    echo "now picUrl: {$picUrl['picUrl']} update_at:{$msgData['updatedAt']} \n";
                }
            }
        }
        sleep(5);
    }

} catch (\GuzzleHttp\Exception\GuzzleException $e) {
    echo "guzzle error:\t" . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "guzzle error:\t" . $e->getMessage() . "\n";
}



/***
 * 根据文本生成二维码
 * @param $text
 */
function build_qr_code($text)
{
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