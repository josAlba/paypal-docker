<?php

require __DIR__ . '/../vendor/autoload.php';
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



/** Crear pedido */
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

        // Call API with your client and get a response for your call
        $response = $client->execute($request);
        
        // If call returns body in response, you can get the deserialized version from the result attribute of the response
        print_r($response);

    }catch(HttpException $ex){

        echo $ex->statusCode;
        print_r($ex->getMessage());

    }       

}