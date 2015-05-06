<?php
/**
 * 特征链接：
 * 1.页面链接http://www.xuebang.com.cn/schoolId3249，url+学校id
 * 2.获取图片的链接http://www.xuebang.com.cn/servlet/showImage?type=college&cid=3249,url+学校id
 * 3.简介地址http://www.xuebang.com.cn/sIntro/3249,url+学校id
 * 4.院系地址http://www.xuebang.com.cn/3249/deptlist
 * 5.图片存放目录md5(学校),取前4位建目录
 * 
 */
$dir = __DIR__;
require_once $dir.'\lib\httpUtil.php';
require_once $dir.'\pinyin.php';
class gatherCollege{
    private static $successFile='/phpStudy/WWW/myGather/college/log/collegesuccess';
    private static $errorFile='/phpStudy/WWW/myGather/college/log/collegeerror';
    private static $excepFile='/phpStudy/WWW/myGather/college/log/collegeexception.log';
    private static $handle = null;
    public static $matchConfig = array(
        'name' => "/<H1 class=\"schoolname\">\r\n(.*)\r\n/",
        'type' => "/<span class=\"zrystrong\">类型：<\/span>(.*)/",
        'belong' => "/<span class=\"zrystrong\" style=\"margin-left: 50px;\">隶属于：<\/span>(.*)<\/p>/",
        '211' => "/(211工程)/",
        '985' => "/(985工程)/",
        'address' => "/<span class=\"zrystrong\">学校地址：<\/span>(.*)<\/p>/",
        'site' => "/<span class=\"zrystrong\">官方网站：<\/span><a href=\"(.*)\" class/",
        );
    public function __construct($shard) {
        self::$successFile .= $shard.'.log';
        self::$errorFile .= $shard.'.log';
    }
    public function collect($id) {
        // self::$handle = curl_init();
        $flag = false;
        $image='';
        $url = 'http://www.xuebang.com.cn/schoolId'.$id;
        $descUrl = 'http://www.xuebang.com.cn/sIntro/'.$id;
        $departUrl = 'http://www.xuebang.com.cn/'.$id.'/deptlist';
        $contentArray = self::getHtml($url);
        // var_dump($contentArray);exit;
        $content = $contentArray['html'];
        $handle = $contentArray['handle'];
        $collegeName = self::getData($content,'name');//名称
        if($collegeName){
            $type = self::getData($content,'type');//类型
            $belong = self::getData($content,'belong');//隶属
            $get211 =  self::getData($content,'211');//是否211
            $get985 = self::getData($content,'985');//是否985
            $address = self::getData($content,'address');//地址
            $site = self::getData($content,'site');//网址
            $desc = self::getDesc($descUrl,$handle);//简介
            $nP = self::getNatureProvince($collegeName,$handle);//性质，用学校名搜索，从结果中匹配性质
            $nature = isset($nP['nature']) ? $nP['nature'] : '';
            $province = isset($nP['province']) ? $nP['province'] : '';
            $departList = self::getDepartList($departUrl,$handle);//院系
            $image = self::fetchImage($id,$collegeName);
            $data = array(
                'collegeName' => $collegeName,
                'type' => $type,
                'belong' => $belong,
                'is211' => ($get211) ? 1 : 0,
                'is985' => ($get985) ? 1 : 0,
                'address' => $address,
                'site' => $site,
                'desc' => $desc,
                'nature' => $nature,
                'province' => $province,
                'departList' => $departList,
                'image' => $image
                );
            // var_dump($data);exit;
            $collegeId = self::saveData($data);
            $flag = ($collegeId > 0) ? true : false;
        }
        // $city = self::getData($content,'city');//城市
        curl_close($handle);
        $result = array('image' => $image, 'url' => $url, 'name' => $collegeName, 'id' => (isset($collegeId) ? $collegeId : 0));
        self::writeLog($result,$flag);
    }
    
