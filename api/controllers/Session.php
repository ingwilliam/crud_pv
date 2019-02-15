<?php

//error_reporting(E_ALL);
//ini_set('display_errors', '1');
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Postgresql as DbAdapter;
use Phalcon\Config\Adapter\Ini as ConfigIni;
use Phalcon\Http\Request;

// Definimos algunas rutas constantes para localizar recursos
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH);

$config = new ConfigIni('../config/config.ini');

// Registramos un autoloader
$loader = new Loader();

$loader->registerDirs(
        [
            APP_PATH . '/models/',
        ]
);

$loader->register();

// Crear un DI
$di = new FactoryDefault();

//Set up the database service
$di->set('db', function () use ($config) {
    return new DbAdapter(
            array(
        "host" => $config->database->host,
        "username" => $config->database->username,
        "password" => $config->database->password,
        "dbname" => $config->database->name
            )
    );
});

$app = new Micro($di);

// Recupera todos los registros
$app->post('/iniciar_session', function () use ($app, $config) {

    //Consulto el usuario por username del parametro get
    $usuario_validar = Usuarios::findFirst("username = '" . $this->request->getPost('username') . "'");

    //Valido si existe
    if (isset($usuario_validar->id)) {
        //Valido si la clave es igual al token del usuario
        if ($this->security->checkHash($this->request->getPost('password'), $usuario_validar->password)) {
            //Fecha actual
            $fecha_actual = date("Y-m-d H:i:s");
            //Fecha limite de videgncia del token de de acceso
            $fecha_limit = date("Y-m-d H:i:s", strtotime('+' . $config->database->time_session . ' minute', strtotime($fecha_actual)));
            
            //Consulto y elimino todos los tokens que ya no se encuentren vigentes
            $tokens_eliminar = Tokens::find("date_limit<='" . $fecha_actual . "'");
            $tokens_eliminar->delete();

            //Elimino el token del usuario
            unset($usuario_validar->password);           
            //Creo el token de acceso para el usuario solicitado, con vigencia del valor configurado en el $config time_session
            $tokens = new Tokens();            
            $tokens->token = $this->security->hash($usuario_validar->id . "-" . $usuario_validar->tipo_documento . "-" . $usuario_validar->numero_documento);
            $tokens->user_current = json_encode($usuario_validar);
            $tokens->date_create = $fecha_actual;
            $tokens->date_limit = $fecha_limit;
            $tokens->save();
            
            //Genero el array que retornare como json, para el manejo del localStorage en el cliente
            $token_actual = array("token"=>$tokens->token, "usuario"=>$usuario_validar->primer_nombre." ".$usuario_validar->segundo_nombre." ".$usuario_validar->primer_apellido." ".$usuario_validar->segundo_apellido );            
            echo json_encode($token_actual);
        } else {
            echo "error";
        }
    } else {
        // To protect against timing attacks. Regardless of whether a user
        // exists or not, the script will take roughly the same amount as
        // it will always be computing a hash.        
        //echo $this->security->hash(rand());
        echo "error_metodo";
    }
}
);

// Permite verificar si el token esta activo
$app->post('/verificar_token', function () use ($app, $config) {

    try {
        //Fecha actual
        $fecha_actual = date("Y-m-d H:i:s");
        //Recupero el valor por post
        $token = $this->request->getPost('token');
        //Consulto y elimino todos los tokens que ya no se encuentren vigentes
        $tokens_eliminar = Tokens::find("date_limit<='" . $fecha_actual . "'");
        $tokens_eliminar->delete();
        //Consulto si el token existe y que este en el periodo de session
        $tokens = Tokens::findFirst("'" . $fecha_actual . "' BETWEEN date_create AND date_limit AND token = '" . $token . "'");
        //Verifico si existe para retornar
        if (isset($tokens->id)) {
            echo "ok";
        } else {
            echo "false";
        }
    } catch (Exception $ex) {
        echo "error_metodo";
    }
}
);

