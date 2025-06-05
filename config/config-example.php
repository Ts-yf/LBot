<?php

return [
    'bot' => [
        'appid' => '',
        'secret' => '',
    ],
    'webui' => [
        'password' => '', // 设置 Web UI 访问密码
    ],
    // 图片上传配置
    'image_upload' => [
        'qqbot' => [
            'channel' => '', // 子频道ID，用于QQbot图床
        ],
        'qqshare' => [
            'p_uin' => '', // QQ号，用于QQShare图床
            'p_skey' => '', // connect.qq.com的p_skey，用于QQShare图床
        ],
        'bilibili' => [
            'csrf_token' => '', // bili_jct，用于B站图床
            'sessdata' => '', // SESSDATA，用于B站图床
        ],
    ],
    'ffmpeg_path' => 'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
    'silk_v3_encoder' => 'C:\\Program Files\\ffmpeg\\bin\\silk_v3_encoder.exe',
    'database' => [
        // 'host' => '127.0.0.1:3306', //注释此行表示不使用数据库
        'dbname' => '', //数据库名
        'user' => '', //数据库用户名
        'password' => '', //数据库密码
        'driver' => 'mysql', //可选mysql，sqlite，etc
    ],
];
