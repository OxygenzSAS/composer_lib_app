<?php

use OxygenzSAS\Framework\WorkerConfig;

$root_path = dirname(__DIR__, 4) ;

// utilisation du loader de composer
require $root_path.'/vendor/autoload.php';

new class($root_path) extends \OxygenzSAS\Framework\Console {

    public function run($params){

        /** recuperation de la config pour les migrations */
        $supervisor_config = OxygenzSAS\Config\Config::getInstance()->get('Supervisor');

        if(
            !isset( $supervisor_config['php_path'])
            || empty( ($supervisor_config['php_path'] ?? ''))
        ){
            throw new Exception('No config found');
        }

        $php_path = $supervisor_config['php_path'];
        $workerName = $params['worker'] ?? '';

        $configSupervisor = \OxygenzSAS\Config\Config::getInstance()->get('Supervisor');

        if(empty($configSupervisor)) {
            echo 'il manque la config Supervisor dans le fichier de config'.PHP_EOL;
            die();
        }

        if(empty($configSupervisor['php_path'])) {
            echo 'il manque la config Supervisor[\'php_path\'] dans le fichier de config'.PHP_EOL;
            die();
        }

        if(empty($configSupervisor['lock_path'])) {
            echo 'il manque la config Supervisor[\'lock_path\'] dans le fichier de config'.PHP_EOL;
            die();
        }

        \OxygenzSAS\Framework\ScriptSupervisor::setPhpPath($configSupervisor['php_path']);
        \OxygenzSAS\Framework\ScriptSupervisor::setLockPath($configSupervisor['lock_path']);

        switch($params['action'] ?? ''){

            case 'all' :
                /**  @todo recup la list des scripts php workers   */
                $all_worker = WorkerConfig::getInstance()->getSettings();
                \OxygenzSAS\Framework\ScriptSupervisor::superviseworkers($all_worker);
                break;

            case 'start' :
                $workerClass = WorkerConfig::getInstance()->get($workerName);
                $worker = new $workerClass();
                $worker->start();
                break;

            case 'kill' :
                $workerClass = WorkerConfig::getInstance()->get($workerName);
                $supervisor = new \OxygenzSAS\Framework\ScriptSupervisor($workerClass);
                $supervisor->killWorker();
                break;

            default :
                echo 'parametre action manquant'.PHP_EOL;
                $this->show_help();
                break;
        }

    }

    public function show_help(){
        echo 'Exemple :'.PHP_EOL;
        echo 'php '.basename($_SERVER['SCRIPT_NAME']).' action=all'.PHP_EOL;
        echo 'php '.basename($_SERVER['SCRIPT_NAME']).' action=start worker=worker_1'.PHP_EOL;
        echo 'php '.basename($_SERVER['SCRIPT_NAME']).' action=kill worker=worker_1'.PHP_EOL;
        echo 'Ce script permet de lancer/relancer les workers si necessaire'.PHP_EOL;
        die();
    }

};
