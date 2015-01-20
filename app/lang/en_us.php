<?php
/**
 * Created by PhpStorm.
 * User: atom
 * Date: 9/4/14
 * Time: 11:59 AM
 */

Translator::addLanguage("en_us", array(
    SystemError::DB_CONNECT_FAIL=>"db connect fail",
    SystemError::DB_SELECTDB_FAIL=>"db select fail",
    SystemError::DB_SQL_FAIL=>"sql query",
    SystemError::REDIS_CONNECT_FAIL=>"redis connect fail",
    SystemError::REDIS_KEY_NOT_EXIST=>"redis key not exist",
    
    /********* app error ************/
    SystemError::SYSTEM_ERROR=>"system error",
    SystemError::PARAMS_WORONG=>"wrong params",
    SystemError::CURL_FAIL=>"curl fail",
    SystemError::USER_NOT_LOGIN=>"user not login",
    
    SystemError::PUSHINFO_GAMELIST_NOT_EXIST=>"PushInfo gamelist not find",
    SystemError::PUSHINFO_GAMECONFIG_NOT_EXIST=>"PushInfo gameconfig not find",
    
    /*********** upload error **************/
    SystemError::OVER_CONFIG_MAX_FILESIZE=>"over max size of upload file",
    SystemError::MAX_HTML_FILE_SIZE=>"over html file size",
    SystemError::ONLY_PARTIALLY_UPLOADED=>"only uploaded partially",
    SystemError::NO_FILE_UPLOADED=>"no file upload",
    SystemError::NO_FIND_TMP=>"upload file's tmp not find",
    SystemError::IO_FAIL=>"disk write fail",
    SystemError::FILE_UNKNOW_ERROR=>"upload unknow error",
    SystemError::FILE_TYPE_ILLEGAL=>"file type not allowed",
    SystemError::OVER_USER_MAX_FILESIZE=>"over user max upload file size",
    SystemError::FILE_MOVE_ERROR=>"move upload file error",
));
