<?php

namespace OxygenzSAS\Framework;

use Cron\CronExpression;
use OxygenzSAS\Container\Container;

abstract class Automate {
    protected CronExpression $cronExpression;
    protected string $sharedLogFileName;

    /**
     * @return mixed
     */
    public function getSharedLogFileName()
    {
        return ScriptSupervisor::getLockPath().DIRECTORY_SEPARATOR.'last_executed_automate.json';
    }

    public function __construct() {
        $this->cronExpression = CronExpression::factory($this->getCronConfig());
    }

    public function start() {

        $currentDateTime = new \DateTime();

        // Lire les dates de la dernière exécution depuis le fichier partagé
        $lastExecutions = $this->readLastExecutions();

        // Vérifier si la dernière exécution était dans l'intervalle Cron
        if (!empty($lastExecutions) && $this->cronExpression->getNextRunDate($lastExecutions) > $currentDateTime) {
            echo $this->getTagIdentifier(). ' cron not satisfied = '.$this->getCronConfig();
            return false;
        }

        $lockFile = ScriptSupervisor::getLockFile($this->getTagIdentifier());
        $lockContent = @file_get_contents($lockFile);

        // Si le fichier de verrouillage est vide, cela signifie que le worker s'est arrêté.
        if (!empty($lockContent)) {
            echo $this->getTagIdentifier(). ' already running with PId = '.$lockContent;
            return false;
        }

        // Créez un fichier de verrouillage pour indiquer que le worker est en cours d'exécution.
        file_put_contents($lockFile, getmypid());

        // Exécutez votre algorithme ici
        $this->exec();

        // Ajoutez la nouvelle date d'exécution au tableau
        $lastExecutions = $currentDateTime->format('Y-m-d H:i:s');

        // Mettre à jour le fichier partagé avec les nouvelles dates
        $this->updateLastExecutions($lastExecutions);

    }

    // Méthodes statiques pour lire et mettre à jour les dates de la dernière exécution
    private function readLastExecutions() {
        if (file_exists($this->getSharedLogFileName())) {
            $content = file_get_contents($this->getSharedLogFileName());
            $arr = json_decode($content, true) ?: [];
            return $arr[$this->getTagIdentifier()] ?? null;
        } else {
            return null;
        }
    }

    private function updateLastExecutions($lastExecutions) {

        if (file_exists($this->getSharedLogFileName())) {
            $content_brut = file_get_contents($this->getSharedLogFileName());
            $arr = json_decode($content_brut, true) ?: [];
        } else {
            $arr = [];
        }
        $arr[$this->getTagIdentifier()] = $lastExecutions;

        $content = json_encode($arr);
        file_put_contents($this->getSharedLogFileName(), $content);
    }

    abstract public function getTagIdentifier() :string ;
    abstract public function getCronConfig() :string ;
    abstract public function exec() ;
}