    /**
     * 获取页面内容
     * @param type $url
     * @return type
     */
    public static function getHtml($url,$handle='') {
        if(!$handle)    $handle = curl_init();
        $timeout = 50;
        $useragent='Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';//模拟爬虫
        // $useragent='Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.94 Safari/537.36';//模拟浏览器用户
        $header = array('Accept-Language:zh-cn', 'Connection:Keep-Alive', 'Cache-Control:no-cache');
        $cookie = 'a2666_times=13; pgv_pvi=9235347456; Hm_lvt_8147cdaed425fa804276ea12cd523210=1429498382,1429514686,1429771110,1429846391; CNZZDATA5928106=cnzz_eid%3D179770302-1428034623-http%253A%252F%252Fmantis.kongfz.com%252F%26ntime%3D1429854879; Hm_lvt_863e19f68502f1ae0f9af1286bb12475=1429498382,1429514686,1429771110,1429846392; _ga=GA1.3.451142169.1428035318; deptNumOf685=16; deptNumOf11=140; deptNumOf3833=5; deptNumOf3662=14; deptNumOf3457=19; deptNumOf3435=45; commentNumOf466=1169; deptNumOf111=6; deptNumOf1181=13; deptNumOf101=20; deptNumOf3101=9; deptNumOf1239=13; JSESSIONID=abcsdCgM-i5s8exn0DPZu; pgv_si=s4336802816; Hm_lpvt_8147cdaed425fa804276ea12cd523210=1429858173; Hm_lpvt_863e19f68502f1ae0f9af1286bb12475=1429858173; deptNumOf1701=7; YJS=c5ab217ce76f2950188dc50bcb2fc172,MTQyOTg3Nzg1MQ==; deptNumOf214=24; deptNumOf260=24; deptNumOf2305=13; a2666_pages=8; deptNumOf257=33; deptNumOf284=27; deptNumOf2380=6';
        curl_setopt($handle, CURLOPT_COOKIE, $cookie);
        curl_setopt($handle, CURLOPT_REFERER, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_USERAGENT, $useragent);
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        $html = self::getRedirect($handle,0);
        // var_dump($html);exit;
    //    $html = safeEncoding($html);
        $html = self::str2utf8($html);
        // curl_close($handle);
        return array('html'=>$html, 'handle'=>$handle);
    }

    /**
     * 获取未转码的页面信息，抓图片内容
     * @param type $url
     * @return type
     */
    public static function getRawHtml($url, $handle='') {
        if(!$handle) $handle = curl_init();
        $timeout = 50;
        $useragent='Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';
        // $useragent='Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.94 Safari/537.36';//模拟浏览器用户
        $header = array('Accept-Language:zh-cn', 'Connection:Keep-Alive', 'Cache-Control:no-cache');
        $cookie = 'a2666_times=13; pgv_pvi=9235347456; Hm_lvt_8147cdaed425fa804276ea12cd523210=1429498382,1429514686,1429771110,1429846391; CNZZDATA5928106=cnzz_eid%3D179770302-1428034623-http%253A%252F%252Fmantis.kongfz.com%252F%26ntime%3D1429854879; Hm_lvt_863e19f68502f1ae0f9af1286bb12475=1429498382,1429514686,1429771110,1429846392; _ga=GA1.3.451142169.1428035318; deptNumOf685=16; deptNumOf11=140; deptNumOf3833=5; deptNumOf3662=14; deptNumOf3457=19; deptNumOf3435=45; commentNumOf466=1169; deptNumOf111=6; deptNumOf1181=13; deptNumOf101=20; deptNumOf3101=9; deptNumOf1239=13; JSESSIONID=abcsdCgM-i5s8exn0DPZu; pgv_si=s4336802816; Hm_lpvt_8147cdaed425fa804276ea12cd523210=1429858173; Hm_lpvt_863e19f68502f1ae0f9af1286bb12475=1429858173; deptNumOf1701=7; YJS=c5ab217ce76f2950188dc50bcb2fc172,MTQyOTg3Nzg1MQ==; deptNumOf214=24; deptNumOf260=24; deptNumOf2305=13; a2666_pages=8; deptNumOf257=33; deptNumOf284=27; deptNumOf2380=6';
        curl_setopt($handle, CURLOPT_COOKIE, $cookie);
        curl_setopt($handle, CURLOPT_REFERER, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_USERAGENT, $useragent);
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        $html =  self::getRedirect($handle,0);
        // curl_close($handle);
        return array('html'=>$html, $handle);
    }