$app->get('/login_actions', function () use ($app, $config) {

    //Phalcon permite buscar directamente por nombre del campo, con metodo independiente
    //$user = Usuarios::findFirstByUsername("ingeniero.wb@gmail.com");
    
    //Consulto el usuario por username del parametro get
    $usuario_validar = Usuarios::findFirst("username = '" . $this->request->get('username') . "'");

    //Valido si existe
    if (isset($usuario_validar->id)) {
        //Valido si la clave es igual al token del usuario
        if ($this->security->checkHash($this->request->get('password'), $usuario_validar->password)) {
            
            //Fecha actual
            $fecha_actual = date("Y-m-d H:i:s");
            //Fecha limite de videgncia del token de de acceso
            $fecha_limit = date("Y-m-d H:i:s", strtotime('+' . $config->database->time_session . ' minute', strtotime($fecha_actual)));
            //Consulto y elimino todos los tokens que ya no se encuentren vigentes
            $tokens_eliminar = Tokens::find("date_limit<='" . $fecha_actual . "'");
            $tokens_eliminar->delete();

            //Elimino el token del usuario
            unset($usuario_validar->password);           
            //Creo el token de acceso para el usuario solicitado, con vigencia del valor configurado en el $config time_session
            $tokens = new Tokens();            
            $tokens->token = $this->security->hash($usuario_validar->id . "-" . $usuario_validar->tipo_documento . "-" . $usuario_validar->numero_documento);
            $tokens->user_current = json_encode($usuario_validar);
            $tokens->date_create = $fecha_actual;
            $tokens->date_limit = $fecha_limit;
            $tokens->save();            
            echo $tokens->token;
        } else {
            echo "error";
        }
    } else {
        // To protect against timing attacks. Regardless of whether a user
        // exists or not, the script will take roughly the same amount as
        // it will always be computing a hash.        
        //echo $this->security->hash(rand());
        echo "error";
    }
}
);

//Verifica permiso de lectura
$app->post('/permiso_lectura', function () use ($app) {

    try {
        //Instancio los objetos que se van a manejar
        $request = new Request();
        $tokens = new Tokens();

        //Consulto si al menos hay un token
        $token_actual = $tokens->verificar_token($request->getPost('token'));

        //Si el token existe y esta activo entra a realizar la tabla
        if ($token_actual > 0) {
            $user_current = json_decode($token_actual->user_current, true);
            
            //Consultar todos los permisos
            $phql = "SELECT mpp.* FROM Moduloperfilpermisos AS mpp "
                    . "INNER JOIN Modulos AS m ON m.id=mpp.modulo "
                    . "WHERE m.nombre='".$request->getPost('modulo')."' AND mpp.perfil IN (SELECT up.perfil FROM Usuariosperfiles AS up WHERE up.usuario=".$user_current["id"].")";
            $permisos = $app->modelsManager->executeQuery($phql);
            
            if( count($permisos)>0)
            {
                echo "ok";
            }
            else
            {
                echo "acceso_denegado";
            }
        } else {
            echo "error";
        }
    } catch (Exception $ex) {        
        echo "error_metodo".$ex;
    }
}
);

//Verifica permiso de lectura
$app->post('/cerrar_session', function () use ($app) {

    try {
        //Instancio los objetos que se van a manejar
        $request = new Request();
        $tokens = new Tokens();

        //Consulto si al menos hay un token
        $token_actual = $tokens->verificar_token($request->getPost('token'));

        //Si el token existe y esta activo entra a realizar la tabla
        if ($token_actual > 0) {
            if($token_actual->delete() != false) 
            {
                echo "ok";
            }
            else
            {
                echo "error";
            }
        } else {
            echo "error";
        }
    } catch (Exception $ex) {        
        echo "error_metodo".$ex;
    }
}
);

//Verifica permiso de escritura para los permisos de control total y lectura e escritura
$app->post('/permiso_escritura', function () use ($app) {

    try {
        //Instancio los objetos que se van a manejar
        $request = new Request();
        $tokens = new Tokens();

        //Consulto si al menos hay un token
        $token_actual = $tokens->verificar_token($request->getPost('token'));

        //Si el token existe y esta activo entra a realizar la tabla
        if ($token_actual > 0) {
            $user_current = json_decode($token_actual->user_current, true);
            
            //Consultar todos los permisos
            $phql = "SELECT mpp.* FROM Moduloperfilpermisos AS mpp "
                    . "INNER JOIN Modulos AS m ON m.id=mpp.modulo "
                    . "WHERE m.nombre='".$request->getPost('modulo')."' AND mpp.perfil IN (SELECT up.perfil FROM Usuariosperfiles AS up WHERE up.usuario=".$user_current["id"].") AND mpp.permiso IN (1,2) ";
            $permisos = $app->modelsManager->executeQuery($phql);
            
            if( count($permisos)>0)
            {
                echo "ok";
            }
            else
            {
                echo "acceso_denegado";
            }
        } else {
            echo "error";
        }
    } catch (Exception $ex) {        
        echo "error_metodo".$ex;
    }
}
);

try {
    // Gestionar la consulta
    $app->handle();
} catch (\Exception $e) {
    echo 'Excepción: ', $e->getMessage();
}
?>