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
        ?>
        <!DOCTYPE html>
        <html lang="zh-CN">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>登录 - LBot管理后台</title>
            <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    background-color: #f5f7fa;
                    font-family: 'Noto Sans SC', sans-serif;
                }

                .login-container {
                    width: 100%;
                    max-width: 400px;
                    padding: 15px;
                }

                .card {
                    border: none;
                    border-radius: 10px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                .card-header {
                    background: linear-gradient(135deg, #4361ee, #3f37c9);
                    color: white;
                    border-radius: 10px 10px 0 0 !important;
                    padding: 20px;
                }

                .card-title {
                    margin: 0;
                    font-weight: 600;
                }

                .form-control:focus {
                    box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
                    border-color: #4361ee;
                }

                .btn-primary {
                    background: #4361ee;
                    border: none;
                    padding: 10px 0;
                    font-weight: 500;
                }

                .btn-primary:hover {
                    background: #3f37c9;
                }
            </style>
        </head>

        <body>
            <div class="login-container">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title text-center">LBot管理后台</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($login_attempted_with_error): ?>
                            <div class="alert alert-danger">访问密码错误，请重试</div>
                        <?php endif; ?>
                        <form id="login-form"> <!-- 移除 method 和 action, 由 JS 处理 -->
                            <div class="mb-3">
                                <label for="password_input" class="form-label">访问密码</label>
                                <input type="password" class="form-control" id="password_input" name="password" required
                                    autofocus>
                                <div class="invalid-feedback" style="display: none;">请输入密码。</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">登录</button>
                        </form>
                    </div>
                </div>
            </div>
            <script>
                document.getElementById('login-form').addEventListener('submit', async function (e) { // 添加 async
                    e.preventDefault(); // 阻止表单默认提交
                    const passwordInput = document.getElementById('password_input');
                    const plainPassword = passwordInput.value;
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const invalidFeedback = this.querySelector('.invalid-feedback');
                    if (submitBtn.disabled) {
                        return;
                    }
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 登录中...';
                    passwordInput.classList.remove('is-invalid');
                    invalidFeedback.style.display = 'none';

                    if (!plainPassword) {
                        passwordInput.classList.add('is-invalid');
                        invalidFeedback.style.display = 'block';
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '登录';
                        return;
                    }

                    // 使用 SubtleCrypto API 进行 SHA-256 哈希
                    async function calculateSHA256(message) {
                        const msgBuffer = new TextEncoder().encode(message); // 将字符串编码为 UTF-8
                        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer); // 哈希处理
                        const hashArray = Array.from(new Uint8Array(hashBuffer)); // 转换为字节数组
                        const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join(''); // 转换为十六进制字符串
                        return hashHex;
                    }

                    const hashedPassword = await calculateSHA256(plainPassword);

                    // 构建新的 URL 并跳转
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('password', hashedPassword); // 使用 'password' 作为参数名传递哈希值
                    window.location.href = currentUrl.toString();
                });
            </script>
        </body>

        </html>
        <?php
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
        }
    }

    // 返回webUI界面
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>LBot管理后台</title>
        <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary-color: #4361ee;
                --secondary-color: #3f37c9;
                --success-color: #4cc9f0;
                --info-color: #4895ef;
                --warning-color: #f72585;
                --danger-color: #e63946;
                --light-color: #f8f9fa;
                --dark-color: #212529;
                --border-radius: 10px;
            }

            body {
                font-family: 'Noto Sans SC', sans-serif;
                background-color: #f5f7fa;
                color: #333;
                min-height: 100vh;
            }

            .page-header {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                color: white;
                padding: 20px 0;
                margin-bottom: 30px;
                border-radius: 0 0 var(--border-radius) var(--border-radius);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .page-title {
                font-weight: 700;
                letter-spacing: 0.5px;
                margin: 0;
            }

            .card {
                border: none;
                border-radius: var(--border-radius);
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
                transition: transform 0.3s, box-shadow 0.3s;
                margin-bottom: 24px;
                overflow: hidden;
            }

            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            }

            .card-header {
                background-color: white;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                padding: 16px 20px;
                font-weight: 500;
            }

            .card-body {
                padding: 20px;
            }

            .system-card {
                height: 100%;
            }

            .system-card .card-body {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .system-title {
                font-size: 14px;
                color: #6c757d;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
            }

            .system-title i {
                margin-right: 8px;
            }

            .system-value {
                font-size: 24px;
                font-weight: 700;
                margin-bottom: 10px;
            }

            .progress {
                height: 8px;
                border-radius: 4px;
                margin-bottom: 6px;
                background-color: #e9ecef;
            }

            .progress-bar {
                border-radius: 4px;
            }

            .progress-bar-cpu {
                background: linear-gradient(to right, #4cc9f0, #4361ee);
            }

            .progress-bar-memory {
                background: linear-gradient(to right, #4895ef, #3f37c9);
            }

            .logs-container {
                height: 450px;
                overflow-y: auto;
                background-color: #fff;
                border: 1px solid #e9ecef;
                border-radius: var(--border-radius);
                padding: 0;
            }

            .log-entry {
                padding: 8px 16px;
                border-bottom: 1px solid #f1f1f1;
                font-size: 14px;
                line-height: 1.5;
                transition: background-color 0.2s;
            }

            .log-entry:hover {
                background-color: #f8f9fa;
            }

            .log-entry:last-child {
                border-bottom: none;
            }

            .timestamp {
                color: #6c757d;
                margin-right: 10px;
                font-weight: 500;
                font-size: 12px;
            }

            .message-content {
                word-break: break-word;
            }

            .nav-tabs {
                border-bottom: none;
                margin-bottom: 20px;
            }

            .nav-tabs .nav-link {
                color: #6c757d;
                border: none;
                border-radius: var(--border-radius);
                padding: 10px 16px;
                margin-right: 10px;
                font-weight: 500;
                transition: all 0.2s;
            }

            .nav-tabs .nav-link:hover {
                color: var(--primary-color);
                background-color: rgba(67, 97, 238, 0.05);
            }

            .nav-tabs .nav-link.active {
                color: white;
                background-color: var(--primary-color);
                font-weight: 500;
            }

            .tab-content {
                background-color: white;
                border-radius: var(--border-radius);
                padding: 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }

            .connection-status {
                display: inline-block;
                width: 12px;
                height: 12px;
                border-radius: 50%;
                margin-right: 6px;
            }

            .connection-status.connected {
                background-color: #4cd137;
                box-shadow: 0 0 8px #4cd137;
            }

            .connection-status.disconnected {
                background-color: #e84118;
                box-shadow: 0 0 8px #e84118;
            }

            .btn-control {
                padding: 6px 12px;
                background-color: var(--light-color);
                color: var(--dark-color);
                border-radius: var(--border-radius);
                border: none;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }

            .btn-control:hover {
                background-color: var(--primary-color);
                color: white;
            }

            .empty-logs {
                padding: 30px;
                text-align: center;
                color: #6c757d;
            }
        </style>
    </head>

    <body class="login-page">
        <!-- 主界面 -->
        <div class="page-header" style="display: none;">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="page-title">LBot管理后台</h1>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="connection-status disconnected" id="connection-indicator"></span>
                            <span class="text-light" id="connection-text">未连接</span>
                        </div>
                        <span class="text-light"><i class="bi bi-clock"></i> <span id="current-time"></span></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-5" style="display: none;">
            <!-- 系统信息卡片 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card system-card">
                        <div class="card-body">
                            <div class="system-title">
                                <i class="bi bi-cpu"></i> CPU 使用率
                            </div>
                            <div class="system-value" id="cpu-text">0%</div>
                            <div class="progress">
                                <div id="cpu-progress" class="progress-bar progress-bar-cpu" role="progressbar"
                                    style="width: 0%"></div>
                            </div>
                            <small class="text-muted">处理器负载</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card system-card">
                        <div class="card-body">
                            <div class="system-title">
                                <i class="bi bi-memory"></i> 内存使用率
                            </div>
                            <div class="system-value" id="memory-text">0%</div>
                            <div class="progress">
                                <div id="memory-progress" class="progress-bar progress-bar-memory" role="progressbar"
                                    style="width: 0%"></div>
                            </div>
                            <small class="text-muted">内存占用情况</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card system-card">
                        <div class="card-body">
                            <div class="system-title">
                                <i class="bi bi-hdd-rack"></i> 物理内存
                            </div>
                            <div class="system-value" id="rss-memory">0 MB</div>
                            <small class="text-muted">实际占用的物理内存</small>
                        </div>
                    </div>
                </div>
                <?php if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') { ?>
                    <div class="col-md-3">
                        <div class="card system-card">
                            <div class="card-body">
                                <div class="system-title">
                                    <i class="bi bi-hdd-network"></i> 虚拟内存
                                </div>
                                <div class="system-value" id="vms-memory">0 MB</div>
                                <small class="text-muted">虚拟内存使用量</small>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="col-md-3">
                        <div class="card system-card">
                            <div class="card-body">
                                <div class="system-title">
                                    <i class="bi bi-hdd-network"></i> 空闲内存
                                </div>
                                <div class="system-value" id="free-memory">0 MB</div>
                                <small class="text-muted">可用物理内存</small>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- 标签页 -->
            <ul class="nav nav-tabs" id="logTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="msglog-tab" data-bs-toggle="tab" href="#msglog" role="tab">
                        <i class="bi bi-chat-dots"></i> 消息日志
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="plugins-tab" data-bs-toggle="tab" href="#plugins" role="tab">
                        <i class="bi bi-puzzle"></i> 插件列表
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="status-tab" data-bs-toggle="tab" href="#status" role="tab">
                        <i class="bi bi-gear"></i> 系统状态
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- 消息日志 -->
                <div class="tab-pane fade show active" id="msglog" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">消息日志</h5>
                        <button class="btn-control" id="refresh-msglog">
                            <i class="bi bi-arrow-clockwise"></i> 刷新
                        </button>
                    </div>
                    <div class="logs-container" id="msglog-container">
                        <div class="empty-logs">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            暂无消息
                        </div>
                    </div>
                </div>

                <!-- 插件列表 -->
                <div class="tab-pane fade" id="plugins" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">插件列表</h5>
                        </div>
                        <div class="card-body">
                            <div id="plugins-container">
                                <!-- 插件列表将在这里动态加载 -->
                                <p class="text-center text-muted">加载中...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 系统状态 -->
                <div class="tab-pane fade" id="status" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">系统状态</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>机器人信息</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>机器人名称</td>
                                            <td id="bot-name">加载中...</td>
                                        </tr>
                                        <tr>
                                            <td>机器人ID</td>
                                            <td id="bot-id">加载中...</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>系统信息</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>PHP版本</td>
                                            <td id="php-version">加载中...</td>
                                        </tr>
                                        <tr>
                                            <td>内存使用</td>
                                            <td id="memory-usage">加载中...</td>
                                        </tr>
                                        <tr>
                                            <td>内存峰值</td>
                                            <td id="memory-peak">加载中...</td>
                                        </tr>
                                        <tr>
                                            <td>服务器时间</td>
                                            <td id="server-time">加载中...</td>
                                        </tr>
                                        <tr>
                                            <td>PHP运行模式</td>
                                            <td id="php-sapi">加载中...</td>
                                        </tr>
                                        <tr>
                                            <td>操作系统</td>
                                            <td id="os">加载中...</td>
                                        </tr>
                                        <tr>
                                            <td>执行时间</td>
                                            <td id="execution-time">加载中...</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="py-3 mt-auto">
            <div class="container">
                <div class="text-center text-muted">
                    <small>LBot管理后台 &copy; <span id="current-year"></span></small>
                </div>
            </div>
        </footer>

        <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        <script>
            // 更新系统时间
            function updateTime() {
                const now = new Date();
                document.getElementById('current-time').textContent = now.toLocaleString('zh-CN');
                document.getElementById('current-year').textContent = now.getFullYear();
            }

            const urlParams = new URLSearchParams(window.location.search);
            const webUiPasswordFromUrl = urlParams.get('password');

            // 更新系统状态
            function updateSystemStatus() {
                let apiUrl = '?api=status';
                if (webUiPasswordFromUrl) { apiUrl += '&password=' + encodeURIComponent(webUiPasswordFromUrl); }
                $.get(apiUrl, function (data) {
                    // 更新机器人信息
                    $('#bot-name').text(data.bot_name);
                    $('#bot-id').text(data.bot_id);

                    // 更新系统信息
                    $('#php-version').text(data.php_version);
                    $('#memory-usage').text(data.memory_usage);
                    $('#memory-peak').text(data.memory_peak);
                    $('#server-time').text(data.server_time);
                    $('#php-sapi').text(data.php_sapi);
                    $('#os').text(data.os);
                    $('#execution-time').text(data.execution_time + ' 秒');

                    // 更新内存使用率
                    let memPercent = Number(data.memory_percent);
                    if (isNaN(memPercent)) memPercent = 0;
                    $('#memory-text').text(memPercent.toFixed(1) + '%');
                    $('#memory-progress').css('width', memPercent + '%');

                    // 更新物理内存和虚拟内存/空闲内存
                    $('#rss-memory').text(data.memory_usage);
                    <?php if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') { ?>
                        $('#vms-memory').text(data.memory_peak || '0 MB');
                    <?php } else { ?>
                        $('#free-memory').text(data.memory_free);
                    <?php } ?>
                });
            }

            // 加载消息日志
            function loadMessages() {
                let apiUrl = '?api=messages';
                if (webUiPasswordFromUrl) { apiUrl += '&password=' + encodeURIComponent(webUiPasswordFromUrl); }
                $.get(apiUrl, function (response) {
                    const container = $('#msglog-container');
                    container.empty();
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(function (log) {
                            container.append(`
                                <div class=\"log-entry\">
                                    <span class=\"timestamp\">[${log.timestamp}]</span>
                                    <span class=\"text-muted me-2\">${log.group_id ? '群:' + log.group_id : ''}</span>
                                    <span class=\"text-muted me-2\">${log.user_id ? '用户:' + log.user_id : ''}</span>
                                    <span class=\"text-muted me-2\">${log.type ? '类型:' + log.type : ''}</span>
                                    <span class=\"message-content\">${log.content}</span>
                                </div>
                            `);
                        });
                    } else {
                        container.html(`
                            <div class=\"empty-logs\">
                                <i class=\"bi bi-inbox fs-1 d-block mb-2\"></i>
                                暂无消息
                            </div>
                        `);
                    }
                });
            }

            // 加载插件列表
            function loadPlugins() {
                let apiUrl = '?api=plugins';
                if (webUiPasswordFromUrl) { apiUrl += '&password=' + encodeURIComponent(webUiPasswordFromUrl); }
                const container = $('#plugins-container');
                container.html('<p class="text-center text-muted">加载中...</p>'); // 显示加载提示

                $.get(apiUrl, function (response) {
                    container.empty(); // 清空旧内容
                    if (response.status === 'success' && response.data && response.data.length > 0) {
                        const ul = $('<ul class="list-group"></ul>');
                        response.data.forEach(function (plugin) {
                            const li = $(`
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="my-0">${plugin.name}</h6>
                                        <small class="text-muted">${plugin.description || '暂无描述'}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">${plugin.status || '未知'}</span>
                                </li>
                            `);
                            ul.append(li);
                        });
                        container.append(ul);
                    } else if (response.status === 'success' && (!response.data || response.data.length === 0)) {
                        container.html(`<div class="empty-logs"><i class="bi bi-puzzle fs-1 d-block mb-2"></i>暂无插件</div>`);
                    } else {
                        container.html(`<div class="alert alert-warning">加载插件列表失败: ${response.message || '未知错误'}</div>`);
                    }
                }).fail(function () {
                    container.empty();
                    container.html(`<div class="alert alert-danger">请求插件列表API失败。</div>`);
                });
            }


            // 发送消息
            $('#send-message-form').on('submit', function (e) {
                e.preventDefault();
                const groupId = $('#group-id').val();
                const content = $('#message-content').val();

                $.post('', {
                    type: 'send_message',
                    group_id: groupId,
                    content: content
                }, function (response) {
                    if (response.status === 'success') {
                        $('#message-result').html(`
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> ${response.message}
                            </div>
                        `);
                        $('#message-content').val('');
                    } else {
                        $('#message-result').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle"></i> ${response.message}
                            </div>
                        `);
                    }
                });
            });

            // 初始化页面显示 (这个函数会在PHP认证通过后，主界面加载时被调用)
            function initializeUI() {
                // 如果此脚本正在运行，则表示PHP已验证用户或不需要密码。
                // 因此，应显示主UI，并隐藏嵌入式登录表单。
                $('.login-container').hide(); // 确保嵌入式登录表单已隐藏
                $('.page-header').show();     // 显示主页面头部
                $('.container.mb-5').show();  // 显示主页面内容区域

                // 初始化功能
                updateTime();
                setInterval(updateTime, 1000);
                updateSystemStatus(); // 这些函数已正确使用 webUiPasswordFromUrl 进行API调用
                setInterval(updateSystemStatus, 5000);
                loadMessages();
                loadPlugins(); // 加载插件列表

                // 绑定事件
                $('#refresh-msglog').click(loadMessages);
                // 如果需要刷新插件列表的按钮，可以像这样添加：
                // $('#refresh-plugins-btn').click(loadPlugins);

                // 更新连接状态
                $('#connection-indicator').removeClass('disconnected').addClass('connected');
                $('#connection-text').text('已连接');
            }

            // 页面加载完成后初始化
            $(document).ready(function () {
                initializeUI();
            });
        </script>
    </body>

    </html>
    <?php
    exit;
}

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理其他POST请求
    $data = file_get_contents('php://input');
    if (empty($data)) {
        $type = $_GET['type'];
        if (!empty($type)) {
            if ($type == 'test') {
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
                $event = new MessageEvent($data);
                if (!$pluginManager->dispatchMessage($event)) {
                    $event->reply("test");
                    exit;
                }
            }
            exit;
        }
        exit;
    }

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
        file_put_contents('message.log', $data . "\n", FILE_APPEND);
        if (!$pluginManager->dispatchMessage($event)) {
            exit;
        } else {
            if ($isIdleTycoonCommand) {
                $event->reply('');
            } else {
                $event->reply('');
            }
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