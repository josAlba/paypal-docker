<?php
/**
 * 
 * Estructura de los datos.
 * 
 * $data=array(
 *  "f"=>'',    //Fichero para leer
 *  "ref"=>'',  //Referencia
 *  "to"=>'',   //Total
 *  "q"=>'accion'
 * );
 * 
 *  *. Para capturar el pedido tiene que ser aprobado por el comprador.
 * 
 */
//require __DIR__ . '/vendor/autoload.php';

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

/**
 * Cliente Paypal
 */
class PayPalClient{

    private static $dev=true;
    private static $CLIENT_ID      ='';
    private static $CLIENT_SECRET  ='';

    /**
     * Returns PayPal HTTP client instance with environment which has access
     * credentials context. This can be used invoke PayPal API's provided the
     * credentials have the access to do so.
     */
    public static function client($user='',$dev=true){
        
        $buffero = getFile::get($user);

        self::$CLIENT_ID        =   $buffero->clientId; 
        self::$CLIENT_SECRET    =   $buffero->clientSecret;

        self::$dev  = $dev;

        return new PayPalHttpClient(self::environment());
    }
    
    /**
     * Setting up and Returns PayPal SDK environment with PayPal Access credentials.
     * For demo purpose, we are using SandboxEnvironment. In production this will be
     * ProductionEnvironment.
     */
    public static function environment(){

        $clientId = getenv("CLIENT_ID") ?: self::$CLIENT_ID;
        $clientSecret = getenv("CLIENT_SECRET") ?: self::$CLIENT_SECRET;

        if(self::$dev==true){
            return new SandboxEnvironment($clientId, $clientSecret);
        }else{
            return new SandboxEnvironment($clientId, $clientSecret);
        }
        
    }

}
/**
 * Se encarga de leer los datos de configuracion del usuario.
 */
class getFile{

    public static function get($file=''){

        if($file==''){
            print_r(array(
                "fichero"=>1,
                "name"=>$file
            ));
            exit();
        }
        
        $file = __DIR__.'/user/'.$file.'.json';

        if(!file_exists($file)){
            print_r(array(
                "fichero"=>2,
                "name"=>$file
            ));
            exit();
        }

        $gestor     = file_get_contents($file, true);
        $buffero    = json_decode($gestor);

        return $buffero;

    }

}
/**
 * Clase para almacenar los ficheros para el registro de transacciones
 */
class NoDB{

    /**
     * Almacena los datos en un archivo de json
     * 
     * @param string $name Nombre de la transaccion.
     * @param string $json JSON con los datos de la transaccion.
     */
    public static function set($name,$json){

        //Crea el directorio si aun no existe, para almacenar las transacciones.
        if(! file_exists(__DIR__.'/transacciones') ){
            mkdir(__DIR__.'/transacciones');
        }

        //ID - transaccion.
        $dir = __DIR__.'/transacciones/'.md5($name);

        //Comprueba si existe esta transaccion.
        if(! file_exists($dir) ){
            mkdir($dir);
        }

        //Nuevo fichero con los datos de la transaccion.
        $file = date("Y.m.d.H.i.s").'-'.md5($name).'.json';
        $file = $dir.'/'.$file;
        //Almacenamos la transaccion.
        $ofile = fopen($file,'a');
        fwrite($ofile,$json);

        //Almacenamos en el log los datos de la transaccion.
        self::setlog($name,$file);

        return true;

    }
    /**
     * Recupera informacion de una transaccion.
     * 
     * @param string $name Nombre de la transaccion.
     * @return array Datos de las diferentes transacciones.
     */
    public static function get($name){

        //Montamos el directorio de la transaccion.
        $dir = __DIR__.'/transacciones/'.md5($name);

        //Comprobamos que exista la transaccion.
        if(! file_exists($dir) ){
            return [];
        }

        $data = [];

        //Recuperamos el listado de archivos.
        $cdir  = scandir($dir);

        //Recoremos los archivos de la transaccion, leemos el fichero y lo almacenamos en el array.
        foreach ($cdir as $key => $value){

            if (!in_array($value,array(".",".."))){

                $gestor     = file_get_contents($dir.'/'.$value, true);
                $buffero    = json_decode($gestor);

                $data[]=$buffero;

            }

        }

        //Devolvemos los datos de la transaccion.
        return $data;

    }
    /**
     * Almacena un log de correos eviados
     * @access private
     * @param string $name 
     * @param string $id
     */
    public static function setlog($name="",$id=""){

        $ddf = fopen(__DIR__.'/log/transaccion.log','a');
        fwrite($ddf,"[".date("r")."] Nueva transaccion: $name \t $id \r\n");
        fclose($ddf);
    } 

}


/**
 * Prende el inicio del script.
 */
function Mecha($data){

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

        NoDB::set($pedido['ref'],json_encode($response));

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

    $p = NoDB::get($pedido['ref']);
    if(count($p)==0){
        
        return json_encode(array(
            'find'=>array(
                'query' => $pedido['ref'],
                'id'    => "n/a",
                'value' => false,
                'description' => 'Pedido no existe'
            )
        ));
    }

    $seleccionado = $p[(count($p)-1)];
    $r = $seleccionado->result->id;

    //Creamos el cliente
    $client = PayPalClient::client($pedido['f']);
    //Buscamos el pedido
    $request = new OrdersCaptureRequest($r);
    $request->prefer('return=representation');
    echo "\n OK\n";
    try {
        
        $response = $client->execute($request);
        
        return json_encode(array(
            'find'=>array(
                'query' => $pedido['ref'],
                'id'    => $r,
                'value' => true,
                'data'  => $response
            )
        ));

    }catch(Exception $ex) {

        return json_encode(array(
            'find'=>array(
                'query' => $pedido['ref'],
                'id'    => $r,
                'value' => false,
                'description' => 'Pedido no encontrado'
            )
        ));
    
    }

}