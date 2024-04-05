<?php

namespace OxygenzSAS\Framework;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use OxygenzSAS\Config\Config;
use OxygenzSAS\Container\Container;

abstract class App {

    protected static $container;
    protected static $header = [];

    /**
     * @return array
     */
    public static function getHeaders() :array
    {
        return self::$header;
    }

    /**
     * @return array
     */
    public static function getHeader($key) :string
    {
        return self::$header[$key] ?? '';
    }

    /**
     * @param string $header
     */
    public static function setHeader(string $key, string $value) :void
    {
        self::$header[$key] = $value;
    }

    private static function showErrorInit($message, \Throwable $e = null) {
        echo '<h1>Erreur 500 : Une erreur interne est survenue</h1>';
        echo $message;
        echo '<br/>';
        echo '<pre>'.print_r($e, true).'</pre>';
        die();
    }

    /**
     * @throws \Exception
     */
    public function __construct($path)
    {
        try{
            session_start();

            self::initDependance($path);

            // creation du dispatcher
            list($dispatcher, $request) = require_once($path.'/config/middleware.php');

            $this->before_router($request);

            $response = $dispatcher->dispatch($request);

            $this->after_router($request, $response);

            // si la reponse est presente ont l'affiche
            if($response instanceof ResponseInterface)
                $this->showResponse($response);

        }catch(\Exception $e){
            if(Container::getInstance()->has('Log')){
                /** @var \Psr\Log\LoggerInterface $log */
                $log = Container::getInstance()->get('Log');
                $log->log(LogLevel::ERROR,'', ['exception' => $e]);
            }
            throw $e;
        }

    }

    public static function initDependance($path){

        // on convertit les erreurs php en exception
        self::AllErrorToException();
                
        // initialise le singleton de configuration
        try {
            Config::getInstance($path.'/config/config.php');
        }catch (\Throwable $e) {
            self::showErrorInit(message: 'Impossible de charger le fichier de configuration config/config.php.', e: $e);
        }

        // parser les plugins
        try {
            Plugin::getInstance($path.'/config/plugin.php');
        }catch (\Throwable $e) {
           self::showErrorInit(message: 'Impossible de charger le fichier de configuration config/plugin.php.', e: $e);
        }

        // initialise le container
        try {
           Container::getInstance($path.'/config/container.php');
        }catch (\Throwable $e) {
            self::showErrorInit(message: 'Impossible de charger le fichier de configuration config/container.php.', e: $e);
        }

        // initialise la config des workers
        if(file_exists($path.'/config/worker.php')){
            try {
                WorkerConfig::getInstance($path.'/config/worker.php');
            }catch (\Throwable $e) {
                self::showErrorInit(message: 'Impossible de charger le fichier de configuration config/worker.php.', e: $e);
            }
        }
    }

    /**
     * Affiche une reponse a l'ecran
     * @param ResponseInterface $response
     */
    public function showResponse(ResponseInterface $response){
        $http_line = sprintf('HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        header($http_line, true, $response->getStatusCode());

        // surcharge des header via l'app
        foreach (self::getHeaders() as $key => $header){
            $response = $response->withHeader($key, $header);
        }

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            echo $stream->read(1024 * 8);
        }
    }

    /**
     * Cette fonction Convertit les erreurs PHP en Exception pour
     * fonctionner avec whoops
     */
    public static function AllErrorToException(){
        set_error_handler(function($severity, $message, $file, $line){
            if (!(error_reporting() & $severity)) {
                // This error code is not included in error_reporting
                return;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    /**
     * Cette methode sera executer avant le router et le dispatcher
     * @param ServerRequestInterface $request
     * @return mixed
     */
    public abstract function before_router(ServerRequestInterface $request);

    /**
     * Cette methode sera executer apres le router mais avant l'affichage
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed
     */
    public abstract function after_router(ServerRequestInterface $request,ResponseInterface $response);

}
