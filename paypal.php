<?php

require __DIR__ . '/vendor/autoload.php';
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

/**
 * Cliente Paypal
 */
class PayPalClient{

    private  $dev=true;
    private  $CLIENT_ID      ='';
    private  $CLIENT_SECRET  ='';

    /**
     * Returns PayPal HTTP client instance with environment which has access
     * credentials context. This can be used invoke PayPal API's provided the
     * credentials have the access to do so.
     */
    public function client($user='',$dev=true){
        
        $buffero = getFile::get();

        $this->CLIENT_ID        =   $buffero->clientId; 
        $this->CLIENT_SECRET    =   $buffero->clientSecret;

        $this->dev  = $dev;

        return new PayPalHttpClient(self::environment());
    }
    
    /**
     * Setting up and Returns PayPal SDK environment with PayPal Access credentials.
     * For demo purpose, we are using SandboxEnvironment. In production this will be
     * ProductionEnvironment.
     */
    public function environment(){

        $clientId = getenv("CLIENT_ID") ?: $this->CLIENT_ID;
        $clientSecret = getenv("CLIENT_SECRET") ?: $this->CLIENT_SECRET;

        if($this->dev==true){
            return new SandboxEnvironment($clientId, $clientSecret);
        }else{
            return new SandboxEnvironment($clientId, $clientSecret);
        }
        
    }

}

class getFile{

    public function get($file=''){

        if($file==''){
            exit();
        }
        if(!file_exists($file)){
            exit();
        }

        $dir = __DIR__.'/user/'.$file;

        $gestor     = file_get_contents($file, true);
        $buffero    = json_decode($gestor);

    }

}


$data=array(
    "f"=>'',    //Fichero para leer
    "ref"=>'',  //Referencia
    "to"=>''    //Total
);

//Validar datos
if(validate($data)){

    if($data['q']=='new'){
        //Nuevo pedido
        return setPedido($data);

    }else if($data['q']=='find'){
        //Buscar pedido
        return getPedido($data);

    }

}else{
    return json_encode(array(
        "Validador"=>"Error en los campos"
    ));
}

/**
 * Validar parametros
 * 
 * @param array $d Datos a validar
 * @return boolean
 */
function validate($d){

    //Identificamos el tipo de peticion
    if(isset($d['q'])){

        if($d['q']=='new'){
            //Validamos los datos para un nuevo pedido.
            if(isset($d['f']) && isset($d['ref']) && isset($d['to'])){
                return true;
            }

        }else if($d['q']=='find'){
            //Validamos los datos para buscar un pedido.
            if(isset($d['f']) && isset($d['ref'])){
                return true;
            }

        }

    }
    

    return false;

}
/**
 * Crea un nuevo pedido para Paypal.
 * 
 * @param array $pedido Datos del pedido.
 * @return string JSON devuelto.
 */
function setPedido($pedido){

    //Creamos el cliente
    $client = PayPalClient::client($pedido['f']);
    //Cargamos la informacion del fichero de configuracion.
    $buffero = getFile::get($pedido['f']);
    //Crea una peticions.
    $request = new OrdersCreateRequest();

    //Prepara el contenido de la peticion.
    $request->prefer('return=representation');
    $request->body = [
        "intent" => "CAPTURE",
        "purchase_units" => [[
            "reference_id" => $pedido['ref'],
            "amount"=> [
                "currency_code"=> "EUR",
                "value"=> floatval($pedido['to'])
            ]
        ]],
        "application_context" => [
            "cancel_url" => $buffero->cancel_url,
            "return_url" => $buffero->return_url
        ] 
    ];

    try{

        $response = $client->execute($request);
        return json_encode($response);

    }catch(HttpException $ex){

        return json_encode($ex->statusCode);

    }       

}
/**
 * Busca un pedido en paypal
 * 
 * @param array $pedido Datos del pedido.
 * @return string JSON devuelto.
 */
function getPedido($pedido){

    //Creamos el cliente
    $client = PayPalClient::client($pedido['f']);

    $request = new OrdersCaptureRequest($pedido['ref']);
    $request->prefer('return=representation');
    try {
        
        $response = $client->execute($request);
        
        return json_encode($response);

    }catch (HttpException $ex) {

        return json_encode($ex->statusCode);
    }

}