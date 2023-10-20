<?php

namespace OxygenzSAS\Framework;

class ScriptSupervisor {

    private Worker $worker;
    private static $phpPath = '';
    private static $lockPath = '';

    public function __construct($worker){
        $this->worker = new $worker();
    }

    public static function setPhpPath($path){
        self::$phpPath = $path;
    }
    public static function setLockPath($path){
        self::$lockPath = $path;
    }

    public static function superviseWorkers($workers) {
        foreach ($workers as $worker) {
            $supervisor = new self($worker);
            if (!$supervisor->isworkerRunning()) {
                $supervisor->startworker();
            }
        }
    }

    private function isworkerRunning() {
        // Obtenez le contenu du fichier de verrouillage du worker.
        $lockFile = self::getLockFile($this->worker->getTagIdentifier());
        $pidLock = @file_get_contents($lockFile);

        // Si le fichier de verrouillage est vide, cela signifie que le worker s'est arrêté.
        if (empty($pidLock)) {
            return false;
        }

        // Si vous êtes sur Windows, utilisez la commande "tasklist" pour vérifier les processus.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Recherchez le nom du worker dans la sortie de la commande "tasklist".
            $cmd = "tasklist /NH /FO CSV /FI \"IMAGENAME eq php.exe\"";
            exec($cmd, $output);

            foreach ($output as $line) {
                if (stripos($line, $pidLock) !== false) {
                    echo $this->worker->getTagIdentifier().' already running with PID = '.$pidLock;
                    return true;
                }
            }

            // Si le worker n'est trouvé dans la liste des processus, le fichier de verrouillage peut être nettoyé.
            unlink($lockFile);
            return false;
        }
        // Si vous êtes sur Linux, utilisez la commande "ps" pour vérifier les processus.
        else {
            // Exécutez la commande "ps" pour obtenir la liste des processus.
            exec("ps aux | grep -e php", $output);

            // Recherchez le nom du worker dans la sortie de "ps".
            foreach ($output as $line) {
                if (stripos($line, $pidLock) !== false) {
                    echo $this->worker->getTagIdentifier().' already running with PID = '.$pidLock;
                    return true;
                }
            }

            // Si le worker n'est trouvé dans la liste des processus, le fichier de verrouillage peut être nettoyé.
            unlink($lockFile);
            return false;
        }
    }

    public static function getLockFile($identifier) {
        // Génère un nom de fichier de verrouillage basé sur le nom du worker.

        if(!file_exists(self::$lockPath)){
            var_dump(self::$lockPath);
            mkdir(self::$lockPath, 0777, true);
        }

        return self::$lockPath.DIRECTORY_SEPARATOR.$identifier. '.lock';
    }

    private function startworker() {
        $lockFile = self::getLockFile($this->worker->getTagIdentifier());
        @unlink($lockFile);

        echo 'start '.$this->worker->getTagIdentifier().' ...';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = self::$phpPath.' '.realpath($_SERVER['SCRIPT_NAME']). " action=start worker=".$this->worker->getTagIdentifier();
            pclose(popen('start /B cmd /C "'.$command.' >NUL 2>NUL"', 'r'));
        } else {
            $command = self::$phpPath.' '.realpath($_SERVER['SCRIPT_NAME']). " action=start worker=".$this->worker->getTagIdentifier();
            $command = 'nohup ' . $command . ' >> /dev/null 2>&1 & echo $!';
            exec($command);
        }
    }

    public function killWorker() {
        $lockFile = self::getLockFile($this->worker->getTagIdentifier());
        $pidLock = @file_get_contents($lockFile);

        // Si le fichier de verrouillage est vide, cela signifie que le worker s'est arrêté.
        if (empty($pidLock)) {
            return false;
        }

        echo 'kill '.$this->worker->getTagIdentifier().' ...';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = "taskkill.exe /F /PID ".$pidLock;
            pclose(popen('start /B cmd /C "'.$command.' >NUL 2>NUL"', 'r'));
        } else {
            $command = "kill -9 ".$pidLock;
            $command = 'nohup ' . $command . ' >> /dev/null 2>&1 & echo $!';
            exec($command);
        }

    }

}
