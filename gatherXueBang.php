<?php
/**
 * 特征链接：
 * 1.页面链接http://www.xuebang.com.cn/teacherId315303，url+教师id
 * 2.获取图片的链接http://www.xuebang.com.cn/showImages?fileID=28230,url+1中匹配的fileID
 * 3.图片真实链接http://www.xuebang.com.cn/images/college/teachers_thumbnails/2008/5/29/VJ_FORUM_82099.tmp
 * 4.图片存放目录md5(作者名),取前4位建目录
 * 
 */

/*
 * 1、数据入库

（1）采集的头像 -> 作家专题数据库中的头像字段
（2）采集的人名 -> 作家专题数据库中的人名字段
注意：入库时，无论是否存在同名的人物，均新增记录，而不是覆盖原记录。
（3）采集的人名的拼音 -> 作家专题数据库中的URL拼音字段
注意：入库时，如果存在同名的人物，则URL拼音字段后添加数字ID。
例如：原库中，存在2个人名“刘晓波”，其URL分别是：www.kongfz.com/writer/liuxiaobo/和www.kongfz.com/writer/liuxiaobo2/，采集来的人名中包含同名人物“刘晓波”，则入库后，新记录的URL拼音字段值为：liuxiaobo{ID}，即该页面的URL为：www.kongfz.com/writer/liuxiaobo{ID}/

2、数据展示

将采集来的（2）人名 （3）大 学 （4）院 系 （5）省 市 （6）状 态 （7）人物简介 几个数据项的值合并，去除可能存在的HTML代码，作为 作家专题库中的 人名简介 字段值进行入库操作

入库字段：
 author.authorName
 author.authorNamePinyin
 author.authorUrl
 
 authorsDesc.image
 authorsDesc.details

 * 
 *  */
require_once 'pinyin.php';
class gatherXueBang{
    private static $successFile='/phpStudy/WWW/gather/log/success';
    private static $errorFile='/phpStudy/WWW/gather/log/error';
    public function __construct($shard) {
        self::$successFile .= $shard.'.log';
        self::$errorFile .= $shard.'.log';
    }
    public function collect($url) {
        $content = self::getHtml($url);
        $authorName = self::getName($content);
        $flag = false;
        $imgUrl='';
        if($authorName) {
            $authorNamePinyin = pinyin($authorName);
            if($authorNamePinyin) {
                $college = self::getCollege($content);
                $department = self::getDepartment($content);
                $city = self::getCity($content);
                $desc = self::getDesc($content);
                $status = self::getStatus($content);
                $details = self::getDetails($authorName, $college, $department, $city, $status, $desc);
                $imgUrl = self::fetchImage($content, $authorName);
                $data = array(
                    'authorName' => $authorName,
                    'authorNamePinyin' => $authorNamePinyin,
                    'authorUrl' => $authorNamePinyin,
                    'source' => 5,
                    'details' => $details,
                    'image' => $imgUrl
                );
//				var_dump($data);
                $insertId = $this->saveData($data);
                $flag = true;
            }
        } 
        $result = array('image' => $imgUrl, 'url' => $url, 'name' => $authorName, 'id' => (isset($insertId) ? $insertId : 0));
        self::writeLog($result,$flag);
    }
    
//----------------------自定义方法-----------------------------//
    /**
     * 组合作者简介
     * @param type $name
     * @param type $college
     * @param type $department
     * @param type $city
     * @param type $status
     * @param type $desc
     */
    public static function getDetails($name, $college,$department,$city, $status,$desc){
        $details = '';
        if($name) {
            $details .= '姓名：'.$name;
        }
        if($college) {
            $details .= '；大学：'.$college;
        }
        if($department) {
            $details .= '；院系：'. $department;
        }
        if($city) {
            $details .= '；省市：' .$city;
        }
        if($status) {
            $details .= '；状态：'.$status;
        }
        if($desc) {
            $details .= '.'.$desc;
        }
        return $details;
    }

    /**
     * 获取页面内容
     * @param type $url
     * @return type
     */
    public static function getHtml($url) {
        $handle = curl_init();
        $timeout = 50;
//        $useragent = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.94 Safari/537.36";
        $useragent='Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';
//        $useragent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/6.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C)";
        $header = array('Accept-Language:zh-cn', 'Connection:Keep-Alive', 'Cache-Control:no-cache');
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
//        var_dump($info);exit;
    //    $html = safeEncoding($html);
        $html = self::str2utf8($html);
        curl_close($handle);
        return $html;
    }

