# App
Classe de demarrage de mon framework


## Migration

CONFIG
```php 
    // Migration
    "Migration"   => [
        /** migration de l'app principal */
        'App' => ['path' => realpath($root_dir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'migration'.DIRECTORY_SEPARATOR), 'name' => 'App']
        ,'Trad' => ['path' => realpath($root_dir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'trad'.DIRECTORY_SEPARATOR), 'name' => 'Trad']
    ],
```

migration INIT ( install la bdd migration )
```bash 
composer migration  source=App action=init 
```
migration UP (all)
```bash 
composer migration  source=App action=upgrade 
```
migration DOWN (one at a time)
```bash 
composer migration  source=App action=downgrade 
```


## Worker

CONFIG
```php 
    // Supervisor
    "Supervisor"   => [
        /** chemin vers l'executable php */
        'php_path' => 'C:\Users\...\PHP\php-8.1.3-nts-Win32-vs16-x64\php.exe'
        ,'lock_path' => realpath($root_dir).DIRECTORY_SEPARATOR.'lock'.DIRECTORY_SEPARATOR
    ],
```

CONFIG-WORKER
a file named worker.php inside folder config.
List all actif worker
```php 
<?php
/**
 * retourne la liste des workers actifs
 */

return [
    (new \App\worker\Worker1())->getTagIdentifier() => \App\worker\Worker1::class
];
```

supervisor all ( start all worker )
```bash 
composer supervisor action=all 
```
start only one worker named worker_1
```bash 
composer supervisor action=start worker=worker_1
```
kill one worker named worker_1
```bash 
composer supervisor action=kill worker=worker_1
```
