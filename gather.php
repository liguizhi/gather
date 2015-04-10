<?php
/**
 * 特征链接：
 * 1.页面链接http://www.xuebang.com.cn/teacherId315303，url+教师id
 * 2.获取图片的链接http://www.xuebang.com.cn/showImages?fileID=28230,url+1中匹配的fileID
 * 3.图片真实链接http://www.xuebang.com.cn/images/college/teachers_thumbnails/2008/5/29/VJ_FORUM_82099.tmp
 * 4.图片存放目录md5(作者名),取前4位建目录
 * 
 */
require_once 'gatherXueBang.php';
$shard = $argv[1];
$start = getLastId($shard);//获取上次执行的最大记录
switch ($shard) {
    case 1:
        $end = 200000;
        break;
    case 2:
//        $start = 200130;
        $end = 400000;
        break;
    case 3:
//        $start = 402456;
        $end = 600000;
        break;
    case 4:
//        $start = 600139;
        $end = 800000;
        break;
    case 5:
//        $start = 802806;
        $end = 1000000;
        break;
    case 6:
//        $start = 1000155;
        $end = 1200000;
        break;
    case 7:
//        $start = 1200155;
        $end = 1338829;
        break;
    default:
        break;
}
$gather = new gatherXueBang($shard);
$globalId = 0;
for($i=$start; $i<= $end; $i++){
    /*
     * 调试目录方法
    //    $content = 'test'.$i;
    //    $rand = substr(md5($content), 0,2);
    //    $dirPath = '/phpStudy/WWW/gather/image/'.$rand;
    //    is_dir($dirPath) || mkdir($dirPath,0777,true);
    //    $filePath = $dirPath."/$i.txt";
    //    $res = file_put_contents($filePath, $content);
    //    continue;
     */
    $url = 'http://www.xuebang.com.cn/teacherId'.$i;
    $gather->collect($url);
    $globalId ++;
    if($globalId == 140) {
        sleep(60);
        $globalId =0;
    }
    usleep(500);
    echo $url."\r\n";
}

function getLastId($shard) {
    $logFile = 'log/success'.$shard.'.log';
    if(is_file($logFile)) {
        $contents = trim(file_get_contents($logFile));
        $logArray = explode("\r\n", $contents);
        $lastIdStr = array_pop($logArray);
        preg_match("/teacherId(\d+)/",$lastIdStr, $lastIdMatch);
        $resumeId = $lastIdMatch[1]+1;
    } else {
        $resumeId = 200000*($shard-1) + 1;
    }
    return $resumeId;
}