<?php
//机器人的appid
$appid = "";
//机器人的secret
$secret = "";

include "function/Access.php";
// 立即返回200响应
http_response_code(200);
header('Content-Type: application/json');
if (function_exists('fastcgi_finish_request')) {
  fastcgi_finish_request();
} else {
  ob_end_flush();
  flush();
}

// 开始异步处理
ignore_user_abort(true);
set_time_limit(0);


//定义全局变量
$GLOBALS['appid'] = $appid;
$GLOBALS['secret'] = $secret;
$data = file_get_contents('php://input');

if (empty($data)) {
  $type = $_GET['type'];
  if (!empty($type)){
    if ($type=='test'){
      $data = [
        't' => 'test',
        'd' => [
          'id' => 'test',
          'content' => $_GET['msg'],
          'timestamp' => time(),
          'group_id' => 'test_group',
          'author' => ['id' => 'test_user']
        ],
      ];
      include "core/plugin/PluginManager.php";
      include "core/event/MessageEvent.php";
      $pluginManager = new PluginManager();
      $pluginManager->loadPlugins();
      $event = new MessageEvent(Json($data));
      if (!$pluginManager->dispatchMessage($event)) {
        // 默认回复（无插件匹配时）
        $event->reply("test");
        exit;
      }
    }
    exit;
  }
  // 收集服务状态信息
  $info = "服务状态信息\n";
  $me = BOTAPI("/users/@me", "GET", $json);
  $name = Json取($me, 'username');
  $info .= "==================\n";
  $info .= "Bot: " . $name . "\n";
  $info .= "PHP 版本: " . phpversion() . "\n";
  $info .= "服务状态: 运行中\n";
  $info .= "当前内存使用: " . formatBytes(memory_get_usage()) . "\n";
  $info .= "内存使用峰值: " . formatBytes(memory_get_peak_usage()) . "\n";
  $info .= "服务器时间: " . date('Y-m-d H:i:s') . "\n";
  $info .= "PHP 运行模式: " . php_sapi_name() . "\n";
  $info .= "操作系统: " . PHP_OS . "\n";
  $info .= "脚本执行时间: " . round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 4) . " 秒\n";
  //$info .= "已加载的PHP扩展: " . implode(", ", get_loaded_extensions()) . "\n";
  // 输出纯文本结果
  echo $me;
  exit;
}
//echo Json(['code' => 0, 'msg' => '接收成功']);
$json = json_decode($data, true);
$op = $json["op"];
$t = $json["t"];
//签名校验
if ($op == 13) {
  include "function/sign.php";
  $sign = new signs();
  echo $sign->sign($data);
  exit;
}

//消息事件
if ($op == 0) {
  include "core/plugin/PluginManager.php";
  include "core/event/MessageEvent.php";
  include "core/hash/hash.php";
  $o = new OpenIDConverter('data/id_map.json');
  $pluginManager = new PluginManager();
  $pluginManager->loadPlugins();
  $event = new MessageEvent($data);
  if (strpos(file_get_contents('message.log'), $event->timestamp) !== false) {
    echo "重复消息，已忽略";
    exit;
  } else {
    file_put_contents('message.log', date('Y-m-d H:i:s') . " 收到消息: " . Json($json) . PHP_EOL, FILE_APPEND);
  }
  if (!$pluginManager->dispatchMessage($event)) {
    // 默认回复（无插件匹配时）
    $event->reply("指令错误或未输入指令 ");
    exit;
  }
  exit;
}


//功能函数封装区————————————————————————————————————————————————————————————————————————————————————
//功能函数封装区————————————————————————————————————————————————————————————————————————————————————
//功能函数封装区————————————————————————————————————————————————————————————————————————————————————
// 转换字节为易读格式
function formatBytes($bytes, $precision = 2)
{
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];
  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);
  $bytes /= (1 << (10 * $pow));
  return round($bytes, $precision) . ' ' . $units[$pow];
}
function 上传群图片($group, $content)
{
  return json_decode(BOTAPI("/v2/groups/{$group}/files", "POST", Json(['srv_send_msg' => false, "file_type" => 1, "file_data" => base64_encode($content)])));
}
function 发群($group, $content)
{
  return BOTAPI("/v2/groups/" . $group . "/messages", "POST", Json($content));
}
function 发群2($group, $content, $type = 0, $id)
{
  //dm{"type":"ark", "template_id": 23, "kv": [ { "key": "#DESC#", "value": "我是DESC" }, { "key": "#PROMPT#", "value": "我是外显" }, { "key": "#LIST#", "obj": [ { "obj_kv": [ { "key": "desc", "value": "111" } ] }, { "obj_kv": [ { "key": "link", "value": "https://QUN.QQ.COM/qunpro/robot/qunshare?robot_uin=3889042293" },{ "key": "desc", "value": "我是标题1" } ] } ] } ] }
  $ark = [
    'template_id' => 23,
    'kv' => [
      ['key' => '#DESC#', 'value' => 'TSmoe'],
      ['key' => '#PROMPT#', 'value' => '闲仁Bot'],
      [
        'key' => '#LIST#',
        'obj' => [
          [
            'obj_kv' => [
              ['key' => 'desc', 'value' => $content]
            ]
          ]
        ]
      ]
    ]
  ];
  return BOTAPI("/v2/groups/" . $group . "/messages", "POST", json_encode([
    "msg_id" => $id,
    "msg_type" => 3,
    "ark" => $ark,
    "msg_seq" => rand(10000, 999999)
  ]));
}
function convertURL($url)
{
  if ($url === null)
    return '';

  $urlStr = strval($url);
  $parts = explode('://', $urlStr);

  if (count($parts) === 1)
    return strtoupper($urlStr);

  $protocol = strtolower($parts[0]);
  $rest = implode('://', array_slice($parts, 1));

  $hostPart = strtok($rest, '/?#');
  $separatorIndex = strpos($rest, $hostPart) + strlen($hostPart);

  return $protocol . '://' .
    strtoupper($hostPart) .
    substr($rest, $separatorIndex);
}
?>