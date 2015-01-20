<?php

class SystemError extends Exception
{

    /********10000-10099 数据库错误 *******************/
    const DB_CONNECT_FAIL                    = 10001; //数据库连接错误
    const DB_SELECTDB_FAIL                   = 10002; //SELECTDB失败
    const DB_SQL_FAIL                        = 10003; //sql语句执行失败
    const REDIS_CONNECT_FAIL                 = 10004;//redis连接失败
    const REDIS_KEY_NOT_EXIST                = 10005;//redis键值不存在

    /******** 10100-10200 应用错误 ********************/
    const SYSTEM_ERROR                       = 10100; //系统错误
    const PARAMS_WORONG                      = 10101;
    const CURL_FAIL                          = 10102;
    const USER_NOT_LOGIN                     = 10103;

    const PUSHINFO_GAMELIST_NOT_EXIST        = 10104;
    const PUSHINFO_GAMECONFIG_NOT_EXIST      = 10105;
    const MOBILE_MESSAGE_NETWORK_CURL_FAIL   = 10106;//手机短信接口curl失败
    const MAIL_NETWORK_CURL_FAIL             = 10107;//邮件curl失败

    /******** 10251-10300 上传错误 ********************/
    const OVER_CONFIG_MAX_FILESIZE = 10251;
    const MAX_HTML_FILE_SIZE       = 10252;
    const ONLY_PARTIALLY_UPLOADED  = 10253;
    const NO_FILE_UPLOADED         = 10254;
    const NO_FIND_TMP              = 10255;
    const IO_FAIL                  = 10256;
    const FILE_UNKNOWN_ERROR       = 10257;
    const FILE_TYPE_ILLEGAL        = 10258;
    const OVER_USER_MAX_FILESIZE   = 10259;
    const NOT_UPLOAD_FILE          = 10260;
    const FILE_MOVE_ERROR          = 10261;


    public static function getErrorMsg($errorCode, $params = array()){
        $translator = Translator::getTranslator();
        $msg = $translator->getText($errorCode);
        if (!is_array($params) || !empty($params)) {
            
        }

        return $msg;
    }
}

?>
