# LBot 灵Bot QQ机器人 🤖

✨ 一个基于PHP的QQ机器人Webhook框架，支持插件扩展和Web管理后台，轻松实现多种自定义功能。

## ✨ 功能特点

- 🔗 支持QQ开放平台Webhook接入
- 🧩 插件化架构，通过正则表达式匹配消息，便于功能扩展
- 🖥️ 内建Web管理后台：
    - 📊 系统状态实时监控 (CPU, 内存, 机器人信息等)
    - 📜 消息日志查看与刷新
    - 🔌 插件列表与基本信息展示
- 💬 丰富的消息构造能力 (文本, 图片, 回复, Ark, Markdown, 按钮等)，通过 `Segment` 类实现
- 🔐 WebUI访问密码保护 (密码在前端进行SHA256哈希后提交)
- 📝 自动记录接收到的消息到 `message.log`
- 🚀 简单易用，配置后快速上手

## 🖥️ 环境要求

- 🐘 **PHP 7.4 及以上版本**
- 📦 **必装PHP扩展**:
    - `curl` (用于API通讯)
    - `json` (通常默认启用)
    - `mbstring` (用于多字节字符串处理)
    - `gd` (用于 `MessageEvent::generateTextImage` 图片生成功能，如果使用到)
    - `fileinfo` (用于媒体文件类型识别，例如图片上传功能)
- ⚙️ **建议开启的PHP函数**:
    - `exec` (用于WebUI在Windows环境下获取更详细的系统状态如CPU/内存使用率，非必需)

## ⚙️ 安装与配置

1. ⬇️ 克隆或下载本项目到服务器目录
2. 📂 进入 `config/` 目录，创建 `config.php` 文件 (可以参考以下结构或从 `config.example.php` 复制)。
3. 📝 编辑 `config/config.php` 文件，填写配置信息：
   ```php
   <?php
   return [
       'bot' => [
           'appid' => '你的QQ机器人AppID',      // 必填
           'secret' => '你的QQ机器人密钥',     // 必填
       ],
       'webui' => [
           'password' => '设置一个Web后台访问密码', // 可选，如果留空或不设置此项，则后台无需密码即可访问
       ],
   ];
   ```
4. 🌐 在你的QQ开放平台后台，找到事件订阅（Webhook）配置，将**回调地址**设置为 `http(s)://你的域名或IP/bot.php`。
5. ✅ 确保服务器的 `bot.php` 文件可以通过公网被QQ开放平台访问，并检查Web服务器（如Nginx/Apache）配置是否正确，以及文件和目录的读写权限。
6. ✍️ 确保项目根目录对于PHP进程是可写的，因为 `message.log` 文件会自动在该目录下创建和追加。

## 🚀 快速开始与管理

1.  **启动与接收消息**:
    完成以上配置后，当QQ开放平台向你的回调地址推送事件时，机器人框架将自动处理。
2.  **访问Web管理后台**:
    -   在浏览器中打开 `http(s)://你的域名或IP/bot.php`。
    -   如果设置了 `webui.password`，页面会提示输入密码。
    -   后台提供系统状态监控、消息日志查看和已加载插件列表等功能。
## 🔌 插件开发

1.  **创建插件目录**: 在 `plugins/` 目录下为你的插件创建一个新目录，例如 `plugins/myplugin/`。
2.  **编写主文件**: 在你的插件目录中创建 `main.php` 文件。
3.  **实现插件类**:
    -   类名应遵循 `插件目录名_Plugin` 的格式 (例如，如果目录是 `myplugin`，类名就是 `myplugin_Plugin`)。
    -   插件类必须实现 `Plugin` 接口 (该接口定义在 `core/plugin/PluginManager.php` 中)。
    -   实现静态方法 `getRegexHandlers()`，此方法返回一个数组，键是用于匹配消息内容的正则表达式，值是处理该消息的**类中静态方法名**。

    ```php
    <?php
    // plugins/myplugin/main.php

    class myplugin_Plugin implements Plugin {
        // 注册正则规则与对应的处理函数
        public static function getRegexHandlers() {
            return [
                '/^你好$/' => 'onHello',         // 精确匹配 "你好"
                '/^查询\s*(.+)$/i' => 'onQuery', // 匹配 "查询 xxx" (不区分大小写)
            ];
        }

        // 处理函数：当消息匹配 "/^你好$/" 时调用
        public static function onHello(MessageEvent $event) {
            // $event 对象包含了消息的各种信息
            // $event->content 是原始消息文本
            // $event->sender_id 是发送者ID
            // $event->group_id 是群ID (如果是群消息)
            
            // 使用 Segment 类构建回复内容
            $replyMessage = Segment::text('你好呀！很高兴认识你。');
            $event->reply($replyMessage); 
            
            // 也可以直接回复字符串，框架会自动处理为文本消息
            // $event->reply('你好呀！');
        }

        // 处理函数：当消息匹配 "/^查询\s*(.+)$/i" 时调用
        public static function onQuery(MessageEvent $event) {
            // $event->matches 是正则表达式的匹配结果数组
            // $matches[0] 是完整匹配的字符串
            // $matches[1] 是第一个捕获组的内容 (即查询的关键词)
            $keyword = $event->matches[1];
            
            $responseText = "正在为你查询：" . $keyword;
            $image = Segment::image('https://example.com/path/to/your/image.png'); // 示例图片
            
            // 发送多段消息
            $event->reply([
                Segment::text($responseText),
                $image,
                Segment::text('查询结果仅供参考。')
            ]);
        }
    }
    ```
