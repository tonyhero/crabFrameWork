<?php

/**
 * Created by PhpStorm.
 * User: atom
 * Date: 9/4/14
 * Time: 3:05 PM
 */
class Translator
{
    private static $_default = null;
    private $_defaultLanguageArray = null;
    private $_allowFallback = true;
    private $_languagePath = "";
    private static $_languages = array();

    private static $_instance = null;

    public static function getTranslator($config = null)
    {
        if (is_null(self::$_instance)) {
            //TODO 如果config为null，读取配置文件
            self::$_instance                = new Translator($config);
            self::$_instance->_languagePath = WEBROOT . DIRECTORY_SEPARATOR . "lang";
        }

        return self::$_instance;
    }

    private function __construct($config)
    {
        self::$_default = isset($_SERVER['HTTP_LANGUAGE'])? $this->mapLanguage($_SERVER['HTTP_LANGUAGE']) : 'zh_cn';
    }

    public static function setLanguage($lang)
    {
        self::$_default = self::mapLanguage($lang);
    }

    private static function mapLanguage($lang)
    {
        //TODO  暂时这么写，把不一样得语言代码转换成统一格式
        $mapping = array(
            "s-cn" => "zh_cn",
            "t-cn" => "zh_tw",
            "en"   => "en_us"
        );
        if (!isset($mapping[$lang])) {
            return $lang;
        } else {
            return $mapping[$lang];
        }

    }

    public function  getText($key, $language = null)
    {
        if (is_null($language)) {
            $language = self::$_default;
        }
        $this->loadLanguage($language);

        //        $arrayName     = "language_" . $language;
        $languageArray = isset(self::$_languages[$language])? self::$_languages[$language] : array();
        if (isset($languageArray[$key])) {
            return $languageArray[$key];
        } else if ($this->_allowFallback && isset($this->_defaultLanguageArray[$key])) {
            return $this->_defaultLanguageArray[$key];
        } else {
            //TODO 如果fallback后都没有正确的语言资源，是返回key还是抛出异常？
            return $key;
        }
    }

    private function loadLanguage($language)
    {
        if (!isset(self::$_languages[$language])) {
            $languageResource = $this->_languagePath . DIRECTORY_SEPARATOR . $language . ".php";
            if (file_exists($languageResource)) {
                require_once($languageResource);
            } else {
                //TODO throw EXCEPTION here
            }
        }
    }

    public static function addLanguage($lang, $langResource)
    {
        self::$_languages[$lang] = $langResource;
    }

}