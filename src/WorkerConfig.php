<?php

namespace OxygenzSAS\Framework;

use OxygenzSAS\Config\Config;

class WorkerConfig extends Config
{

    private $settings = [];

    private static $_instance = null;

    /**
     * Retourne une instance singleton du fichier de config
     * @param null $path_config_file Chemin vers le fichier de config
     * @return null|Config
     */
    public static function getInstance($path_config_file = null){

        if( ! (self::$_instance instanceof self ) )
            self::$_instance = new self($path_config_file);

        return self::$_instance;

    }

}