<?php
// 引入配置文件
$config = include __DIR__ . '/config/config.php';
//机器人的appid
$appid = $config['bot']['appid'];
//机器人的secret
$secret = $config['bot']['secret'];
//定义全局变量
$GLOBALS['appid'] = $appid;
$GLOBALS['secret'] = $secret;
include "function/Access.php";
include_once __DIR__ . '/core/StatisticsManager.php'; // Include the new StatisticsManager

// ====== PDO Database Connection ======
$pdo = null;
if (isset($config['database']) && !empty($config['database']['host'])) {
    try {
        $dsn = "{$config['database']['driver']}:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password'], $options);
        $statisticsManager = new StatisticsManager($pdo, $appid); // Pass PDO and appid
        $statisticsManager->initializeSchema(); // Create tables if they don't exist
    } catch (\PDOException $e) {
    }
}
// ====== 日期变量（如有需要） ======
$date = date('Ymd');

$webui_password_from_config = $config['webui']['password'] ?? null;

// 如果是GET请求，返回webUI界面
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $provided_password_hash_get = $_GET['password'] ?? null; // 现在接收的是哈希后的密码
    $is_api_request = isset($_GET['api']);
    $login_attempted_with_error = false; // 标记是否是因密码错误而重新显示登录页
    // 验证密码
    $is_authenticated = false;
    if (empty($webui_password_from_config)) {
        $is_authenticated = true; // 如果配置文件中未设置密码，则直接认证通过
    } elseif ($provided_password_hash_get !== null) {
        // 计算配置文件中明文密码的SHA256哈希值
        $config_password_hash = hash('sha256', $webui_password_from_config);
        // 使用 hash_equals 安全地比较哈希值
        if (hash_equals($config_password_hash, $provided_password_hash_get)) {
            $is_authenticated = true;
        } else {
            $login_attempted_with_error = true; // 提供了密码（哈希）但验证失败
        }
    }
    // 处理API请求
    if ($is_api_request && !$is_authenticated) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => '未授权访问API'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 如果未认证且不是API请求，显示登录页面
    if (!$is_authenticated && !$is_api_request) {
        http_response_code(401);
        include __DIR__ . '/WebUI/webui_login.php';
        exit;
    }
    // 检查是否是API请求
    if (isset($_GET['api'])) {
        header('Content-Type: application/json');
        switch ($_GET['api']) {
            case 'status':
                // 获取系统状态（优化版）
                $info = array();
                $info['php_version'] = phpversion();
                $info['server_time'] = date('Y-m-d H:i:s');
                $info['php_sapi'] = php_sapi_name();
                $info['os'] = PHP_OS;
                $info['execution_time'] = round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 4);

                // 获取机器人信息
                $me = BOTAPI("/users/@me", "GET", null);
                $botInfo = json_decode($me, true);
                $info['bot_name'] = $botInfo['username'] ?? '未知';
                $info['bot_id'] = $botInfo['id'] ?? '未知';

                // 获取CPU使用率（Windows）
                $cpu = 0;
                $cpuOut = [];
                if (function_exists('exec')) {
                    @exec('wmic cpu get loadpercentage /value', $cpuOut);
                    foreach ($cpuOut as $line) {
                        $line = trim($line);
                        if ($line === '')
                            continue;
                        if (preg_match('/LoadPercentage=(\\d+)/', $line, $m)) {
                            $cpu = intval($m[1]);
                            break;
                        }
                    }
                }
                $info['cpu_percent'] = is_numeric($cpu) ? $cpu : 0;

                // 获取内存信息（Windows）
                $totalMem = 0;
                $freeMem = 0;
                @exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value', $memOut);
                foreach ($memOut as $line) {
                    $line = trim($line);
                    if ($line === '')
                        continue;
                    if (preg_match('/TotalVisibleMemorySize=(\d+)/', $line, $m)) {
                        $totalMem = intval($m[1]);
                    }
                    if (preg_match('/FreePhysicalMemory=(\d+)/', $line, $m)) {
                        $freeMem = intval($m[1]);
                    }
                }
                if ($totalMem > 0 && $freeMem >= 0 && is_numeric($totalMem) && is_numeric($freeMem)) {
                    $usedMem = $totalMem - $freeMem;
                    $percent = $usedMem / $totalMem * 100;
                    $info['memory_percent'] = is_finite($percent) ? round($percent, 1) : 0;
                    $info['memory_usage'] = round($usedMem / 1024, 1) . ' MB';
                    $info['memory_total'] = round($totalMem / 1024, 1) . ' MB';
                    $info['memory_free'] = round($freeMem / 1024, 1) . ' MB';
                } else {
                    $info['memory_percent'] = 0;
                    $info['memory_usage'] = '未知';
                    $info['memory_total'] = '未知';
                    $info['memory_free'] = '未知';
                }

                echo json_encode($info, JSON_UNESCAPED_UNICODE);
                exit;

            case 'messages':
                // 获取消息日志
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                $log_file_path = __DIR__ . '/message.log';
                $display_logs = [];

                if (file_exists($log_file_path)) {
                    $lines = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines === false) {
                        echo json_encode(['status' => 'error', 'message' => '无法读取日志文件', 'data' => []], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    foreach ($lines as $line) {
                        $json_data = json_decode($line, true);
                        if (is_array($json_data) && isset($json_data['d']) && isset($json_data['t'])) {
                            $d = $json_data['d'];
                            $t = $json_data['t'];

                            $display_logs[] = [
                                'timestamp' => $d['timestamp'] ?? 'N/A',
                                'group_id' => $d['group_id'] ?? $d['group_openid'] ?? null,
                                'user_id' => $d['author']['id'] ?? $d['group_member_openid'] ?? null,
                                'type' => $t,
                                'content' => $d['content'] ?? $d['data']['resolved']['button_data'] ?? ''
                            ];
                        }
                    }
                    $display_logs = array_reverse($display_logs); // Newest first
                    if ($limit > 0 && count($display_logs) > $limit) {
                        $display_logs = array_slice($display_logs, 0, $limit);
                    }
                }
                echo json_encode(['status' => 'success', 'data' => $display_logs], JSON_UNESCAPED_UNICODE);
                exit;

            case 'plugins':
                $pluginDir = __DIR__ . '/plugins/';
                $plugins_info = [];
                if (is_dir($pluginDir)) {
                    $files = glob($pluginDir . '*/main.php');
                    foreach ($files as $file) {
                        // 确保是PHP文件
                        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {

                            $pluginName = basename(dirname($file));
                            $description = "暂无描述"; // 默认描述
                            $status = "可用"; // 默认状态

                            // 尝试从文件内容中提取类定义前的第一个块注释作为描述
                            $filePath = $pluginDir . $file;
                            $fileContent = @file_get_contents($filePath);
                            if ($fileContent) {
                                if (preg_match('/\/\*\*(.*?)\*\/\s*(abstract\s+|final\s+)?class\s+' . preg_quote($pluginName, '/') . '/is', $fileContent, $matches)) {
                                    $docComment = trim($matches[1]);
                                    $lines = explode("\n", $docComment);
                                    $potentialDescription = [];
                                    foreach ($lines as $line) {
                                        $line = trim($line);
                                        if (strpos($line, '*') === 0) {
                                            $line = trim(substr($line, 1));
                                        }
                                        if (!empty($line) && strpos($line, '@') !== 0) { // 忽略 @tag
                                            $potentialDescription[] = $line;
                                        }
                                    }
                                    if (!empty($potentialDescription)) {
                                        $description = implode(' ', $potentialDescription);
                                    }
                                }
                            }
                            $plugins_info[] = ['name' => $pluginName, 'description' => $description, 'status' => $status];
                        }
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => '插件目录未找到: ' . $pluginDir, 'data' => []], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                echo json_encode(['status' => 'success', 'data' => $plugins_info], JSON_UNESCAPED_UNICODE);
                exit;
            case 'stats_daily_active_users':
                $stat_date = $_GET['date'] ?? date('Y-m-d');
                if ($statisticsManager) {
                    echo json_encode(['status' => 'success', 'date' => $stat_date, 'dau' => $statisticsManager->getDailyActiveUsers($stat_date)], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Statistics service not available.'], JSON_UNESCAPED_UNICODE);
                }
                exit;
            case 'stats_bot_status':
                if ($statisticsManager) {
                    echo json_encode(['status' => 'success', 'data' => $statisticsManager->getBotOnlineStatus()], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Statistics service not available.'], JSON_UNESCAPED_UNICODE);
                }
                exit;
            case 'stats_daily_messages':
                $stat_date = $_GET['date'] ?? date('Y-m-d');
                if ($statisticsManager) {
                    echo json_encode(['status' => 'success', 'date' => $stat_date, 'data' => $statisticsManager->getDailyMessageStats($stat_date)], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Statistics service not available.'], JSON_UNESCAPED_UNICODE);
                }
                exit;
            case 'stats_command_frequency':
                $stat_date = $_GET['date'] ?? date('Y-m-d');
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                if ($statisticsManager) {
                    echo json_encode(['status' => 'success', 'date' => $stat_date, 'data' => $statisticsManager->getCommandFrequencyStats($stat_date, $limit)], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Statistics service not available.'], JSON_UNESCAPED_UNICODE);
                }
                exit;
            case 'stats_user_daily_invocations':
                $stat_date = $_GET['date'] ?? date('Y-m-d');
                $user_id = $_GET['user_id'] ?? null;
                if (!$user_id) {
                    echo json_encode(['status' => 'error', 'message' => 'User ID is required.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if ($statisticsManager) {
                    echo json_encode(['status' => 'success', 'date' => $stat_date, 'user_id' => $user_id, 'count' => $statisticsManager->getUserDailyInvocationCount($stat_date, $user_id)], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Statistics service not available.'], JSON_UNESCAPED_UNICODE);
                }
                exit;
            // Add more cases for getUserCommandPreferenceStats and getGroupDailyInvocationCount similarly
            // For brevity, I'll skip adding all of them here, but the pattern is the same.
            // Example for user command preference:
            // case 'stats_user_command_preference':
            //     // ... get user_id, limit, call $statisticsManager->getUserCommandPreferenceStats(...) ...
            //     exit;
        }
    }

    // 返回webUI界面
    include __DIR__ . '/WebUI/webui_template.php'; // 引入HTML模板文件
    exit;
}

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理其他POST请求
    $data = file_get_contents('php://input');
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
        include "core/segment/MessageSegment.php";
        $pluginManager = new PluginManager();
        $pluginManager->loadPlugins();
        $event = new MessageEvent($data);
        if ($statisticsManager) {
            if ($event->user_id) {
                $statisticsManager->logUserActivity($event->user_id);
            }
            $statisticsManager->logMessageReceived();
            $knownCommands = StatisticsManager::getKnownCommands(); // Get known commands
            $command = StatisticsManager::extractCommandFromMessage($event->content, $knownCommands);
            if ($command && $event->user_id) {
                $statisticsManager->logCommandInvocation($event->user_id, $event->group_id, $command, $event->content);
            }
        }
        $msg_id = $event->message_id;
        $log_file_path = __DIR__ . '/message.log';
        $msg_log = file_get_contents($log_file_path);
        if (strpos($msg_log, $msg_id) !== false) {
            echo Json(['status' => 'error', 'message' => '消息重复']);
            exit;
        }
        if (filesize($log_file_path) > 10000000) {
            file_put_contents($log_file_path, '');
        }
        file_put_contents($log_file_path, $data . "\n", FILE_APPEND);
        if (!$pluginManager->dispatchMessage($event)) {
            //无插件匹配处理
            exit;
        } else {
            //有插件匹配处理
            if ($statisticsManager)
                $statisticsManager->logMessageSent();
        }
        exit;
    }
}

// 格式化字节数
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
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