<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
session_start();
spl_autoload_register(function ($class) {
    $dirs = [__DIR__.'/../../src/Controllers/',__DIR__.'/../../src/Models/',__DIR__.'/../../src/Middleware/',__DIR__.'/../../src/Helpers/',__DIR__.'/../../src/Config/'];
    foreach ($dirs as $dir) { $f = $dir.$class.'.php'; if (file_exists($f)) { require_once $f; return; } }
});
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/api', '', $uri);
$uri = trim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];
if ($uri === '' || $uri === 'index.php') { echo json_encode(['code'=>0,'message'=>'MusicSite API v1.0','data'=>null]); exit; }
$routes = [
    'POST auth/register'=>[AuthController::class,'register'],'POST auth/login'=>[AuthController::class,'login'],'POST auth/logout'=>[AuthController::class,'logout'],
    'GET auth/me'=>[AuthController::class,'me'],'PUT auth/profile'=>[AuthController::class,'updateProfile'],'PUT auth/password'=>[AuthController::class,'changePassword'],
    'GET songs'=>[SongController::class,'index'],'GET songs/hot'=>[SongController::class,'hot'],'GET songs/latest'=>[SongController::class,'latest'],
    'GET categories'=>[SongController::class,'categories'],'GET favorites'=>[SongController::class,'favorites'],'GET history'=>[SongController::class,'history'],
    'GET admin/stats'=>[AdminController::class,'stats'],'GET admin/songs'=>[AdminController::class,'songs'],'POST admin/songs/upload'=>[AdminController::class,'uploadSong'],
    'POST admin/users'=>[AdminController::class,'createUser'],'PUT admin/categories'=>[AdminController::class,'createCategory'],'GET admin/users'=>[AdminController::class,'users'],
];
$dynamicRoutes = [
    ['pattern'=>'#^songs/(\d+)$#','GET'=>[SongController::class,'show']],['pattern'=>'#^songs/(\d+)/play$#','GET'=>[SongController::class,'play']],
    ['pattern'=>'#^favorite/(\d+)$#','POST'=>[SongController::class,'favorite']],['pattern'=>'#^admin/songs/(\d+)$#','PUT'=>[AdminController::class,'updateSong']],
    ['pattern'=>'#^admin/songs/(\d+)$#','DELETE'=>[AdminController::class,'deleteSong']],['pattern'=>'#^admin/users/(\d+)/toggle$#','PUT'=>[AdminController::class,'toggleUser']],
    ['pattern'=>'#^admin/categories/(\d+)$#','PUT'=>[AdminController::class,'updateCategory']],['pattern'=>'#^admin/categories/(\d+)$#','DELETE'=>[AdminController::class,'deleteCategory']],
];
$routeKey = "{$method} {$uri}";
if (isset($routes[$routeKey])) { [$class,$action]=$routes[$routeKey]; $ctrl=new $class(); $ctrl->$action(); exit; }
foreach ($dynamicRoutes as $route) { if (preg_match($route['pattern'],$uri,$matches)&&isset($route[$method])) { [$class,$action]=$route[$method]; $ctrl=new $class(); $ctrl->$action((int)$matches[1]); exit; } }
http_response_code(404); echo json_encode(['code'=>404,'message'=>'API not found','data'=>null]);
