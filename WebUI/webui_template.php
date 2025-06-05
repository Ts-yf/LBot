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
            --primary-color: #2563eb;
            --secondary-color: #10b981;
            --success-color: #34d399;
            --info-color: #60a5fa;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 10px;
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Noto Sans SC', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            min-height: 100vh;
        }

        .top-bar {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            height: 60px;
            background: white;
            z-index: 999;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .top-bar {
                left: 0;
            }
        }

        .page-title {
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            margin-bottom: 24px;
            overflow: hidden;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 16px 20px;
            font-weight: 500;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i {
            font-size: 1.2rem;
        }

        .card-body {
            padding: 20px;
            background: white;
        }

        .system-card {
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .system-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: transparent;
        }

        .system-card .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .system-title {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .system-title i {
            margin-right: 8px;
            font-size: 1.2rem;
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
            background-color: rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            border-radius: 4px;
        }

        .progress-bar-cpu {
            background: var(--success-color);
            box-shadow: 0 0 10px var(--success-color);
        }

        .progress-bar-memory {
            background: var(--info-color);
            box-shadow: 0 0 10px var(--info-color);
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

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav .nav-item {
            margin: 0;
        }

        .sidebar-nav .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-nav .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .sidebar-nav .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border-left-color: var(--success-color);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 80px 20px 20px;
            min-height: 100vh;
            background-color: #f5f7fa;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
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

        .dashboard-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card .card-body {
            padding: 1.5rem;
        }

        .dashboard-card .bi {
            opacity: 0.8;
        }

        .dashboard-card:hover .bi {
            opacity: 1;
        }

        .dashboard-card h3 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .connection-status-badge .badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
        }
    </style>
</head>

<body class="login-page">
    <!-- 主界面 -->
    <div class="top-bar" style="display: none;">
        <div class="d-flex justify-content-end align-items-center px-4 py-2">
            <div class="me-4">
                <span class="connection-status disconnected" id="connection-indicator"></span>
                <span class="text-dark" id="connection-text">未连接</span>
            </div>
            <span class="text-dark"><i class="bi bi-clock"></i> <span id="current-time"></span></span>
        </div>
    </div>

    <!-- 侧边栏 -->
    <div class="sidebar" style="display: none;">
        <div class="sidebar-header">
            <h5 class="mb-0">LBot管理后台</h5>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard">
                    <i class="bi bi-speedometer2"></i> 仪表盘
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#msglog">
                    <i class="bi bi-chat-dots"></i> 消息日志
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#plugins">
                    <i class="bi bi-puzzle"></i> 插件列表
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#status">
                    <i class="bi bi-gear"></i> 系统状态
                </a>
            </li>
        </ul>
    </div>

    <!-- 主要内容区域 -->
    <div class="main-content" style="display: none;">
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

        <!-- 仪表盘 -->
        <div class="content-section" id="dashboard">
            <div class="row mb-4">
                <!-- 系统信息卡片已经在上面定义，这里不需要重复 -->
            </div>

            <!-- 统计信息卡片 -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-chat-dots fs-2 me-2 text-primary"></i>
                                <h5 class="card-title mb-0">消息统计</h5>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1" id="total-messages">0</h3>
                                    <small class="text-muted">今日消息总数</small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="loadMessages()">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-puzzle fs-2 me-2 text-success"></i>
                                <h5 class="card-title mb-0">插件状态</h5>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1" id="active-plugins">0</h3>
                                    <small class="text-muted">已启用插件数</small>
                                </div>
                                <button class="btn btn-sm btn-outline-success" onclick="loadPlugins()">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-clock-history fs-2 me-2 text-info"></i>
                                <h5 class="card-title mb-0">运行状态</h5>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1" id="uptime">0分钟</h3>
                                    <small class="text-muted">已运行时间</small>
                                </div>
                                <div class="connection-status-badge" id="connection-status-badge">
                                    <span class="badge bg-success">在线</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 快速操作区域 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">快速操作</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <button class="btn btn-light w-100 text-start" onclick="loadMessages()">
                                <i class="bi bi-chat-text me-2"></i>刷新消息日志
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-light w-100 text-start" onclick="loadPlugins()">
                                <i class="bi bi-plugin me-2"></i>刷新插件列表
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-light w-100 text-start" onclick="updateSystemStatus()">
                                <i class="bi bi-arrow-clockwise me-2"></i>刷新系统状态
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 消息日志 -->
        <div class="content-section" id="msglog" style="display: none;">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">消息日志</h5>
                    <button class="btn-control" id="refresh-msglog">
                        <i class="bi bi-arrow-clockwise"></i> 刷新
                    </button>
                </div>
                <div class="card-body">
                    <div class="logs-container" id="msglog-container">
                        <div class="empty-logs">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            暂无消息
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 插件列表 -->
        <div class="content-section" id="plugins" style="display: none;">
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
        <div class="content-section" id="status" style="display: none;">
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

        // 更新消息统计
        function updateMessageStats() {
            let apiUrl = '?api=messages';
            if (webUiPasswordFromUrl) { apiUrl += '&password=' + encodeURIComponent(webUiPasswordFromUrl); }
            $.get(apiUrl, function (response) {
                if (response.data) {
                    const todayMessages = response.data.filter(msg => {
                        const msgDate = new Date(msg.timestamp * 1000).toLocaleDateString();
                        const today = new Date().toLocaleDateString();
                        return msgDate === today;
                    }).length;
                    $('#total-messages').text(todayMessages);
                }
            });
        }

        // 更新插件统计
        function updatePluginStats() {
            let apiUrl = '?api=plugins';
            if (webUiPasswordFromUrl) { apiUrl += '&password=' + encodeURIComponent(webUiPasswordFromUrl); }
            $.get(apiUrl, function (response) {
                if (response.status === 'success' && response.data) {
                    const activePlugins = response.data.filter(plugin => plugin.status === 'enabled').length;
                    $('#active-plugins').text(activePlugins);
                }
            });
        }

        // 更新运行时间
        function updateUptime() {
            const startTime = new Date().getTime() - (Math.floor(Math.random() * 3600) + 1) * 1000; // 示例：随机1-3600秒
            setInterval(() => {
                const now = new Date().getTime();
                const diff = now - startTime;
                const minutes = Math.floor(diff / (1000 * 60));
                const hours = Math.floor(minutes / 60);
                let uptimeText = '';
                if (hours > 0) {
                    uptimeText = `${hours}小时${minutes % 60}分钟`;
                } else {
                    uptimeText = `${minutes}分钟`;
                }
                $('#uptime').text(uptimeText);
            }, 60000); // 每分钟更新一次
        }

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
            $('.top-bar').show();         // 显示顶部栏
            $('.sidebar').show();         // 显示侧边栏
            $('.main-content').show();    // 显示主要内容区域

            // 移动端侧边栏切换
            if (window.innerWidth <= 768) {
                const toggleBtn = $('<button class="btn btn-primary position-fixed" style="top: 10px; left: 10px; z-index: 1001;"><i class="bi bi-list"></i></button>');
                $('body').append(toggleBtn);
                toggleBtn.click(function () {
                    $('.sidebar').toggleClass('show');
                });
            }

            // 初始化功能
            updateTime();
            setInterval(updateTime, 1000);
            updateSystemStatus(); // 这些函数已正确使用 webUiPasswordFromUrl 进行API调用
            setInterval(updateSystemStatus, 5000);
            loadMessages();
            loadPlugins(); // 加载插件列表

            // 初始化仪表盘功能
            updateMessageStats();
            updatePluginStats();
            updateUptime();
            setInterval(updateMessageStats, 60000); // 每分钟更新一次消息统计
            setInterval(updatePluginStats, 60000); // 每分钟更新一次插件统计

            // 绑定事件
            $('#refresh-msglog').click(loadMessages);
            // 如果需要刷新插件列表的按钮，可以像这样添加：
            // $('#refresh-plugins-btn').click(loadPlugins);

            // 侧边栏导航点击事件
            $('.nav-link').click(function (e) {
                e.preventDefault();
                // 移除所有导航项的激活状态
                $('.nav-link').removeClass('active');
                // 添加当前点击项的激活状态
                $(this).addClass('active');

                // 隐藏所有内容区域
                $('.content-section').hide();
                // 显示对应的内容区域
                const targetId = $(this).attr('href');
                $(targetId).show();

                // 在移动端时，点击导航项后自动收起侧边栏
                if (window.innerWidth <= 768) {
                    $('.sidebar').removeClass('show');
                }
            });

            // 默认显示仪表盘
            $('.nav-link[href="#dashboard"]').click();

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