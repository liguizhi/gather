<?php
/**
 * http工具类
 */
class HttpUtil{
    /**
     * 获取页面内容
     * @param type $url
     * @return type
     */
    public static function getHtml($url,$handle='',$needClose=false) {
        if(!$handle)    $handle = curl_init();
        $timeout = 50;
        $useragent='Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';
        $header = array('Accept-Language:zh-cn', 'Connection:Keep-Alive', 'Cache-Control:no-cache');
        curl_setopt($handle, CURLOPT_REFERER, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_USERAGENT, $useragent);
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        $html = curl_exec($handle);
        $info = curl_getinfo($handle);
        if(isset($info['redirect_url']) && $info['redirect_url']) {
            $url = $info['redirect_url'];
            curl_setopt($handle, CURLOPT_URL, $url);
            $html = curl_exec($handle);
        }
//        var_dump($info);exit;
        $html = self::str2utf8($html);
        if($needClose) curl_close($handle);
        return array('html'=>$html, 'handle'=>$handle, 'status' => $needClose);
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
     * 重启路由
     */
    public function restartRouter($config)
    {
        $router = $config['router']['routerIp'];
        $username = $config['router']['username'];
        $password = $config['router']['password'];
        $headerArr = array(
            $config['router']['authorization'],
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://{$router}/userRpm/SysRebootRpm.htm?Reboot=%D6%D8%C6%F4%C2%B7%D3%C9%C6%F7");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
        sleep(10);
    }
}