<?php

class Tools
{
    //将飞行时长(秒)转换成(小时:分钟)格式
    public static function flightTimeFormat($seconds_count)
    {
        $h      = floor($seconds_count / 3600);
        $tmp    = $seconds_count % 3600;
        $minute = intval(floor($tmp / 60));
        $minute = ($minute > 9) ? $minute : '0' . $minute;

        return $h . ':' . $minute;
    }

    //将格式(小时:分钟)转换成秒
    public static function transHourMinuteToSecond($hm)
    {
        $tmp = explode(':', $hm);

        return (is_array($tmp) && count($tmp) == 2) ? 3600 * intval($tmp[0]) + 60 * intval($tmp[1]) : 0;
    }

    //获取两个microtime之间的时间差(单位:毫秒)
    public static function getMicrotimeDiff($start, $end)
    {
        $s_tmp                = explode(' ', $start);
        $e_tmp                = explode(' ', $end);
        $start_seconds_format = $s_tmp[1] + round($s_tmp[0], 3);
        $end_seconds_format   = $e_tmp[1] + round($e_tmp[0], 3);

        //echo($end_seconds_format.'-'.$start_seconds_format);die();
        return round(($end_seconds_format - $start_seconds_format) * 1000);
    }

    /**
     * 获取$_GET值
     *
     * @param string  $param_name 参数名
     * @param integer $is_filter  是否安全过滤
     *
     * @return string
     */
    public static function _G($param_name, $is_filter = 0x01)
    {
        $param_value = isset($_GET[$param_name]) ? $_GET[$param_name] : '';

        return $is_filter ? self::fliter_html(self::safe_replace($param_value)) : $param_value;
    }

    /**
     * 获取$_POST值
     *
     * @param string  $param_name 参数名
     * @param integer $is_filter  是否安全过滤
     *
     * @return string
     */
    public static function _P($param_name, $is_filter = 0x01)
    {
        $param_value = isset($_POST[$param_name]) ? $_POST[$param_name] : '';

        return $is_filter ? self::fliter_html(self::safe_replace($param_value)) : $param_value;
    }

    /**
     * 获取$_COOKIE值
     *
     * @param string  $param_name 参数名
     * @param integer $is_filter  是否安全过滤
     *
     * @return string
     */
    public static function _C($param_name, $is_filter = 0x01)
    {
        $param_value = isset($_COOKIE[$param_name]) ? $_COOKIE[$param_name] : '';

        return $is_filter ? self::fliter_html(self::safe_replace($param_value)) : $param_value;
    }

    /**
     * 过滤html代码
     *
     * @param string $value
     *
     * @return string
     */
    public static function fliter_html($value)
    {
        if (function_exists('htmlspecialchars'))
            return htmlspecialchars($value);

        return str_replace(array("&", '"', "'", "<", ">"), array("&", "\"", "'", "<", ">"), $value);
    }

    /**
     * 安全过滤函数
     *
     * @param $string
     *
     * @return string
     */
    public static function safe_replace($string, $is_replace = 0x00)
    {
        if (!get_magic_quotes_gpc())
            return addslashes($string);
        if ($is_replace == 0x01) {
            $string = str_replace('%20', '', $string);
            $string = str_replace('%27', '', $string);
            $string = str_replace('%2527', '', $string);
            $string = str_replace('*', '', $string);
            $string = str_replace('"', '&quot;', $string);
            $string = str_replace("'", '', $string);
            $string = str_replace('"', '', $string);
            $string = str_replace(';', '', $string);
            $string = str_replace('<', '&lt;', $string);
            $string = str_replace('>', '&gt;', $string);
            $string = str_replace("{", '', $string);
            $string = str_replace('}', '', $string);
            $string = str_replace('\\', '', $string);
        }

        return $string;
    }

    /**
     * 加载摸板
     *
     * @param string $view_name 模板名
     * @param array  $data      数据
     *
     * @return mixed
     */
    public static function view($view_name, $data = array())
    {
        $view = Loader::loadView($view_name);
        if (is_array($data) && count($data) >= 1) {
            foreach ($data as $key => $val)
                $view->loadParams($key, $val);
        }
        $view->disPlay();
    }

    /**
     * 提示语
     *
     * @param string  $messages 提示语
     * @param integer $type     1:warn 2:busy 3:warn flightno
     *
     * @return mixed
     */
    public static function messages($messages = '', $type = 1)
    {
        $old_message = $messages;
        ($type == 1 && $ico_box = 'ico-box ico-warn') && $messages = '该航线无可查询航班，请您尝试其它航线。';
        ($type == 3 && $ico_box = 'ico-box ico-warn') && $messages = '抱歉，没有找到结果。';
        ($type == 2 && $ico_box = 'ico-box ico-busy') &&
        $messages = '您输入出发城市与到达城市相同，请至少修改其中之一。';
        $messages = $old_message ? $old_message : $messages;

        return '<div class="failure"><p><i class="' . $ico_box . '"></i>' . $messages .
        '</p><i class="split"></i></div>';
    }

