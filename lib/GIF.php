<?php
namespace lib;

use GifCreator\GifCreator;
use GifFrameExtractor\GifFrameExtractor;
use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\parse_response;

class GIF {
    /***
     * 反转GIF
     *
     * @param $path
     * @param $save_path
     * @return bool
     * @throws \Exception
     */
    public static function reserve($path,$save_path)
    {
        if (GifFrameExtractor::isAnimatedGif($path)) {

            $gfe = new GifFrameExtractor();
            $gfe->extract($path);
            $durations = [];
            $frameImages = [];
            foreach ($gfe->getFrames() as $frame) {
                $frameImages[] = $frame['image'];
                $durations[] = $frame['duration'];
            }
            $gc = new GifCreator();
            $source = $gc->create(array_reverse($frameImages), array_reverse($durations), 0);
            if($source){
                return false !== file_put_contents($save_path,$source);
            }
            return false;
        }

        return false;
    }
}