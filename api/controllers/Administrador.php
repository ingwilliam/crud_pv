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
$app->get('/menu', function () use ($app) {
    ?>
    <!-- Bootstrap Core JavaScript -->
    <script src="../../vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="../../vendor/metisMenu/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="../../dist/js/sb-admin-2.js"></script>

    <ul class="nav" id="side-menu">
        <li class="sidebar-search">
            <div class="input-group custom-search-form">
                <input type="text" class="form-control" placeholder="Search...">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button">
                        <i class="fa fa-search"></i>
                    </button>
                </span>
            </div>
            <!-- /input-group -->
        </li>
        <li>
            <a href="index.html"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
        </li>
        <li>
            <a href="#"><i class="fa fa-lock fa-fw"></i> Panel de Seguridad<span class="fa arrow"></span></a>
            <ul class="nav nav-second-level">
                <li>
                    <a href="../usuarios/list.html">Usuarios</a>
                    <a style="display: none" href="../usuarios/form.html">Usuarios</a>
                </li>
                <li>
                    <a href="../seguridad/list.html">Seguridad</a>                    
                </li>
                <li>
                    <a href="../perfiles/list.html">Perfiles</a>
                    <a style="display: none" href="../perfiles/form.html">Perfiles</a>
                </li>
                <li>
                    <a href="../modulos/list.html">Modulos</a>
                    <a style="display: none" href="../modulos/form.html">Modulos</a>
                </li>
                <li>
                    <a href="../permisos/list.html">Permisos</a>
                    <a style="display: none" href="../permisos/form.html">Permisos</a>
                </li>
            </ul>
            <!-- /.nav-second-level -->
        </li>
    </ul>
    <?php    
}
);

try {
    // Gestionar la consulta
    $app->handle();
} catch (\Exception $e) {
    echo 'Excepción: ', $e->getMessage();
}
?>