    public static function getRawHtmlNew($url, $handle='') {
        $handle = curl_init();
        $timeout = 30;
        $useragent='Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';
        // $useragent='Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.94 Safari/537.36';//模拟浏览器用户
        $header = array('Accept-Language:zh-cn', 'Connection:Keep-Alive', 'Cache-Control:no-cache');
        $cookie = 'a2666_times=13; pgv_pvi=9235347456; Hm_lvt_8147cdaed425fa804276ea12cd523210=1429498382,1429514686,1429771110,1429846391; CNZZDATA5928106=cnzz_eid%3D179770302-1428034623-http%253A%252F%252Fmantis.kongfz.com%252F%26ntime%3D1429854879; Hm_lvt_863e19f68502f1ae0f9af1286bb12475=1429498382,1429514686,1429771110,1429846392; _ga=GA1.3.451142169.1428035318; deptNumOf685=16; deptNumOf11=140; deptNumOf3833=5; deptNumOf3662=14; deptNumOf3457=19; deptNumOf3435=45; commentNumOf466=1169; deptNumOf111=6; deptNumOf1181=13; deptNumOf101=20; deptNumOf3101=9; deptNumOf1239=13; JSESSIONID=abcsdCgM-i5s8exn0DPZu; pgv_si=s4336802816; Hm_lpvt_8147cdaed425fa804276ea12cd523210=1429858173; Hm_lpvt_863e19f68502f1ae0f9af1286bb12475=1429858173; deptNumOf1701=7; YJS=c5ab217ce76f2950188dc50bcb2fc172,MTQyOTg3Nzg1MQ==; deptNumOf214=24; deptNumOf260=24; deptNumOf2305=13; a2666_pages=8; deptNumOf257=33; deptNumOf284=27; deptNumOf2380=6';
        curl_setopt($handle, CURLOPT_COOKIE, $cookie);
        curl_setopt($handle, CURLOPT_REFERER, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_USERAGENT, $useragent);
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        $html = curl_exec($handle);
        curl_close($handle);
        return array('html'=>$html, $handle);
    }