    /**
     * 获取配置文件
     *
     * @param mixed  $param_name 变量名
     * @param string $file_name  文件名
     *
     * @cache_time 缓存时间
     */
    public static function get_cache_config($param_name, $file_name, $cache_time = 86400)
    {
        $cache_key  = md5($file_name . 'array');
        $array_list = array();
        if (($array_list = unserialize(RedisCache::getCacheInfo($cache_key))) == null) {
            include WEBROOT.'/config/app-static-config/' . $file_name . '.php';
            $array_list = $$param_name;
            RedisCache::setCacheInfo($cache_key, serialize($array_list), $cache_time);
        }

        return $array_list;
    }

    /**
     * 判断是否是ip地址
     *
     * @param str $ip 变量名
     */
    public static function is_ip($ip)
    {
        return preg_match('/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1 -9]?\d))))$/', $ip);
    }

    /**
     * 对二维数组按其中的某个键值排序
     *
     * @param str $arr  需要排序的数组
     * @param str $keys 用于排序的键名
     */
    public static function array_sort($arr, $keys, $type = 'asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }

        return $new_array;
    }

    /**
     * 获取毫秒时间戳
     */
    public static function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    /**
     * 获取js版片号
     */
    public static function jsver()
    {
        $ver_file = WEBROOT. '/.ver';
        if (file_exists($ver_file) && is_readable($ver_file))
            return trim(file_get_contents($ver_file));
        else
            return '2014040418463202';
    }

    /**
     * 将秒住哪花城h:m:s的格式
     *
     * @param $seconds
     *
     * @return string
     */
    public static function formatSecondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $mins  = floor(($seconds - ($hours * 3600)) / 60);
        $secs  = floor($seconds % 60);

