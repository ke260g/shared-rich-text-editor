<?php
require 'func.php';
$config = require 'config.php';
parse_str($_SERVER["QUERY_STRING"], $QUERY_TABLE);
$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

$SAVE_EXPIRE = 24*3600;

function ConnectRedis($hash) {
    global $config;
    $redis  = new Redis();
    $redis->pconnect($config['redis']['host'], $config['redis']['port']);

    # debug information
    # print "Server is running: " . $redis->ping()."\n";
    # var_dump($config['redis']);

    if($config['redis']['pass']){
        $redis->auth( $config['redis']['pass'] );
    }

    return $redis;
}

function OnGet($redis, $hash) {
    global $SAVE_EXPIRE;
    $str  = '';
    header('Content-Type', 'text/plain; charset=utf-8');
    $messages = $redis->lRange($hash, 0, 0);
    foreach($messages as $k => $m) {
        $m = htmlspecialchars_decode($m);
        $str .= "{$m}\n\n";
    }
    echo $str;

    # refresh timeout
    $redis->setTimeout($hash, $SAVE_EXPIRE);
}

function OnPost($redis, $hash) {
    global $SAVE_EXPIRE;

    # save_cb will retrict redis space usage
    # save_cb will echo data on success
    save_cb($redis, $hash, $_POST['data']);

    # refresh timeout
    $redis->setTimeout($hash, $SAVE_EXPIRE);
}

# 提取参数
$username = $QUERY_TABLE['username'];
$password = $QUERY_TABLE['password'];

# 检查参数
if (empty($username) or !isset($username) or
    empty($password) or !isset($password)) {
    echo '';
    return;
}

# 初始化
$hash  = md5($password . $username);
$redis = ConnectRedis($hash);

# 路由
switch ($REQUEST_METHOD) {
    case 'POST':
        OnPost($redis, $hash);
    case 'GET':
        OnGet($redis, $hash);
    default:
}