    /**
     * 获取未转码的页面信息，抓图片内容
     * @param type $url
     * @return type
     */
    public static function getRawHtml($url) {
        $handle = curl_init();
        $timeout = 50;
//        $useragent = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.94 Safari/537.36";
//        $useragent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/6.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C)";
        $useragent='Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';
        $header = array('Accept-Language:zh-cn', 'Connection:Keep-Alive', 'Cache-Control:no-cache');
        curl_setopt($handle, CURLOPT_REFERER, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_USERAGENT, $useragent);
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        $html = curl_exec($handle);
        curl_close($handle);
        return $html;
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
     * 从页面内容解析简介
     * @param type $content
     * @return string
     */
    public static function getDesc($content) {
        preg_match("/<div class=\"in_test1\">(.*)<\/div>/", $content, $match);
        if(isset($match[1])) {
            return $match[1];
        } else {
            return '';
        }
    }
    /**
     * 从页面内容解析姓名
     * @param type $content
     * @return string
     */
    public static function getName($content) {
        preg_match("/<font color=\"#0088cc\">(.*)<\/font>/", $content, $match);
        if(isset($match[1])) {
            return $match[1];
        } else {
            return '';
        }    
    }
    /**
     * 从页面内容解析大学
     * @param type $content
     * @return string
     */
    public static function getCollege($content) {
        preg_match("/<p> 大 学：<a href=.*>(.*)<\/a><\/p>/", $content, $match);
        if(isset($match[1])) {
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
    public static function getDepartment($content) {
        preg_match("/<p> 院 系：<a .*>(.*)<\/a>/", $content, $match);
        if(isset($match[1])) {
            return $match[1];
        } else {
            return '';
        }   
    }
    /**
     * 从页面内容解析省市
     * @param type $content
     * @return string
     */
    public static function getCity($content) {
        preg_match("/<p> 省 市：<a href=.*>(.*)<\/a>/", $content, $match);
        if(isset($match[1])) {
            return $match[1];
        } else {
            return '';
        }   
    }
    /**
     * 从页面内容解析在职状态
     * @param type $content
     * @return string
     */
    public static function getStatus($content) {
        preg_match("/style=\"color:#0088cc\">(.*)/", $content, $match);
        if(isset($match[1])) {
            return substr($match[1], 0, -1);
        } else {
            return '';
        }   
    }
    /**
     * 从页面内容解析图片地址并保存图片
     * @param type $content
     * @param type $authorName
     * @return string
     */
    public static function fetchImage($content, $authorName){
        preg_match("/fileID = \"(\d*)\"/", $content, $matchId);
        if(isset($matchId[1]) && ($matchId[1])) {
            $fileId = $matchId[1];
            $url = 'http://www.xuebang.com.cn/showImages?fileID='. $fileId.'&type=200';
            $imageSrc = self::getHtml($url);
            preg_match("/src=\"(.*)?\"/",$imageSrc, $imgUrl);
            if(isset($imgUrl[1])){
                if($imgUrl[1] !='http://www.xuebang.com.cn:80/images/college/no_pic_teacher_200.gif'){
                    $imgContent = self::getRawHtml($imgUrl[1]);
                    if($imgContent) {
                        $imgMd5 = md5($authorName.  rand(0, 10000));
                        $dirPath = substr($imgMd5, 0,4);
                        $filePath = $dirPath."/".$imgMd5.'.jpg';
                        is_dir('/phpStudy/WWW/gather/image/'.$dirPath) || mkdir('/phpStudy/WWW/gather/image/'.$dirPath,0777,true);
    //                    var_dump(is_dir($dirPath));exit;
                        $res = file_put_contents('/phpStudy/WWW/gather/image/'.$filePath, $imgContent);
                        if($res) {
                            return $filePath;
                        }
                    }
                }
            }
        }
        return '';
    }
    /**
     * 数据入库
     * @param type $data
     */
    public function saveData($data){
    //     author.authorName
    // author.authorNamePinyin
    // author.authorUrl
    // 
    // authorsDesc.image
    // authorsDesc.details
        $dbo = new PDO('mysql:dbname=booklib;charset=utf8;host=localhost', 'root', 'root');
        $sql = "insert into authors (authorName,authorNamePinyin, authorUrl,source ) values ('{$data['authorName']}','{$data['authorNamePinyin']}','{$data['authorUrl']}','{$data['source']}')";
        $dbo->exec($sql);
        $authorId = $dbo->lastInsertId();
        if($authorId) {
            $sql = "insert into authorsDesc (authorId,image,details) values($authorId,'{$data['image']}','{$data['details']}')";
            $dbo->exec($sql);
        }
        $dbo = null;
        return $authorId;
    }
    /**
     * 记录日志
     * @param type $data
     * @param type $flag true:成功日志，false:失败日志
     */
    public static function writeLog($data,$flag){
//        $successFile = '/phpStudy/WWW/gather/success.log';
//        $errorFile = '/phpStudy/WWW/gather/error.log';
        $content = "url:{$data['url']};image:{$data['image']};name:{$data['name']};id:{$data['id']}";
        $flag ? file_put_contents(self::$successFile, date('Y-m-d H:i:s').$content."\r\n", FILE_APPEND) : file_put_contents(self::$errorFile, date('Y-m-d H:i:s').$content."\r\n", FILE_APPEND);
    }
}



