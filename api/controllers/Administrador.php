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
$app->post('/menu', function () use ($app) {
    try {
        //Instancio los objetos que se van a manejar
        $request = new Request();
        $tokens = new Tokens();

        //Consulto si al menos hay un token
        $token_actual = $tokens->verificar_token($request->getPost('token'));

        //Si el token existe y esta activo entra a realizar la tabla
        if ($token_actual > 0) {
            //Extraemos el usuario del token
            $user_current = json_decode($token_actual->user_current, true);
            
            //Consultar todos los permiso del panel de seguridad
            $phql = "SELECT mpp.* FROM Moduloperfilpermisos AS mpp "
                    . "INNER JOIN Modulos AS m ON m.id=mpp.modulo "
                    . "WHERE m.nombre='Panel de Seguridad' AND mpp.perfil IN (SELECT up.perfil FROM Usuariosperfiles AS up WHERE up.usuario=".$user_current["id"].")";
            
            $permisos_panel_de_seguridad = $app->modelsManager->executeQuery($phql);
            
            //Consultar todos los permiso de la administración
            $phql = "SELECT mpp.* FROM Moduloperfilpermisos AS mpp "
                    . "INNER JOIN Modulos AS m ON m.id=mpp.modulo "
                    . "WHERE m.nombre='Administración' AND mpp.perfil IN (SELECT up.perfil FROM Usuariosperfiles AS up WHERE up.usuario=".$user_current["id"].")";
            
            $permisos_administracion = $app->modelsManager->executeQuery($phql);
            
            ?>

            <!-- Metis Menu Plugin JavaScript -->
            <script src="../../vendor/metisMenu/metisMenu.min.js"></script>

            <!-- Custom Theme JavaScript -->
            <script src="../../dist/js/sb-admin-2.js"></script>

            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.html">SB Admin v2.0</a>
            </div>
            <!-- /.navbar-header -->

            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-envelope fa-fw"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-messages">
                        <li>
                            <a href="#">
                                <div>
                                    <strong>John Smith</strong>
                                    <span class="pull-right text-muted">
                                        <em>Yesterday</em>
                                    </span>
                                </div>
                                <div>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque eleifend...</div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <strong>John Smith</strong>
                                    <span class="pull-right text-muted">
                                        <em>Yesterday</em>
                                    </span>
                                </div>
                                <div>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque eleifend...</div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <strong>John Smith</strong>
                                    <span class="pull-right text-muted">
                                        <em>Yesterday</em>
                                    </span>
                                </div>
                                <div>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque eleifend...</div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a class="text-center" href="#">
                                <strong>Read All Messages</strong>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                    <!-- /.dropdown-messages -->
                </li>
                <!-- /.dropdown -->
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-tasks fa-fw"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-tasks">
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <strong>Task 1</strong>
                                        <span class="pull-right text-muted">40% Complete</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 40%">
                                            <span class="sr-only">40% Complete (success)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <strong>Task 2</strong>
                                        <span class="pull-right text-muted">20% Complete</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
                                            <span class="sr-only">20% Complete</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <strong>Task 3</strong>
                                        <span class="pull-right text-muted">60% Complete</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%">
                                            <span class="sr-only">60% Complete (warning)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <strong>Task 4</strong>
                                        <span class="pull-right text-muted">80% Complete</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 80%">
                                            <span class="sr-only">80% Complete (danger)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a class="text-center" href="#">
                                <strong>See All Tasks</strong>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                    <!-- /.dropdown-tasks -->
                </li>
                <!-- /.dropdown -->
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-bell fa-fw"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-alerts">
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-comment fa-fw"></i> New Comment
                                    <span class="pull-right text-muted small">4 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-twitter fa-fw"></i> 3 New Followers
                                    <span class="pull-right text-muted small">12 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-envelope fa-fw"></i> Message Sent
                                    <span class="pull-right text-muted small">4 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-tasks fa-fw"></i> New Task
                                    <span class="pull-right text-muted small">4 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-upload fa-fw"></i> Server Rebooted
                                    <span class="pull-right text-muted small">4 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a class="text-center" href="#">
                                <strong>See All Alerts</strong>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                    <!-- /.dropdown-alerts -->
                </li>
                <!-- /.dropdown -->
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="../perfil/form.html"><i class="fa fa-user fa-fw"></i> Mi perfil</a>
                        </li>
                        <li><a href="#"><i class="fa fa-gear fa-fw"></i> Settings</a>
                        </li>
                        <li class="divider"></li>
                        <li><a href="javascript:void(0)" onclick="logout()"><i class="fa fa-sign-out fa-fw"></i> Cerrar sesión</a>
                        </li>
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
            </ul>
            <!-- /.navbar-top-links -->

            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">

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
                            <a href="../index/index.html"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                        </li>
                        <?php
                        if(count($permisos_panel_de_seguridad)>0)
                        {    
                        ?>
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
                        <?php
                        }
                        ?>
                        <?php
                        if(count($permisos_administracion)>0)
                        {    
                        ?>
                        <li>
                            <a href="#"><i class="fa fa-lock fa-fw"></i> Administracion<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="../tablasmaestras/list.html">Tablas maestras</a>
                                    <a style="display: none" href="../tablasmaestras/form.html">Tablas maestras</a>
                                </li>
                                <li>
                                    <a href="../paises/list.html">Paises</a>
                                    <a style="display: none" href="../paises/form.html">Paises</a>
                                </li>
                                <li>
                                    <a href="../departamentos/list.html">Departamentos</a>                    
                                    <a style="display: none" href="../departamentos/form.html">Departamentos</a>
                                </li>
                                <li>
                                    <a href="../ciudades/list.html">Ciudades</a>
                                    <a style="display: none" href="../ciudades/form.html">Ciudades</a>
                                </li>
                                <li>
                                    <a href="../localidades/list.html">Localidades</a>
                                    <a style="display: none" href="../localidades/form.html">Localidades</a>                                    
                                </li>
                                <li>
                                    <a href="../upz/list.html">Upz</a>
                                    <a style="display: none" href="../upz/form.html">Upz</a>
                                </li>
                                <li>
                                    <a href="../barrios/list.html">Barrios</a>
                                    <a style="display: none" href="../barrios/form.html">Barrios</a>
                                </li>
                                <li>
                                    <a href="../tiposdocumentos/list.html">Tipos de documentos</a>
                                    <a style="display: none" href="../tiposdocumentos/form.html">Tipos de documentos</a>
                                </li>
                                <li>
                                    <a href="../sexos/list.html">Sexos</a>
                                    <a style="display: none" href="../sexos/form.html">Sexos</a>
                                </li>
                                <li>
                                    <a href="../orientacionessexuales/list.html">Orientaciones sexuales</a>
                                    <a style="display: none" href="../orientacionessexuales/form.html">Orientaciones sexuales</a>
                                </li>
                                <li>
                                    <a href="../identidadesgeneros/list.html">Identidades de generos</a>
                                    <a style="display: none" href="../identidadesgeneros/form.html">Identidades de generos</a>
                                </li>
                                <li>
                                    <a href="../niveleseducativos/list.html">Niveles educativos</a>
                                    <a style="display: none" href="../niveleseducativos/form.html">Niveles educativos</a>
                                </li>
                                <li>
                                    <a href="../lineasestrategicas/list.html">Líneas estratégicas</a>
                                    <a style="display: none" href="../lineasestrategicas/form.html">Líneas estratégicas</a>
                                </li>
                                <li>
                                    <a href="../areas/list.html">Areas</a>
                                    <a style="display: none" href="../areas/form.html">Areas</a>
                                </li>
                                <li>
                                    <a href="../modalidades/list.html">Modalidades</a>
                                    <a style="display: none" href="../modalidades/form.html">Modalidades</a>
                                </li>
                                <li>
                                    <a href="../documentosconvocatorias/list.html">Documentos convocatorias</a>
                                    <a style="display: none" href="../documentosconvocatorias/form.html">Documentos convocatorias</a>
                                </li>
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>
                        <?php
                        }
                        ?>
                    </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
            <?php
        } else {
            echo "error";
        }
    } catch (Exception $ex) {
        echo "error_metodo" . $ex;
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