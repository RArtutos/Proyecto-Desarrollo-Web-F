<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');

session_name('APPSESSID');
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => false,
  'httponly' => true,
  'samesite' => 'Lax',
]);

session_start();

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/conexion.php';

require_once APP_PATH . '/modelos/CuentaModelo.php';
require_once APP_PATH . '/controladores/LoginControlador.php';

require_once APP_PATH . '/modelos/ServidorModelo.php';
require_once APP_PATH . '/controladores/ServidorControlador.php';
require_once APP_PATH . '/controladores/ServidorApiControlador.php';

require_once APP_PATH . '/controladores/RegistroControlador.php';
require_once APP_PATH . '/controladores/UsuarioControlador.php';
require_once APP_PATH . '/controladores/RespaldoControlador.php';

// Detectar el BASE_URL ocultando la carpeta 'public' para que no aparezca en las URLs
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$basePath = preg_replace('/\/public$/', '', $scriptName);
$BASE_URL = ($basePath === '/' || $basePath === '.') ? '' : $basePath;
define('BASE_URL', $BASE_URL);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($uri !== '/' && substr($uri, -1) === '/') $uri = rtrim($uri, '/');

if (BASE_URL !== '' && str_starts_with($uri, BASE_URL)) {
  $uri = substr($uri, strlen(BASE_URL));
  if ($uri === '') $uri = '/';
}

function validarCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Error de seguridad: Token CSRF no válido.']);
            exit;
        }
    }
}

$auth = new LoginControlador();

