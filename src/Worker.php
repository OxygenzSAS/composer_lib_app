<?php

namespace OxygenzSAS\Framework;

use OxygenzSAS\Config\Config;

abstract class Worker
{

    public JobQueue $job_queue;

    public function getJobQueue() :JobQueue
    {

        if(!empty($this->jobQueue)){
            return $this->jobQueue;
        }

        $config = Config::getInstance()->getSettings();

        $dsn = '';
        if ($config['db_host'] != '') {
            $dsn .= 'mysql:host=' . $config['db_host'] . ';';
        }
        if ( $config['db_port'] != '') {
            $dsn .= 'port=' . $config['db_port'] . ';';
        }
        if ($config['db_name'] != '') {
            $dsn .= 'dbname=' . $config['db_name'] . ';';
        }
        if ( $config['db_charset'] != '') {
            $dsn .= 'options=\'--client_encoding=' .  $config['db_charset']  . '\'' . ';';
        }

        $PDO = new \PDO($dsn, $config['db_user'], $config['db_pass']);
        $this->job_queue = new JobQueue('mysql');
        $this->job_queue->addQueueConnection($PDO);
        return $this->job_queue;
    }

    public function start() {

        $lockFile = ScriptSupervisor::getLockFile($this->getTagIdentifier());
        $lockContent = @file_get_contents($lockFile);

        // Si le fichier de verrouillage est vide, cela signifie que le worker s'est arrêté.
        if (!empty($lockContent)) {
            echo $this->getTagIdentifier(). ' already running with PId = '.$lockContent;
            return false;
        }

        // Créez un fichier de verrouillage pour indiquer que le worker est en cours d'exécution.
        file_put_contents($lockFile, getmypid());

        $jobQueue = $this->getJobQueue();
        $this->job_queue->watchPipeline($this->getTagIdentifier());
        while(true) {
            $job = $jobQueue->getNextJobAndReserve();

            if(empty($job)) {
                sleep(5);
                continue;
            }

            $payload = json_decode($job['payload'], true);

            try {

                $result = $this->exec($payload);

                if($result === true) {
                    $jobQueue->deleteJob($job);
                } else {
                    // this takes it out of the ready queue and puts it in another queue that can be picked up and "kicked" later.
                    $jobQueue->buryJob($job);
                }
            } catch(Exception $e) {
                $jobQueue->buryJob($job);
            }
        }
    }

    public function add($payload) {
        $jobQueue = $this->getJobQueue();
        $Job_Queue->selectPipeline($this->getTagIdentifier());
        $Job_Queue->addJob(json_encode($payload));
    }

    abstract public function getTagIdentifier() :string ;

    abstract public function exec($payload) :bool ;
}