<?php

/**
 * Class MessageSegment
 * 用于为 makeMsg 函数构造消息段的辅助类。
 */
class Segment
{
    /**
     * 创建一个文本消息段。
     * @param string $text 文本内容。
     * @return array 文本消息段。
     */
    public static function text(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    /**
     * 创建一个图片消息段。
     * @param string $data 图片数据 (URL、路径或 base64 字符串)。
     * @return array 图片消息段。
     */
    public static function image(string $data): array
    {
        return ['type' => 'image', 'data' => $data];
    }

    /**
     * 创建一个视频消息段。
     * @param string $data 视频数据 (URL、路径或 base64 字符串)。
     * @return array 视频消息段。
     */
    public static function video(string $data): array
    {
        return ['type' => 'video', 'data' => $data];
    }

    /**
     * 创建一个音频消息段。
     * @param string $data 音频数据 (URL、路径或 base64 字符串)。
     * @return array 音频消息段。
     */
    public static function audio(string $data): array
    {
        return ['type' => 'audio', 'data' => $data];
    }

    /**
     * 创建一个语音 (record) 消息段。是 audio 的别名。
     * @param string $data 语音数据 (URL、路径或 base64 字符串)。
     * @return array 语音消息段。
     */
    public static function record(string $data): array
    {
        return ['type' => 'record', 'data' => $data];
    }

    /**
     * 创建一个 Ark 消息段。
     * @param array $data Ark 数据结构。
     * @return array Ark 消息段。
     */
    public static function ark(array $data): array
    {
        return ['type' => 'ark', 'data' => $data];
    }

    /**
     * 创建一个回复消息段。
     * @param string $id 要回复的消息或事件的 ID。
     * @return array 回复消息段。
     */
    public static function reply(string $id): array
    {
        return ['type' => 'reply', 'id' => $id];
    }

    /**
     * 创建一个 Markdown 消息段。
     * @param string|array $data Markdown 内容字符串或数据结构。
     * @return array Markdown 消息段。
     */
    public static function markdown($data): array
    {
        return ['type' => 'markdown', 'data' => $data];
    }

    /**
     * 创建一个键盘消息段。
     * @param array $data 键盘数据 (例如：['id' => 'template_id'] 或完整的键盘结构)。
     * @return array 键盘消息段。
     */
    public static function keyboard(array $data): array
    {
        return self::button($data);
    }

    /**
     * 创建一个按钮消息段。
     * 这将被 makeMsg 函数解释为一个键盘。
     * @param string|array $idOrData 键盘模板 ID (字符串) 或键盘数据结构 (数组)。
     * @return array 按钮消息段。
     */
    public static function button($idOrData): array
    {
        if (is_string($idOrData)) {
            return ['type' => 'button', 'id' => $idOrData];
        }
        return ['type' => 'button', 'data' => $idOrData];
    }

    /**
     * 创建一个流式消息段。
     * @param array $streamInfo 包含流参数的关联数组 (例如：['state' => 1, 'id' => null, 'index' => 0, 'reset' => false])。
     * @param string $innerType 内部消息的类型 ('text', 'markdown', 'keyboard', 'button')。
     * @param mixed $innerData 内部消息的数据。
     * @return array 流式消息段。
     */
    public static function stream(array $streamInfo, string $innerType, $innerData): array
    {
        $streamDataPayload = [
            'stream' => $streamInfo,
            'type' => $innerType,
        ];

        switch ($innerType) {
            case 'text':
                $streamDataPayload['text'] = $innerData;
                break;
            case 'markdown':
            case 'keyboard':
                $streamDataPayload['data'] = $innerData;
                break;
            case 'button':
                if (is_string($innerData)) {
                    $streamDataPayload['id'] = $innerData; // 对于按钮类型，'id' 可以与 'type' 在同一层级
                } else {
                    $streamDataPayload['data'] = $innerData;
                }
                break;
            default:
                // 或者为不支持的内部类型抛出异常
                error_log("MessageSegment::stream: Unsupported inner type '{$innerType}' for stream segment.");
                break;
        }

        return ['type' => 'stream', 'data' => $streamDataPayload];
    }

    /**
     * 创建一个原始消息段，允许使用自定义的 payload 结构。
     * @param array $data 原始 payload 数据。
     * @return array 原始消息段。
     */
    public static function raw(array $data): array
    {
        return ['type' => 'raw', 'data' => $data];
    }
}