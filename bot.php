<?php

namespace bin;

use lib\GIF;
use lib\JK_HTTP;
use lib\QN_HTTP;
use PHPQRCode\QRcode;

require_once "vendor/autoload.php";

spl_autoload_register(function ($class) {
    $path = str_replace("/", DIRECTORY_SEPARATOR, $class);
    $path = str_replace("\\", DIRECTORY_SEPARATOR, $class);
    $path .= ".php";

    require_once $path;
});
$config = require_once "config/config.php";

try {
    $jk_client = new JK_HTTP();
    $qn_client = new QN_HTTP();

    /**检测旧有的uuid**/
    $token_path = "runtime/token";
    $token = '';
    if (is_file($token_path)) {
        $token = unserialize(file_get_contents($token_path));
        try {
            $jk_client->checkToken($token);
            echo "login success!\n";
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $token = '';
        }
    }

    /*获取新的uuid和token */
    if (!$token) {
        $uuid = $jk_client->getUUID();
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
                $token = $jk_client->getUserToken($uuid);
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

        file_put_contents($token_path, serialize($token));
    }

    /*进入定时步骤，每5s刷新一次,执行逻辑 */
    while (true) {
        {
            echo date("Y-m-d H:i:s")."\n";
            $jk_client->checkToken($token);
            foreach ($config['topic'] as $topic) {
                $msgData = $jk_client->getTopicMsg($topic['id']);
                $topic_comment_switch = "runtime/comment/{$topic['id']}_{$msgData['id']}";
                echo "now topic: {$topic['name']}\t";
                if (is_file($topic_comment_switch)) {
                    echo "no new posts.\n";
                } else {
                    $picKeys = [];
                    switch ($msgData['messageType']) {
                        case 'PERSONAL_UPDATE_ORIGINAL_POST':
                            $pictureUrls = $msgData['personalUpdate']['pictures'];
                            break;
                        case 'NORMAL':
                        default:
                            $pictureUrls = $msgData['pictureUrls'];
                            break;
                    }
                    foreach ($pictureUrls as $picUrl) {
                        echo "\npicUrl: {$picUrl['picUrl']}\n";
                        $imgCacheFile = 'runtime/cache/' . md5($picUrl['picUrl']) . '.gif';
                        $imgCacheFileReverse = 'runtime/cache/' . md5($picUrl['picUrl']) . '_reverse.gif';
                        file_put_contents($imgCacheFile, file_get_contents($picUrl['picUrl']));
                        if (!is_file($imgCacheFile)) {
                            echo "download file fail \n";
                            continue;
                        }
                        if (!GIF::reserve($imgCacheFile, $imgCacheFileReverse)) {
                            echo "reserve fail \n";
                            continue;
                        }

                        $picKeys[] = $qn_client->uploadImage($imgCacheFileReverse);
                    }

                    if (count($picKeys)) {
                        $jk_client->sendComment($token, $msgData, $picKeys);
                    }
                    touch($topic_comment_switch);
                }

            }
        }
        sleep(5);
    }

} catch (\GuzzleHttp\Exception\GuzzleException $e) {
    echo "guzzle error:\t" . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "system error:\t" . $e->getMessage() . "\n";
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