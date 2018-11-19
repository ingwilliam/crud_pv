<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Postgresql as DbAdapter;
use Phalcon\Config\Adapter\Ini as ConfigIni;
use Phalcon\Http\Request;

require "../library/movilmente/ImageUpload.php";

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
$app->get('/all', function () use ($app) {
    
    $request = new Request();
    
    //Defino columnas para el orden desde la tabla html
    $columns = array( 
            0 =>'u.primer_nombre',
            1 =>'u.segundo_nombre', 
            2 => 'u.primer_apellido',
            3 => 'u.segundo_apellido',
            4 => 'u.username',
            5 => 'b.nombre',
    );
        
    $where .=" WHERE u.active=true";
    //Condiciones para la consulta
    if( !empty($request->get("search")['value']) ) {               
            $where .=" AND ( ".$columns[0]." LIKE '%".$request->get("search")['value']."%' ";    
            $where .=" OR ".$columns[1]." LIKE '%".$request->get("search")['value']."%' ";
            $where .=" OR ".$columns[2]." LIKE '%".$request->get("search")['value']."%' ";
            $where .=" OR ".$columns[3]." LIKE '%".$request->get("search")['value']."%' ";            
            $where .=" OR ".$columns[4]." LIKE '%".$request->get("search")['value']."%' ";            
            $where .=" OR ".$columns[5]." LIKE '%".$request->get("search")['value']."%' )";
    }

    //Defino el sql del total y el array de datos
    $sqlTot = "SELECT count(*) as total FROM Usuarios AS u "
            . "INNER JOIN Sexos AS b ON b.id=u.sexo ";
    $sqlRec = "SELECT ".$columns[0].",".$columns[1].",".$columns[2].",".$columns[3].",".$columns[4].",".$columns[5]." AS sexo , concat('<button type=\"button\" class=\"btn btn-warning\" onclick=\"form_edit(',u.id,')\"><span class=\"glyphicon glyphicon-edit\"></span></button><button type=\"button\" class=\"btn btn-danger\" onclick=\"form_del(',u.id,')\"><span class=\"glyphicon glyphicon-remove\"></span></button>') as acciones FROM Usuarios AS u "
            . "INNER JOIN Sexos AS b ON b.id=u.sexo ";
    
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
    $usuario = new Usuarios();
    $usuario->active=true;    
    if ($usuario->save($post) === false) {
        echo "error";
    } else {
        echo $usuario->id;
    }
}
);

// Editar registro
$app->put('/edit/{id:[0-9]+}', function ($id) use ($app) {
        $usuario = $app->request->getPut();                            
        // Consultar el usuario que se esta editando
        $usuario2=Usuarios::findFirst(json_decode($id));
        if ($usuario2->save($usuario) === false) {
            echo "error";
        }else{
            echo $id;
        }
    }
);

// Editar registro
$app->delete('/delete/{id:[0-9]+}', function ($id) use ($app) {        
    // Consultar el usuario que se esta editando
    $usuario=Usuarios::findFirst(json_decode($id));    
    $usuario->active=false;
    if ($usuario->save($usuario) === false) {
        echo "error";
    }else{
        echo json_encode($usuario);
    }            
});

// Editar registro
$app->get('/search/{id:[0-9]+}', function ($id) use ($app) {        
        $phql = 'SELECT * FROM Usuarios WHERE id = :id:';
        $usuario = $app->modelsManager->executeQuery($phql,['id' => $id,])->getFirst();
        echo json_encode($usuario);
    }
);

//Recupera todos los registros
$app->post('/imageupload', function () use ($app) {

    $ImageUpload = new ImageUpload();
    $content = file_get_contents("php://input");

    $array = array();

    parse_str($content, $array);

    $validar = $ImageUpload->validate($array['encodedImage']);

    if ($validar == 1) {
        $imagen = $ImageUpload->save_base64_image($array['encodedImage'], "resources/img/IMG_" . strtoupper(md5($array['encodedImage'])));

        if ($imagen != null) {
            $ImageUpload->print_json(200, "Completado", $imagen);
        } else {
            $ImageUpload->print_json(200, "Este archivo ya existe", null);
        }
    } else {
        $ImageUpload->print_json(200, "Extension invalida", null);
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