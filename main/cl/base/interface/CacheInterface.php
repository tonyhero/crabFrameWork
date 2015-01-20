<?php
interface CacheInterface
{
    public static function setCacheInfo($key,$val,$cache_timeout_seconds);
    public static function getCacheInfo($key);
}
?>