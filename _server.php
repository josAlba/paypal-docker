<?php

require __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Protocols\Http;

include(__DIR__ .'/include/conf.php');
include(__DIR__.'/paypal.php');

$http_worker = new Worker("http://".$configuracion->ip.":".$configuracion->puerto);
$http_worker->count = $configuracion->hilos;

//Recive las peticiones.
$http_worker->onMessage = function($connection, $data){

    /**
     * Almacena los parametros GET recividos en la peticion
     * @var array GET
     */
    $get = $data->get();

    if(count($get)==0){
        $connection->send(json_encode(array(
            'Servicio'  =>'Paypal',
            'Version'   =>"1.0.0",
            'Respuesta' =>'Servicio OK'
        )));
        return;
    }
    
    $connection->send(Mecha($get));

};

// run all workers
Worker::runAll();