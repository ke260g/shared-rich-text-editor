<?php
require './submodules/online-clipboard/func.php';
$config = require './submodules/online-clipboard/config.php';
parse_str($_SERVER["QUERY_STRING"], $QUERY_TABLE);
$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

$SAVE_EXPIRE = 24*3600;

function ConnectRedis() {
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

function DoParseParam() {
    global $QUERY_TABLE;
    $uuid = $QUERY_TABLE['uuid'];

    if (empty($uuid) or !isset($uuid)) {
        // TODO: 返回错误原因
        exit();
    } else if (!ctype_alnum($uuid)) {
        // TODO: 返回错误原因
        exit();
    }
    return $uuid;
}

# 提取参数
$uuid = DoParseParam();
# 初始化
$hash  = md5($uuid);
$redis = ConnectRedis();

# 路由
switch ($REQUEST_METHOD) {
    case 'POST':
        OnPost($redis, $hash);
    case 'GET':
        OnGet($redis, $hash);
    default:
}