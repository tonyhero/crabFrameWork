<?php
/**
 * Created by PhpStorm.
 * User: atom
 * Date: 9/4/14
 * Time: 11:59 AM
 */

Translator::addLanguage("zh_cn", array(
    SystemError::DB_CONNECT_FAIL=>"数据库连接错误",
    SystemError::DB_SELECTDB_FAIL=>"SELECTDB失败",
    SystemError::DB_SQL_FAIL=>"sql语句执行失败",
    SystemError::REDIS_CONNECT_FAIL=>"redis连接失败",
    SystemError::REDIS_KEY_NOT_EXIST=>"redis键值不存在",
    
    SystemError::SYSTEM_ERROR=>"系统错误",
    SystemError::PARAMS_WORONG=>"参数错误",
    SystemError::CURL_FAIL=>"CURL 失败",
    SystemError::USER_NOT_LOGIN=>"用户未登陆",
    
    SystemError::PUSHINFO_GAMELIST_NOT_EXIST=>"未找到推送的目标游戏信息",
    SystemError::PUSHINFO_GAMECONFIG_NOT_EXIST=>"未找到游戏配置",
    
    
    SystemError::OVER_CONFIG_MAX_FILESIZE=>"上传的文件超过配置上限",
    SystemError::MAX_HTML_FILE_SIZE=>"超过html页文件上线",
    SystemError::ONLY_PARTIALLY_UPLOADED=>"只上传成功部分内容",
    SystemError::NO_FILE_UPLOADED=>"无上传内容",
    SystemError::NO_FIND_TMP=>"上传的文件的tmp内容未找到",
    SystemError::IO_FAIL=>"上传文件磁盘写入失败",
    SystemError::FILE_UNKNOW_ERROR=>"未知错误",
    SystemError::FILE_TYPE_ILLEGAL=>"不允许上传此文件类型",
    SystemError::OVER_USER_MAX_FILESIZE=>"超过用户所能上传的文件大小上限",
    SystemError::FILE_MOVE_ERROR=>"移动上传文件失败",
));
