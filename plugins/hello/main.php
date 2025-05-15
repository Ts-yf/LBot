<?php
// plugins/hello/main.php
// 示例插件：收到“你好”时自动回复“你好，世界！”

class hello_Plugin implements Plugin {
    // 注册正则规则与处理函数
    public static function getRegexHandlers() {
        // 匹配“你好”
        return [
            '/^你好$/' => 'onHello',
        ];
    }

    // 处理函数
    public static function onHello($event) {
        $event->reply('你好，世界！');
    }
} 