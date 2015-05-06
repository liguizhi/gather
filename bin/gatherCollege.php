<?php
/**
 * 特征链接：
 * 1.页面链接http://www.xuebang.com.cn/teacherId315303，url+教师id
 * 2.获取图片的链接http://www.xuebang.com.cn/showImages?fileID=28230,url+1中匹配的fileID
 * 3.图片真实链接http://www.xuebang.com.cn/images/college/teachers_thumbnails/2008/5/29/VJ_FORUM_82099.tmp
 * 4.图片存放目录md5(作者名),取前4位建目录
 * 
 */
$dir = __DIR__;
// var_dump($dir);exit;
require_once $dir.'/../gatherCollege.php';

$shard = $argv[1];
$start = getLastId($shard);//获取上次执行的最大记录
switch ($shard) {
    case 1:
        $end = 1700;
        break;
    case 2:
        $end = 3249;
        break;
    default:
        break;
}
// $shard = 1;
$gather = new gatherCollege($shard);
$globalId = 0;
// $url = 'http://www.xuebang.com.cn/schoolId11';
// $gather->collect(1731);
// exit;
for($i=$start; $i<= $end; $i++){
    $url = 'http://www.xuebang.com.cn/schoolId'.$i;
    $gather->collect($i);
    $globalId ++;
    if($globalId == 50) {
        sleep(60);
        $globalId =0;
    }
    usleep(500);
    echo $url."\r\n";
}

function getLastId($shard) {
    // http://www.xuebang.com.cn/schoolId
    $logFile = 'college/log/collegesuccess'.$shard.'.log';
    // var_dump($logFile);exit;
    if(is_file($logFile)) {
        $contents = trim(file_get_contents($logFile));
        $logArray = explode("\r\n", $contents);
        $lastIdStr = array_pop($logArray);
        preg_match("/schoolId(\d+)/",$lastIdStr, $lastIdMatch);
        $resumeId = $lastIdMatch[1]+1;
    } else {
        $resumeId = 1700*($shard-1) + 1;
    }
    return $resumeId;
}