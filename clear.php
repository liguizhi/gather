<?php

// $url = 'http://www.xuebang.com.cn/11/deptlist';
// $contents = getHtml($url);
// $con = $contents['html'];
var_dump(fetchImage(11,''));
function getHtml($url,$handle='') {
    if(!$handle)    $handle = curl_init();
    $timeout = 50;
    // $useragent='Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';//模拟爬虫
    $useragent='Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.94 Safari/537.36';//模拟浏览器用户
    $header = array('Accept-Language:zh-cn', 'Connection:Keep-Alive', 'Cache-Control:no-cache');
    $cookie = 'pgv_pvi=9952082944; deptNumOf2921=9; deptNumOf656=32; deptNumOf1656=8; deptNumOf2656=6; deptNumOf3000=8; deptNumOf3200=10; deptNumOf3230=11; deptNumOf3231=12; deptNumOf3241=12; deptNumOf3248=16; deptNumOf3249=9; commentNumOf18095=0; commentNumOf18089=17; commentNumOf18091=75; commentNumOf33090=0; a1589_times=1; Hm_lvt_2f41e97729216342ff4721c441879e60=1428996376; commentNumOf16=400; deptNumOf11=140; JSESSIONID=abczw6AtZimcycsAnTuZu; pgv_si=s5761983488; a2666_pages=2; a2666_times=8; Hm_lvt_8147cdaed425fa804276ea12cd523210=1428629093,1428994726,1429145272,1429498095; Hm_lpvt_8147cdaed425fa804276ea12cd523210=1429498671; CNZZDATA5928106=cnzz_eid%3D1439838148-1428559895-%26ntime%3D1429495260; _ga=GA1.3.578089251.1428560421; Hm_lvt_863e19f68502f1ae0f9af1286bb12475=1428629093,1428994726,1429145272,1429498095; Hm_lpvt_863e19f68502f1ae0f9af1286bb12475=1429498672';
    curl_setopt($handle, CURLOPT_COOKIE, $cookie);
    curl_setopt($handle, CURLOPT_REFERER, $url);
    curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
    curl_setopt($handle, CURLOPT_USERAGENT, $useragent);
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
//    curl_setopt($handle, CURLOPT_HEADER, 1);
    $html = curl_exec($handle);
    $info = curl_getinfo($handle);
    if(isset($info['redirect_url']) && $info['redirect_url']) {
        $url = $info['redirect_url'];
        curl_setopt($handle, CURLOPT_URL, $url);
        $html = curl_exec($handle);
    }
//    $html = safeEncoding($html);
    $html = str2utf8($html);
    // curl_close($handle);
    return array('html'=>$html, 'handle'=>$handle);
}

    function str2utf8($content) {
        $encode  = mb_detect_encoding($content , array('UTF-8','ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        if($encode != 'UTF-8') {
            $content = mb_convert_encoding($content,'UTF-8',array('UTF-8','ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        }
        return $content;
    }

    function fetchImage($id, $authorName){
        $url = 'http://www.xuebang.com.cn/servlet/showImage?type=college&cid='.$id;
        $imageSrc = getRawHtml($url);
        if($imageSrc['html']) {
            $imgMd5 = md5($authorName);
            $dirPath = substr($imgMd5, 0,3);
            $filePath = $dirPath."/".$imgMd5.'.jpg';
            is_dir('/phpStudy/WWW/myGather/college/'.$dirPath) || mkdir('/phpStudy/WWW/myGather/college/'.$dirPath,0777,true);
//                    var_dump(is_dir($dirPath));exit;
            $res = file_put_contents('/phpStudy/WWW/myGather/college/'.$filePath, $imageSrc['html']);
            if($res) {
                return $filePath;
            }
        }
        return '';
    }

    function getRawHtml($url, $handle='') {
        if(!$handle) $handle = curl_init();
        $timeout = 50;
        $useragent='Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';
        $useragent='Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.94 Safari/537.36';//模拟浏览器用户
        $header = array('Accept-Language:zh-cn', 'Connection:Keep-Alive', 'Cache-Control:no-cache');
        $cookie = 'pgv_pvi=9952082944; deptNumOf2921=9; deptNumOf656=32; deptNumOf1656=8; deptNumOf2656=6; deptNumOf3000=8; deptNumOf3200=10; deptNumOf3230=11; deptNumOf3231=12; deptNumOf3241=12; deptNumOf3248=16; deptNumOf3249=9; commentNumOf18095=0; commentNumOf18089=17; commentNumOf18091=75; commentNumOf33090=0; a1589_times=1; Hm_lvt_2f41e97729216342ff4721c441879e60=1428996376; commentNumOf16=400; deptNumOf11=140; JSESSIONID=abczw6AtZimcycsAnTuZu; pgv_si=s5761983488; a2666_pages=2; a2666_times=8; Hm_lvt_8147cdaed425fa804276ea12cd523210=1428629093,1428994726,1429145272,1429498095; Hm_lpvt_8147cdaed425fa804276ea12cd523210=1429498671; CNZZDATA5928106=cnzz_eid%3D1439838148-1428559895-%26ntime%3D1429495260; _ga=GA1.3.578089251.1428560421; Hm_lvt_863e19f68502f1ae0f9af1286bb12475=1428629093,1428994726,1429145272,1429498095; Hm_lpvt_863e19f68502f1ae0f9af1286bb12475=1429498672';
        curl_setopt($handle, CURLOPT_COOKIE, $cookie);
        curl_setopt($handle, CURLOPT_REFERER, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_USERAGENT, $useragent);
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        $html = curl_exec($handle);
        // curl_close($handle);
        return array('html'=>$html, $handle);
    }