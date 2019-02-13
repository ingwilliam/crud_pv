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
$app->get('/select', function () use ($app) {

    try {
        //Instancio los objetos que se van a manejar
        $request = new Request();
        $tokens = new Tokens();

        //Consulto si al menos hay un token
        $token_actual = $tokens->verificar_token($request->get('token'));
        
        //Si el token existe y esta activo entra a realizar la tabla
        if ($token_actual>0) {
            $phql = 'SELECT * FROM Sexos WHERE active = true ORDER BY nombre';
            $robots = $app->modelsManager->executeQuery($phql);
            echo json_encode($robots);
        }
        else
        {
            echo "error";
        }
    } catch (Exception $ex) {
        echo "error_metodo";
    }        
}
);

// Recupera todos los registros
$app->get('/all', function () use ($app) {
    
    $request = new Request();
    
    //Defino columnas para el orden desde la tabla html
    $columns = array( 
            0 =>'u.nombre',            
    );
        
    $where .=" WHERE u.active=true";
    //Condiciones para la consulta
    
    if( !empty($request->get("search")['value']) ) {               
            $where .=" AND ( UPPER(".$columns[0].") LIKE '%".strtoupper($request->get("search")['value'])."%' )";
    }

    //Defino el sql del total y el array de datos
    $sqlTot = "SELECT count(*) as total FROM Sexos AS u";
    $sqlRec = "SELECT ".$columns[0]." , concat('<button type=\"button\" class=\"btn btn-warning\" onclick=\"form_edit(',u.id,')\"><span class=\"glyphicon glyphicon-edit\"></span></button><button type=\"button\" class=\"btn btn-danger\" onclick=\"form_del(',u.id,')\"><span class=\"glyphicon glyphicon-remove\"></span></button>') as acciones FROM Sexos AS u";
    
    //concatenate search sql if value exist
    if(isset($where) && $where != '') {

            $sqlTot .= $where;
            $sqlRec .= $where;
    }
    
    //Concateno el orden y el limit para el paginador
    $sqlRec .=  " ORDER BY ". $columns[$request->get('order')[0]['column']]."   ".$request->get('order')[0]['dir']."  LIMIT ".$request->get('length')." offset ".$request->get('start')." ";
    
    //ejecuto el total de registros actual
    $totalRecords = $app->modelsManager->executeQuery($sqlTot)->getFirst();
    
    //creo el array
    $json_data = array(
			"draw"            => intval( $request->get("draw") ),   
			"recordsTotal"    => intval( $totalRecords["total"] ),  
			"recordsFiltered" => intval($totalRecords["total"]),
			"data"            => $app->modelsManager->executeQuery($sqlRec)   // total data array
			);
    //retorno el array en json
    echo json_encode($json_data);
}
);

// Crear registro
$app->post('/new', function () use ($app) {

    $post = $app->request->getPost();
    $user = new Sexos();    
    $user->active=true;    
    if ($user->save($post) === false) {
        echo "error";
    } else {
        echo $user->id;
    }
}
);

// Editar registro
$app->put('/edit/{id:[0-9]+}', function ($id) use ($app) {
        $user = $app->request->getPut();                    
        // Consultar el usuario que se esta editando
        $user2=Sexos::findFirst(json_decode($id));
        if ($user2->save($user) === false) {
            echo "error";
        }else{
            echo $id;
        }
    }
);

// Editar registro
$app->delete('/delete/{id:[0-9]+}', function ($id) use ($app) {        
    // Consultar el usuario que se esta editando
    $user=Sexos::findFirst(json_decode($id));    
    $user->active=false;
    if ($user->save($user) === false) {
        echo "error";
    }else{
        echo json_encode($user);
    }            
});

// Editar registro
$app->get('/search/{id:[0-9]+}', function ($id) use ($app) {        
        $phql = 'SELECT * FROM Sexos WHERE id = :id:';
        $user = $app->modelsManager->executeQuery($phql,['id' => $id,])->getFirst();
        echo json_encode($user);
    }
);


try {
    // Gestionar la consulta
    $app->handle();
} catch (\Exception $e) {
    echo 'Excepción: ', $e->getMessage();
}
?>