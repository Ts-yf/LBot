<?php
class Segment {
    // 创建文本消息段
    public static function text(string $text): array {
        return ['type' => 'text', 'text' => $text];
    }

    // 创建图片消息段
    public static function image(string $file, ?string $cache = null): array {
        $data = ['type' => 'image', 'file' => $file];
        if ($cache !== null) {
            $data['cache'] = $cache;
        }
        return $data;
    }

    // 创建At消息段（扩展功能）
    public static function at(string $userId): array {
        return ['type' => 'at', 'user_id' => $userId];
    }

    // 通用消息段构造方法
    public static function raw(string $type, array $data = []): array {
        return array_merge(['type' => $type], $data);
    }
}
