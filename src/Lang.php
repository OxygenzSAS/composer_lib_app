<?php

namespace OxygenzSAS\Framework;

use OxygenzSAS\Container\Container;

class Lang {

    public static function get($key){
        /** @var \OxygenzSAS\Interfaces\LangInterface $lang */
        $lang = Container::getInstance()->get('Lang');
        return $lang->get($key);
    }

    public static function has($key): bool
    {
        /** @var \OxygenzSAS\Interfaces\LangInterface $lang */
        $lang = Container::getInstance()->get('Lang');
        return $lang->has($key);
    }

    public static function set($key, $lang_str, $traduction){
        /** @var \OxygenzSAS\Interfaces\LangInterface $lang */
        $lang = Container::getInstance()->get('Lang');
        return $lang->set($key, $lang_str, $traduction);
    }

    public static function addLang($lang_str){
        /** @var \OxygenzSAS\Interfaces\LangInterface $lang */
        $lang = Container::getInstance()->get('Lang');
        return $lang->addLang($lang_str);
    }

    /**
     * @return string
     */
    public static function getLang(): string
    {
        /** @var \OxygenzSAS\Interfaces\LangInterface $lang */
        $lang = Container::getInstance()->get('Lang');
        return $lang->getLang();
    }

    /**
     * @param string $lang
     */
    public static function setLang(string $lang_str): void
    {
        /** @var \OxygenzSAS\Interfaces\LangInterface $lang */
        $lang = Container::getInstance()->get('Lang');
        $lang->setLang($lang_str);
    }

}