4.  **消息处理函数**:
    -   处理函数必须是 `public static`。
    -   函数接收一个 `MessageEvent` 对象 (`core/event/MessageEvent.php`) 作为参数，该对象封装了接收到的消息数据和一些便捷方法。
    -   使用 `$event->reply($message)` 方法进行回复。`$message` 参数可以是：
        -   一个字符串 (将作为纯文本消息发送)。
        -   一个由 `Segment` 类方法生成的单个消息段数组 (例如 `Segment::text('你好')`)。
        -   一个包含多个消息段数组的数组 (用于发送多段消息，例如 `[Segment::text('第一段'), Segment::image('url')]`)。
5.  **使用 `Segment` 类构造消息**:
    -   `Segment` 类 (定义于 `core/segment/MessageSegment.php` 文件中) 提供了一系列静态方法来创建不同类型的消息段，如：
        -   `Segment::text(string $text)`: 文本消息
        -   `Segment::image(string $data)`: 图片消息 (URL、路径或base64)
        -   `Segment::reply(string $id)`: 回复指定消息
        -   `Segment::ark(array $data)`: Ark 消息
        -   `Segment::markdown(string|array $data)`: Markdown 消息
        -   `Segment::button(string|array $idOrData)` / `Segment::keyboard(array $data)`: 按钮/键盘消息
        -   更多类型请查看 `Segment` 类源码。
6.  **自动加载**: 框架启动时，`PluginManager` 会自动扫描 `plugins/` 目录下所有符合 `*/main.php` 结构且正确实现了 `Plugin` 接口的插件，并注册它们定义的正则处理器。

## 📁 项目结构 (简要)

```
LBot/
├── bot.php             # Web入口文件 (Webhook处理, WebUI界面)
├── config/
│   └── config.php      # 配置文件 (需用户根据说明创建和配置)
├── core/
│   ├── event/
│   │   └── MessageEvent.php # 消息事件封装与处理类
│   ├── plugin/
│   │   └── PluginManager.php# 插件加载与调度管理器
│   └── segment/
│       └── MessageSegment.php # 消息段构造类 (名为 Segment)
├── function/
│   └── Access.php      # 包含 BOTAPI 等与QQ平台API交互的函数
├── plugins/            # 插件存放目录
│   └── hello/          # 示例插件 "hello"
│       └── main.php    # hello插件主实现文件
├── message.log         # 接收到的原始消息日志 (自动创建)
└── README.md           # 本文档
```

## ❓ 常见问题

- 🚫 **回调地址无法访问/验证失败？**
    -   请检查服务器防火墙设置，确保QQ开放平台的服务器IP可以访问你的回调URL。
    -   确认你的Web服务器（Nginx, Apache等）配置正确，能够将请求指向 `bot.php`。
    -   确保域名解析正确，或IP地址可公网访问。
- ❌ **消息发送后机器人无响应？**
    -   仔细检查 `config/config.php` 中的 `appid` 和 `secret` 是否从QQ开放平台正确复制。
    -   登录QQ开放平台，检查机器人是否在线，以及回调地址是否配置正确且处于启用状态。
    -   查看服务器的Web服务错误日志 (如 Nginx error.log, PHP-FPM error.log) 和PHP自身的错误日志，排查运行时错误。
    -   检查项目根目录下的 `message.log` 文件，确认是否有收到来自QQ平台的原始消息数据。如果日志为空或没有新消息，说明请求未到达框架。
- 🔑 **忘记WebUI后台密码？**
    -   如果设置了 `webui.password` 但忘记了，你需要直接编辑 `config/config.php` 文件，修改 `webui.password` 的值为新密码，或者暂时移除该行/将其值设为空字符串以取消密码验证。

## 💬 交流群

- QQ群：687976465

🎉 欢迎反馈建议与贡献插件！