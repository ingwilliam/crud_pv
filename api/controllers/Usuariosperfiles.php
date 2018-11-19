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
$app->post('/new', function () use ($app) {

    $post = $app->request->getPost();    
    $user = new Usuariosperfiles();    
    if ($user->save($post) === false) {
        echo "error";
    } else {
        echo $user->id;
    }
}
);

// Editar registro
$app->delete('/delete/{user:[0-9]+}/{profile:[0-9]+}', function ($user,$profile) use ($app) {        
    // Consultar el usuario que se esta editando
    $user=Usuariosperfiles::find("usuario = ".$user." AND perfil=".$profile);    

    if ($user->delete() === false) {
        echo "error";
    }else{
        echo "ok";
    }        
                
});


try {
    // Gestionar la consulta
    $app->handle();
} catch (\Exception $e) {
    echo 'Excepción: ', $e->getMessage();
}
?>