        return "{$hours}:{$mins}:{$secs}";
    }

    /**
     * 去除pgsql timestamp类型秒后面的小数点
     *
     * @param $time
     *
     * @return string
     */
    public static function trimPgTimestamp($time)
    {
        if (!empty($time)) {
            $index = strpos($time, ".");
            if ($index > 0) {
                return substr($time, 0, strpos($time, "."));
            }
        }

        return $time;
    }

    /**
     * 签名
     *
     * @param array  param 加密参数
     * @param string salt 盐值
     *
     * @return string 加密串
     */
    public static function sign(array $param, $salt = 'account_customer')
    {
        ksort($param);
        $sign = md5(http_build_query($param) . $salt);

        return $sign;
    }

    public static function getPregxStr($pregx_name)
    {
        $pregx_list = array(
            'timestamp' => "/^20[\d]{2}-([0][1-9]|[1][0-2])-([0][1-9]|[1-2][0-9]|[3][0-1])\s([0-1][0-9]|[2][0-3]):[0-5][0-9](:[0-5][0-9])?$/",
            'email'     => "/^(\w)+([\.\-]\w+)*@(\w)+((\.\w{2,3}){1,3})$/",
            'int'       => "/^[0-9]+$/",
            'mobile'    => "/^1[\d]{10}$/",
            'datetime'=>"/^20[\d]{2}-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[0    1])\s(0?[0-9]|1[0-9]|2[0-3]):(0?[0-9]|[1-5][0-9])(:[0-5][0-9])?$/"
        );

        return isset($pregx_list[$pregx_name]) ? $pregx_list[$pregx_name] : null;
    }

    public static function isEmail($email)
    {
        $pregx = self::getPregxStr('email');

        return preg_match($pregx, $email);
    }

    public static function isInt($d)
    {
        $pregx = self::getPregxStr('int');

        return preg_match($pregx, $d);
    }

    public static function isMobile($d)
    {
        $pregx = self::getPregxStr('mobile');

        return preg_match($pregx, $d);
    }
    
    public static function isIdentityCard($card)
    {
        if(!preg_match("/^[\d]{17}([\d]|X)$/",$card)){
            return false;
        }
        
        $map=array('1','0','X','9','8','7','6','5','4','3','2');
        $sum = 0;
        for($i = 17; $i > 0; $i--){
            $s = pow(2,$i) % 11;
            $sum += $s * $card[17-$i];
        }
        return ($map[$sum % 11] == substr($card,-1,1));
    }
    
    public static function convertIP($ip)
    {
        //IP数据文件路径
        $dat_path = WEBROOT . DIRECTORY_SEPARATOR . "static" . DIRECTORY_SEPARATOR . 'qqwry.dat';

        //检查IP地址
        if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ip)) {
            return 'IP Address Error';
        }
        //打开IP数据文件
        if (!$fd = @fopen($dat_path, 'rb')) {
            return 'IP date file not exists or access denied';
        }

        //分解IP进行运算，得出整形数
        $ip = explode('.', $ip);
        $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

        //获取IP数据索引开始和结束位置
        $DataBegin = fread($fd, 4);
        $DataEnd = fread($fd, 4);
        $ipbegin = implode('', unpack('L', $DataBegin));
        if ($ipbegin < 0) $ipbegin += pow(2, 32);
        $ipend = implode('', unpack('L', $DataEnd));
        if ($ipend < 0) $ipend += pow(2, 32);
        $ipAllNum = ($ipend - $ipbegin) / 7 + 1;

        $BeginNum = 0;
        $EndNum = $ipAllNum;

        //使用二分查找法从索引记录中搜索匹配的IP记录
        $ip1num = 0;
        $ip2num = 0;
        $ipAddr2 = "";
        $ipAddr1 = "";
        while ($ip1num > $ipNum || $ip2num < $ipNum) {
            $Middle = intval(($EndNum + $BeginNum) / 2);

            //偏移指针到索引位置读取4个字节
            fseek($fd, $ipbegin + 7 * $Middle);
            $ipData1 = fread($fd, 4);
            if (strlen($ipData1) < 4) {
                fclose($fd);
                return 'System Error';
            }
            //提取出来的数据转换成长整形，如果数据是负数则加上2的32次幂
            $ip1num = implode('', unpack('L', $ipData1));
            if ($ip1num < 0) $ip1num += pow(2, 32);

            //提取的长整型数大于我们IP地址则修改结束位置进行下一次循环
            if ($ip1num > $ipNum) {
                $EndNum = $Middle;
                continue;
            }

            //取完上一个索引后取下一个索引
            $DataSeek = fread($fd, 3);
            if (strlen($DataSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $DataSeek = implode('', unpack('L', $DataSeek . chr(0)));
            fseek($fd, $DataSeek);
            $ipData2 = fread($fd, 4);
            if (strlen($ipData2) < 4) {
                fclose($fd);
                return 'System Error';
            }
            $ip2num = implode('', unpack('L', $ipData2));
            if ($ip2num < 0) $ip2num += pow(2, 32);

            //没找到提示未知
            if ($ip2num < $ipNum) {
                if ($Middle == $BeginNum) {
                    fclose($fd);
                    return 'Unknown';
                }
                $BeginNum = $Middle;
            }
        }

        //下面的代码读晕了，没读明白，有兴趣的慢慢读
        $ipFlag = fread($fd, 1);
        if ($ipFlag == chr(1)) {
            $ipSeek = fread($fd, 3);
            if (strlen($ipSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $ipSeek = implode('', unpack('L', $ipSeek . chr(0)));
            fseek($fd, $ipSeek);
            $ipFlag = fread($fd, 1);
        }

        if ($ipFlag == chr(2)) {
            $AddrSeek = fread($fd, 3);
            if (strlen($AddrSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $ipFlag = fread($fd, 1);
            if ($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if (strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }

            while (($char = fread($fd, 1)) != chr(0))
                $ipAddr2 .= $char;

            $AddrSeek = implode('', unpack('L', $AddrSeek . chr(0)));
            fseek($fd, $AddrSeek);

            while (($char = fread($fd, 1)) != chr(0))
                $ipAddr1 .= $char;
        } else {
            fseek($fd, -1, SEEK_CUR);
            while (($char = fread($fd, 1)) != chr(0))
                $ipAddr1 .= $char;

            $ipFlag = fread($fd, 1);
            if ($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if (strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }
            while (($char = fread($fd, 1)) != chr(0)) {
                $ipAddr2 .= $char;
            }
        }
        fclose($fd);

        //最后做相应的替换操作后返回结果
        if (preg_match('/http/i', $ipAddr2)) {
            $ipAddr2 = '';
        }
        $ipaddr = "$ipAddr1 $ipAddr2";
        $ipaddr = preg_replace('/CZ88.Net/is', '', $ipaddr);
        $ipaddr = preg_replace('/^s*/is', '', $ipaddr);
        $ipaddr = preg_replace('/s*$/is', '', $ipaddr);
        if (preg_match('/http/i', $ipaddr) || $ipaddr == '') {
            $ipaddr = 'Unknown';
        }


//        return $ipAddr1;
        return array(
            "area" => $ipAddr1,
            "carrier" => $ipAddr2
        );
    }


    public static function getDatesArray($startDate, $endDate)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $array = array();
        if ($startTime >= $endTime) {
            $array[] = $startDate;
        } else {
            while ($startTime <= $endTime) {
                $array[] = date("Y-m-d", $startTime);
                $startTime += 60 * 60 * 24;
            }
        }

        return $array;
    }
}

?>
