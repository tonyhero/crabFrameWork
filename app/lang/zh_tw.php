<?php
/**
 * Created by PhpStorm.
 * User: atom
 * Date: 9/4/14
 * Time: 11:59 AM
 */

Translator::addLanguage("zh_tw", array(
    SystemError::DB_CONNECT_FAIL=>"數據庫連接失敗",
    SystemError::DB_SELECTDB_FAIL=>"SELECTDB失敗",
    SystemError::DB_SQL_FAIL=>"sql語句執行失敗",
    SystemError::REDIS_CONNECT_FAIL=>"redis連接失敗",
    SystemError::REDIS_KEY_NOT_EXIST=>"redis鍵值不存在",
    
    SystemError::SYSTEM_ERROR=>"網絡繁忙,請稍後再試",
    SystemError::PARAMS_WORONG=>"參數錯誤",
    SystemError::CURL_FAIL=>"CURL 失敗",
));