    public static function getRedirect($handle,$num=0){
        $html = curl_exec($handle);
        $info = curl_getinfo($handle);
        // var_dump($info);#exit;
        if(isset($info['redirect_url']) && $info['redirect_url']) {
            $url = $info['redirect_url'];
            curl_setopt($handle, CURLOPT_URL, $url);
            self::writeExceLog($url);
            if($num == 3) {
                return '';
            }
            $num = $num+1;
            sleep(10);
            return self::getRedirect($handle,$num);
        } else {
            return $html;
        }
    }
    /**
     * 字符转换
     * @param type $content
     * @return type
     */
    public static function str2utf8($content) {
        $encode  = mb_detect_encoding($content , array('UTF-8','ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        if($encode != 'UTF-8') {
            $content = mb_convert_encoding($content,'UTF-8',array('UTF-8','ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        }
        return $content;
    }
    /**
     * 从内容中匹配指定格式的字段
     */
    public static function getData($content, $name){
        $matchExpr = self::$matchConfig[$name];
        preg_match($matchExpr, $content, $match);
        if(isset($match[1])) {
            return trim($match[1]);
        } else {
            return '';
        }  
    }
    /**
     * 从页面内容解析简介
     * @param type $content
     * @return string
     */
    public static function getDesc($url, $handle) {
        $descContent = self::getHtml($url, $handle);
        // var_dump($descContent);exit;
        $content = $descContent['html'];
        preg_match("/<p class=\"Introductionsclool\">\r\n(.*)\r\n<\/p>/", $content, $match);
        if(isset($match[1])) {
            $match[1] = preg_replace(array("/<strong>/","/<\/strong>/"), array(""), $match[1]);
            $match[1] = trim($match[1]);
            return $match[1];
        } else {
            return '';
        }
    }
    /**
     * 从页面内容解析院系
     * @param type $content
     * @return string
     */
    public static function getDepartList($url, $handle) {
        $departlist = array();
        $descContent = self::getHtml($url, $handle);
        // var_dump($descContent);exit;
        $content = $descContent['html'];
        $preg = "/<a href=\".*\" class=\"yxcologe\" target=\"_blank\">\r\n(.*)<\/a>/";
        preg_match_all($preg, $content, $matches);
        if(is_array($matches[1]) && count($matches[1])){
            foreach ($matches[1] as $key => $value) {
                $departlist[] = trim($value);
            }
        }
        return $departlist;
    }

    public static function getNatureProvince($name, $handle) {
        $url = 'http://www.xuebang.com.cn/collegedb/list';
        curl_setopt($handle, CURLOPT_URL, $url);
        $data['schoolName'] = $name;
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        $info = curl_exec($handle);
        $contents = preg_replace("/\r\n/", "", $info);
        preg_match("/<tr class=\"tr2\">(.*)<\/tr>/", $contents,$match);
        // var_dump($match);
        $res = isset($match[1]) ? $match[1] : '';
        $res = preg_split("/<\/td>[\s]+<td>/", $res);
        // var_dump($res);exit;
        $result['nature'] = isset($res[3]) ? trim($res[3]) : '';
        $result['province'] = isset($res[1]) ? trim($res[1]) : '';
        // var_dump($result);exit;
        return $result;
        // var_dump($result);
    }
    /**
     * 从页面内容解析图片地址并保存图片
     * @param type $content
     * @param type $authorName
     * @return string
     */
    public static function fetchImage($id, $authorName){
        $url = 'http://www.xuebang.com.cn/servlet/showImage?type=college&cid='.$id;
        $imageSrc = self::getRawHtmlNew($url);
        if($imageSrc['html']) {
            $imgMd5 = md5($authorName);
            $dirPath = substr($imgMd5, 0,3);
            $filePath = $dirPath."/".$imgMd5.'.jpg';
            is_dir('/phpStudy/WWW/myGather/college/image/'.$dirPath) || mkdir('/phpStudy/WWW/myGather/college/image/'.$dirPath,0777,true);
//                    var_dump(is_dir($dirPath));exit;
            $res = file_put_contents('/phpStudy/WWW/myGather/college/image/'.$filePath, $imageSrc['html']);
            if($res) {
                return $filePath;
            }
        }
        return '';
    }
    /**
     * 数据入库
     * @param type $data
     */
    public static function saveData($data){
        $collegeId = 0;
        $dbo = new PDO('mysql:dbname=college;charset=utf8;host=localhost', 'root', 'root');
        $dbo->beginTransaction();
        try {
            $sql = "insert into collegeInfo (collegeName,type, belong,is211,is985,address,site,nature,province ) values
             ('{$data['collegeName']}',
                '{$data['type']}',
                '{$data['belong']}',
                '{$data['is211']}',
                '{$data['is985']}',
                '{$data['address']}',
                '{$data['site']}',
                '{$data['nature']}',
                '{$data['province']}')";
            $dbo->exec($sql);
            $collegeId = $dbo->lastInsertId();
            $sql = "insert into collegeDesc (collegeId,image,collegeDesc) values
             ($collegeId,'{$data['image']}','{$data['desc']}')";
             // var_dump($sql);exit;
            $dbo->exec($sql);
            if(is_array($data['departList']) && count($data['departList'])) {
                $values = implode("'),($collegeId,'", $data['departList']);
                $values = "($collegeId,'".$values."')";
                // var_dump($values);exit;
                $sql = "insert into department (collegeId,departmentName) values $values";
                $dbo->exec($sql);
            } 

            $dbo->commit();
        } catch (PDOException  $e) {
            $info = $e->getMessage();
            self::writeExceLog($info);
            $dbo->rollBack();
        }
        $dbo = null;
        return $collegeId;
    }
    /**
     * 记录日志
     * @param type $data
     * @param type $flag true:成功日志，false:失败日志
     */
    public static function writeLog($data,$flag,$info =''){
        $content = "url:{$data['url']};image:{$data['image']};name:{$data['name']};id:{$data['id']}";
        $flag ? file_put_contents(self::$successFile, date('Y-m-d H:i:s').$content."\r\n", FILE_APPEND) : file_put_contents(self::$errorFile, date('Y-m-d H:i:s').$content.$info."\r\n", FILE_APPEND);
    }
    /**
     * 记录错误日志
     * @param type $data
     * @param type $flag true:成功日志，false:失败日志
     */
    public static function writeExceLog($info){
        file_put_contents(self::$excepFile, date('Y-m-d H:i:s').$info."\r\n", FILE_APPEND) ;
    }
}



