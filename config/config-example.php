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
];