switch (true) {
  case (($uri === '/' || $uri === '/index.php') && $method === 'GET'):
    header('Location: ' . BASE_URL . (!empty($_SESSION['usuario']) ? '/dashboard' : '/login'));
    exit;

  case ($uri === '/login' && $method === 'GET'):
    $auth->mostrarLogin();
    exit;

  case ($uri === '/login' && $method === 'POST'):
    $auth->login();
    exit;

  case ($uri === '/logout' && $method === 'GET'):
    $auth->logout();
    exit;

  case ($uri === '/instaladorDemonio.sh' && $method === 'GET'):
    header('Content-Type: text/x-shellscript');
    readfile(ROOT_PATH . '/instaladorDemonio.sh');
    exit;

  case (($uri === '/dashboard' || $uri === '/dashboard.php') && $method === 'GET'):
    if (empty($_SESSION['usuario'])) {
      header("Location: " . BASE_URL . "/login");
      exit;
    }
    $srv = new ServidorControlador();
    $srv->dashboard(); 
    exit;

  
  case ($uri === '/add_servidor' && $method === 'GET'):
    if (empty($_SESSION['usuario'])) {
      header("Location: " . BASE_URL . "/login"); 
      exit;
    }
    require APP_PATH . '/vistas/dashboard/agregar_servidor.php';
    exit;

  case ($uri === '/respaldo' && $method === 'GET'):
    $res = new RespaldoControlador();
    $res->enviarTelegram();
    exit;

  
  case ($uri === '/add_servidor' && $method === 'POST'):
    if (empty($_SESSION['usuario'])) {
      header("Location: " . BASE_URL . "/login");
      exit;
    }
    validarCSRF(); 
    $srv = new ServidorControlador();
    $srv->guardar();
    exit;

  case ($uri === '/edit_servidor' && $method === 'POST'):
    if (empty($_SESSION['usuario'])) {
      header("Location: " . BASE_URL . "/login");
      exit;
    }
    validarCSRF();
    $srv = new ServidorControlador();
    $srv->editar();
    exit;

  case ($uri === '/delete_servidor' && $method === 'POST'):
    if (empty($_SESSION['usuario'])) {
      header("Location: " . BASE_URL . "/login");
      exit;
    }
    validarCSRF();
    $srv = new ServidorControlador();
    $srv->eliminar();
    exit;

  case ($uri === '/add_usuario' && $method === 'GET'):
    if (empty($_SESSION['usuario'])) {
      header("Location: " . BASE_URL . "/login");
      exit;
    }
    require APP_PATH . '/vistas/dashboard/agregar_usuario.php';
    exit;

  case ($uri === '/add_usuario' && $method === 'POST'):
    if (empty($_SESSION['usuario'])) {
      header("Location: " . BASE_URL . "/login");
      exit;
    }
    validarCSRF();
    $reg = new RegistroControlador();
    $reg->registrar();
    exit;

  case ($uri === '/list_usuarios' && $method === 'GET'):
    if (empty($_SESSION['usuario'])) {
      http_response_code(401);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'No autorizado']);
      exit;
    }
    $usr = new UsuarioControlador();
    $usr->listarUsuarios();
    exit; 


    case ($uri === '/api/usuarios' && $method === 'GET'):
      if (empty($_SESSION['usuario'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
      }
      $usr = new UsuarioControlador();
      $usr->apiListarUsuarios();
      exit;   

  case ($uri === '/edit_usuario' && $method === 'POST'):
    validarCSRF();
    $usr = new UsuarioControlador();
    $usr->editarUsuario();
    exit;

  case ($uri === '/delete_usuario' && $method === 'POST'):
    validarCSRF();
    $usr = new UsuarioControlador();
    $usr->eliminarUsuario();
    exit;

  case ($uri === '/list_usuarios_activos' && $method === 'GET'):
    (new UsuarioControlador())->usuariosActivos();
    break;
  
  case ($uri === '/toggle_usuario' && $method === 'POST'):
    validarCSRF();
    (new UsuarioControlador())->toggleUsuario();
    break;

  case ($uri === '/logout' && $method === 'GET'):
    session_start();
    $_SESSION = [];
    session_destroy();
    header("Location: " . BASE_URL . "/login");
    exit;

    $reg = new RegistroControlador();
    $reg->registrar();
    exit;
    


    case ($uri === '/api/servidores' && $method === 'GET'):
      if (empty($_SESSION['usuario'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
      }
      $srv = new ServidorControlador();
      $srv->apiServidores();
      exit;
    
    case (preg_match('#^/api/servidor/(\d+)/usuarios$#', $uri, $matches) && $method === 'GET'):
      $srv = new ServidorControlador();
      $srv->apiListarUsuariosServidor((int)$matches[1]);
      exit;

    case (preg_match('#^/api/servidor/(\d+)/usuarios/agregar$#', $uri, $matches) && $method === 'POST'):
      validarCSRF(); 
      $srv = new ServidorControlador();
      $srv->apiAgregarUsuarioServidor((int)$matches[1]);
      exit;

    case (preg_match('#^/api/servidor/(\d+)/usuarios/eliminar$#', $uri, $matches) && $method === 'POST'):
      validarCSRF(); 
      $srv = new ServidorControlador();
      $srv->apiEliminarUsuarioServidor((int)$matches[1]);
      exit;

    case (preg_match('#^/api/servidor/(\d+)/usuarios/promover$#', $uri, $matches) && $method === 'POST'):
      validarCSRF();
      $srv = new ServidorControlador();
      $srv->apiPromoverUsuarioServidor((int)$matches[1]);
      exit;

    case (preg_match('#^/api/servidor/(\d+)/usuarios/degradar$#', $uri, $matches) && $method === 'POST'):
      validarCSRF();
      $srv = new ServidorControlador();
      $srv->apiDegradarUsuarioServidor((int)$matches[1]);
      exit;

    case ($uri === '/api/keepalive' && $method === 'POST'):
      (new ServidorApiControlador())->keepalive();
      exit;
  
    case ($uri === '/api/shutdown' && $method === 'POST'):
      (new ServidorApiControlador())->shutdown();
      exit;
  
    
      case ($uri === '/api/whoami' && $method === 'GET'):
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
          'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
          'x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
          'x_real_ip' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
        ]);
        exit;

        
  default:
    http_response_code(404);
    echo "404";
    exit;
}
