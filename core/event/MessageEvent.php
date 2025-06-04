<?php
class MessageEvent
{
    public $raw_data;
    public $event_type;
    public $message_id;
    public $content;
    public $sender_id;
    public $group_id;
    public $user_id;
    public $channel_id;
    public $guild_id;
    public $timestamp;
    public $matches;
    public $response;
    public $img = [];
    public $baseUrl;
    public function __construct($data)
    {
        $this->raw_data = $data;
        $this->event_type = $this->get('t'); // 事件类型，如GROUP_AT_MESSAGE_CREATE, INTERACTION_CREATE
        $this->message_id = ($this->event_type == 'INTERACTION_CREATE') ? $this->get('id') : $this->get('d/id');
        $this->content = $this->sanitize_content($this->get('d/content') ?? $this->get('d/data/resolved/button_data') ?? '');
        $this->sender_id = $this->get('d/author/id') ?? null;
        $this->timestamp = $this->get('d/timestamp');

        $this->group_id = $this->get('d/group_id') ?? $this->get('d/group_openid') ?? null;
        $this->user_id = $this->get('d/author/id') ?? $this->get('d/group_member_openid') ?? $this->sender_id;

        $this->channel_id = $this->get('d/channel_id') ?? null;
        $this->guild_id = $this->get('d/guild_id') ?? null;

        if ($this->get('d/attachments')) {
            $attachments = $this->get('d/attachments');
            foreach ($attachments as $attachment) {
                $this->img[] = $attachment['url'];
            }
        }
    }

    public function reply($content)
    {
        $msg = $this->makeMsg($content);
        $response = '';
        switch ($this->event_type) {
            case "GROUP_AT_MESSAGE_CREATE":
                $this->baseUrl = "/v2/groups/{$this->group_id}";
                $response = $this->sendMsg($msg);
                break;
            case "GROUP_ADD_ROBOT":
                $this->baseUrl = "/v2/groups/{$this->group_id}";
                $response = $this->sendMsg($msg);
                break;
            case "test":
                echo $content;
                $response = '{}';
                break;
            case "C2C_MESSAGE_CREATE":
                $this->baseUrl = "/v2/users/{$this->user_id}";
                $response = $this->sendMsg($msg);
                break;
            default:
                $response = '{}';
                break;
        }
        $this->response = $response;
        echo Json([
            // 'send' => json_decode($payload, true),
            'response' => $response
        ]);
        return $response;
    }
    private function makeMsg($data)
    {
        $messages = [];
        $normalizedData = [];
        if (is_array($data) && isset($data['type']) && !array_key_exists(0, $data)) {
            $normalizedData = [$data];
        } elseif (!is_array($data)) {
            $normalizedData = [['type' => 'text', 'text' => (string) $data]];
        } else {
            $normalizedData = $data;
        }

        $i = 0;
        while ($i < count($normalizedData)) {
            $currentSegment = $normalizedData[$i];
            $nextSegment = ($i + 1 < count($normalizedData)) ? $normalizedData[$i + 1] : null;
            $payload = [
                'msg_id' => $this->message_id,
                'msg_type' => 0, // 默认文本消息
                'content' => '',
                'msg_seq' => rand(10000, 999999)
            ];

            if (!is_array($currentSegment) || !isset($currentSegment['type'])) {
                $currentSegment = ['type' => 'text', 'text' => (string) $currentSegment];
            }

            switch ($currentSegment['type']) {
                case 'image':
                    $payload['msg_type'] = 7;
                    $payload['media'] = $this->getmedia($currentSegment['data'], $currentSegment['type']);
                    $messages[] = $payload;
                    $i++;
                    break;
                case 'text':
                    $payload['content'] = $currentSegment['text'] ?? '';

                    // Check for text + image combination
                    if ($nextSegment !== null && $nextSegment['type'] === 'image') {
                        // Combine text and the next image into one payload (msg_type 7 for image)
                        $payload['msg_type'] = 7;
                        $payload['media'] = $this->getmedia($nextSegment['data'], $nextSegment['type']);
                        // URLs in text content should still be converted if needed
                        if (preg_match_all('/https?:\/\/[\w\-]+(\.[\w\-]+)+[\w\-\.,@?^=%&:\/~\+#!]*[\w\-\@?^=%&\/~\+#]/', $payload['content'], $matches)) {
                            if (!empty($matches[0])) {
                                foreach ($matches[0] as $match_url) {
                                    $payload['content'] = str_replace($match_url, convertURL($match_url), $payload['content']);
                                }
                            }
                        }
                        $messages[] = $payload;
                        $i += 2; // Skip both the current text and the next image
                        continue; // Move to the next iteration
                    } else {
                        // Standard text message
                        if (preg_match_all('/https?:\/\/[\w\-]+(\.[\w\-]+)+[\w\-\.,@?^=%&:\/~\+#!]*[\w\-\@?^=%&\/~\+#]/', $payload['content'], $matches)) {
                            if (!empty($matches[0])) {
                                foreach ($matches[0] as $match_url) {
                                    $payload['content'] = str_replace($match_url, convertURL($match_url), $payload['content']);
                                }
                            }
                        }
                        $messages[] = $payload;
                        $i++;
                        continue; // Move to the next iteration
                    }
                case 'video':
                    $payload['msg_type'] = 7;
                    $payload['media'] = $this->getmedia($currentSegment['data'], $currentSegment['type']);
                    break;
                case 'ark':
                    $payload['msg_type'] = 3;
                    $payload['ark'] = $currentSegment['data'];
                    break;
                case 'reply':
                    if (isset($currentSegment['id'])) {
                        if (strpos($currentSegment['id'], 'event_') === 0) {
                            $payload['event_id'] = $currentSegment['id'];
                            if (isset($payload['msg_id']))
                                unset($payload['msg_id']);
                        } else {
                            $payload['msg_id'] = $currentSegment['id'];
                        }
                    }
                    break;
                case 'audio':
                    $payload['msg_type'] = 7;
                    $payload['media'] = $this->getmedia($currentSegment['data'], $currentSegment['type']);
                    break;
                case 'markdown':
                    $payload['msg_type'] = 2;
                    if (isset($currentSegment['data']) && (is_array($currentSegment['data']) || is_object($currentSegment['data']))) {
                        $payload['markdown'] = $currentSegment['data'];
                    } else {
                        $payload['markdown']['content'] = (string) ($currentSegment['data'] ?? '');
                    }
                    break;
                case 'keyboard':
                    $payload['msg_type'] = 2;
                    if (isset($currentSegment['data']['id'])) {
                        $payload['keyboard']['id'] = $currentSegment['data']['id'];
                    } elseif (isset($currentSegment['data'])) {
                        $payload['keyboard'] = $currentSegment['data'];
                    } else {
                        error_log("MessageEvent::makeMsg: Keyboard segment missing 'data'. Segment: " . json_encode($currentSegment));
                        continue 2; // 跳过此 segment
                    }
                    break;
                case 'button':
                    $payload['msg_type'] = 2;
                    if (isset($currentSegment['id'])) {
                        $payload['keyboard']['id'] = $currentSegment['id'];
                    } elseif (isset($currentSegment['data']['id'])) {
                        $payload['keyboard']['id'] = $currentSegment['data']['id'];
                    } elseif (isset($currentSegment['data'])) {
                        $payload['keyboard'] = $currentSegment['data'];
                    } else {
                        error_log("MessageEvent::makeMsg: Button segment is missing 'id' or 'data'. Segment: " . json_encode($currentSegment));
                        continue 2; // 跳过此 segment
                    }
                    break;
                case 'record':
                    $payload['msg_type'] = 7;
                    $payload['media'] = $this->getmedia($currentSegment['data'], 'audio');
                    break;
                case 'stream':
                    $streamData = $currentSegment['data'] ?? null;
                    if (!$streamData || !isset($streamData['stream'])) {
                        error_log("MessageEvent::makeMsg: Stream segment missing 'data' or 'data.stream'. Segment: " . json_encode($currentSegment));
                        continue 2;
                    }

                    $payload['stream'] = $streamData['stream'];
                    if (!isset($payload['stream']['index'])) {
                        $payload['stream']['index'] = $stream_outer_index++;
                    }

                    $payload['msg_type'] = 0;

                    if (isset($streamData['type'])) {
                        switch ($streamData['type']) {
                            case 'text':
                                $payload['content'] = $streamData['text'] ?? '';
                                break;
                            case 'markdown':
                                $payload['msg_type'] = 2;
                                if (isset($streamData['data']) && (is_array($streamData['data']) || is_object($streamData['data']))) {
                                    $payload['markdown'] = $streamData['data'];
                                } else {
                                    $payload['markdown']['content'] = (string) ($streamData['data'] ?? '');
                                }
                                break;
                            case 'keyboard':
                            case 'button':
                                $payload['msg_type'] = 2;
                                if (isset($streamData['id']) && $streamData['type'] === 'button') {
                                    $payload['keyboard']['id'] = $streamData['id'];
                                } elseif (isset($streamData['data']['id'])) {
                                    $payload['keyboard']['id'] = $streamData['data']['id'];
                                } elseif (isset($streamData['data'])) {
                                    $payload['keyboard'] = $streamData['data'];
                                } else {
                                    error_log("MessageEvent::makeMsg: Stream button/keyboard missing 'id' or 'data'. Stream data: " . json_encode($streamData));
                                    continue 3;
                                }
                                break;
                            default:
                                error_log("MessageEvent::makeMsg: Unknown type within stream segment: " . $streamData['type']);
                                break;
                        }
                    }
                    break;
                case 'raw':
                    if (is_array($currentSegment['data'])) {
                        $payload = $currentSegment['data'];
                        $payload['msg_id'] = $payload['msg_id'] ?? $this->message_id;
                        $payload['msg_seq'] = $payload['msg_seq'] ?? rand(10000, 999999);
                        $messages[] = $payload;
                        $i = count($normalizedData);
                    } else {
                        error_log("MessageEvent::makeMsg: Raw segment 'data' is not an array. Segment: " . json_encode($currentSegment));
                        continue 2;
                    }
                    break;
                default:
                    error_log("MessageEvent::makeMsg: Unknown message segment type: " . $currentSegment['type'] . ". Segment: " . json_encode($currentSegment));
                    continue 2;
            }
            $messages[] = $payload;
            $i++;
        }
        return $messages;
    }
    private function getmedia($data, $type)
    {
        $file_type_map = ['image' => 1, 'video' => 2, 'audio' => 3]; // 假设的映射关系，具体值需查API
        $api_file_type = $file_type_map[strtolower($type)] ?? 0; // 默认为0或其他表示未知的值
        $finalBase64Data = '';

        if (strtolower($type) === 'audio') {
            // 步骤 1: 获取原始音频二进制数据
            $rawAudioData = null;
            if (substr($data, 0, strlen('http')) === 'http') { // 网络 URL
                $rawAudioData = @file_get_contents($data);
            } elseif (is_string($data) && substr($data, 0, strlen('base64://')) === 'base64://') { // Base64 字符串
                $rawAudioData = base64_decode(str_replace('base64://', '', $data));
            } elseif (is_string($data) && !preg_match('/[^\x20-\x7E]/', $data) && @is_readable($data)) { // 本地文件路径
                $rawAudioData = @file_get_contents($data);
            } elseif (is_string($data) && preg_match('/[^\x20-\x7E]/', $data)) { // 可能已经是二进制数据字符串
                $rawAudioData = $data;
            } elseif (is_string($data)) { // 尝试作为普通 base64 解码
                $decoded = base64_decode($data, true);
                if ($decoded !== false && preg_match('/[^\x20-\x7E\r\n\t]/', $decoded)) { // 解码成功且包含非可见字符，认为是二进制
                    $rawAudioData = $decoded;
                } else {
                    error_log("MessageEvent::getmedia: Audio data is a string but not recognized as URL, base64 scheme, readable file, or raw binary. Data: " . substr($data, 0, 100));
                }
            }

            if ($rawAudioData) {
                // 步骤 2: 转换为 Silk 格式
                $silkAudioData = $this->convertToSilk($rawAudioData);

                if ($silkAudioData) {
                    $finalBase64Data = base64_encode($silkAudioData);
                    // 注意: QQ API 可能对 Silk 音频有特定的 ptt_format 要求 (例如 2)
                    // 如果 API 需要，你可能要在这里或 $body 中添加类似 'ptt_format' => 2 的参数
                } else {
                    error_log("MessageEvent::getmedia: Failed to convert audio to silk. Attempting to send original audio data.");
                    // Silk 转换失败，回退到发送原始音频数据 (Base64编码后)
                    $finalBase64Data = base64_encode($rawAudioData);
                }
            } else {
                error_log("MessageEvent::getmedia: Could not obtain raw audio data for type 'audio'. Source: " . (is_string($data) ? substr($data, 0, 100) . "..." : gettype($data)));
            }
        } else {
            // 对于图片、视频等其他类型，使用全局的 getFileBase64 函数
            $finalBase64Data = getFileBase64($data);
        }

        if (empty($finalBase64Data)) {
            error_log("MessageEvent::getmedia: Final base64 data is empty. Type: {$type}. Cannot proceed with API call.");
            return ['file_info' => null];
        }

        $body = [
            'file_type' => $api_file_type, // 使用映射后的文件类型
            'file_data' => $finalBase64Data, // 确保这里是 Base64 编码后的数据
            'srv_send_msg' => false
        ];

        if (empty($this->baseUrl) && ($this->group_id || $this->user_id)) {
            // 尝试根据 group_id 或 user_id 推断 baseUrl (这只是一个后备方案)
            if ($this->group_id) {
                 $this->baseUrl = "/v2/groups/{$this->group_id}";
            } elseif ($this->user_id) {
                 $this->baseUrl = "/v2/users/{$this->user_id}";
            }
        }
        if (empty($this->baseUrl)) {
            error_log("MessageEvent::getmedia: baseUrl is not set.");
            return ['file_info' => null]; // 或者抛出异常
        }

        $result = BOTAPI("{$this->baseUrl}/files", "POST", $body); // 假设上传文件的端点是 /files
        $responseData = json_decode($result, true);

        // 检查API响应是否成功以及是否包含 file_info
        if (isset($responseData['file_info'])) {
            return ['file_info' => $responseData['file_info']];
        } else {
            error_log("MessageEvent::getmedia: Failed to upload media or missing file_info. API Response: " . $result);
            return ['file_info' => null]; // 返回一个表示失败的结构
        }
    }

    /**
     * 将原始音频数据转换为 Silk v3 格式 (需要外部编码器工具)
     *
     * @param string $rawAudioData 原始音频的二进制数据
     * @return string|false Silk 格式的音频二进制数据，或者在失败时返回 false
     */
    private function convertToSilk($rawAudioData)
    {
        // 【重要配置】设置你的 ffmpeg 和 Silk 编码器路径
        global $config;
        $ffmpegPath = $config['ffmpeg_path'] ?? 'ffmpeg'; // 从配置读取 ffmpeg 路径
        $silkEncoderPath = $config['silk_v3_encoder'] ?? 'silk_v3_encoder'; // 从配置读取 silk 编码器路径 (注意 config.php 中是 silk_v3_decoder, 将在 config 中修正)

        // 检查必要的工具是否存在 (可选，但推荐)
        // if (empty(shell_exec("command -v " . escapeshellarg($ffmpegPath)))) { error_log("convertToSilk: ffmpeg not found at " . $ffmpegPath); return false; }
        // if (empty(shell_exec("command -v " . escapeshellarg($silkEncoderPath)))) { error_log("convertToSilk: silk_v3_encoder not found at " . $silkEncoderPath); return false; }

        // 创建临时输入文件
        $inputFile = tempnam(sys_get_temp_dir(), 'audio_in_');
        if ($inputFile === false || file_put_contents($inputFile, $rawAudioData) === false) {
            error_log("convertToSilk: Failed to create or write temporary input file.");
            if ($inputFile && file_exists($inputFile)) @unlink($inputFile);
            return false;
        }

        // 创建临时 PCM 文件路径
        $pcmFile = $inputFile . '.pcm'; // 在同目录下创建 .pcm 文件

        // 创建最终 Silk 输出文件路径
        $silkOutputFile = $inputFile . '.silk'; // 在同目录下创建 .silk 文件

        // 步骤 1: 使用 ffmpeg 转换为 PCM
        // ffmpeg -i "${convFile}" -f s16le -ar 48000 -ac 1 "${convFile}.pcm"
        $ffmpegCommand = sprintf("%s -i %s -f s16le -ar 48000 -ac 1 %s",
            escapeshellarg($ffmpegPath), // <--- 修正：使用 escapeshellarg 确保路径被正确引用
            escapeshellarg($inputFile),
            escapeshellarg($pcmFile)
        );

        exec($ffmpegCommand . " >> ffmpeg.log 2>&1");

        if (!file_exists($pcmFile) || filesize($pcmFile) === 0) {
            error_log("convertToSilk: ffmpeg conversion to PCM failed or PCM file is empty. Command: " . $ffmpegCommand);
            @unlink($inputFile);
            return false;
        }

        // 步骤 2: 使用 silk_v3_encoder 转换为 Silk
        // silk_v3_encoder "${convFile}.pcm" "${convFile}.silk" -Fs_API 48000 -rate 48000 -tencent
        $silkCommand = sprintf("%s %s %s -Fs_API 48000 -rate 48000 -tencent",
            escapeshellarg($silkEncoderPath),
            escapeshellarg($pcmFile),
            escapeshellarg($silkOutputFile)
        );

        exec($silkCommand . " >> silk_v3_encoder.log 2>&1");

        $silkData = false;
        if (file_exists($silkOutputFile) && filesize($silkOutputFile) > 0) {
            $silkData = file_get_contents($silkOutputFile);
        } else {
            error_log("convertToSilk: silk_v3_encoder conversion failed or Silk file is empty/not created. Command: " . $silkCommand);
        }

        // 清理临时文件
        if (file_exists($inputFile)) {
            @unlink($inputFile);
        }
        if (file_exists($pcmFile)) {
            @unlink($pcmFile);
        }
        if (file_exists($silkOutputFile)) {
            @unlink($silkOutputFile);
        }

        if ($silkData === false) {
             error_log("convertToSilk: Failed to obtain silk data. Original audio data size: " . strlen($rawAudioData) . " bytes.");
        }

        return $silkData;
    }

    private function sendMsg($messages)
    {
        $response = [];
        // 确保 baseUrl 在调用 BOTAPI 前是有效的
        if (empty($this->baseUrl) && ($this->group_id || $this->user_id)) {
             if ($this->group_id) {
                 $this->baseUrl = "/v2/groups/{$this->group_id}";
            } elseif ($this->user_id) {
                 $this->baseUrl = "/v2/users/{$this->user_id}";
            }
        }
        if (empty($this->baseUrl)) {
            error_log("MessageEvent::sendMsg: baseUrl is not set. Cannot send messages.");
            return []; // 返回空响应数组的JSON表示
        }

        foreach ($messages as $i) {
            $response[] = BOTAPI("{$this->baseUrl}/messages", "POST", Json($i));
        }
        return $response;
    }
    public function get($path)
    {
        // 兼容raw_data为数组或json字符串
        if (is_array($this->raw_data)) {
            $data = $this->raw_data;
        } else {
            $data = json_decode($this->raw_data, true);
        }
        $keys = explode('/', $path);
        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return null;
            }
        }
        return $data;
    }

    private function sanitize_content($content)
    {
        return rtrim(ltrim(ltrim($content, "/")));
    }

    // 文字转图片，返回图片二进制数据
    public function generateTextImage($text)
    {
        // 删除emoji
        $text = $this->removeEmoji($text);
        // 配置参数
        $fontFile = __DIR__ . '/data/ttf/msyh.ttf';
        if (!file_exists($fontFile)) {
            throw new Exception('字体文件缺失');
        }
        $bgWidth = 600;
        $bgHeight = 450;
        $panelWidth = 500;
        $panelHeight = 300;
        $alpha = 70;
        $hh = 15;
        $hhf = "\n";
        $size = 20;
        $textMargin = 20;
        // 分行
        $lines = $this->splitTextToLines($text, $hh, $hhf);
        $testBox = imagettfbbox($size, 0, $fontFile, "中");
        $lineHeight = abs($testBox[5] - $testBox[1]) * 1.2;
        $numLines = count($lines);
        if ($numLines == 0 && trim($text) === '') {
            $lines[] = " ";
            $numLines = 1;
        } else if ($numLines == 0 && !empty(trim($text))) {
            $lines[] = trim($text);
            $numLines = 1;
        }
        if ($numLines == 0) {
            $lines[] = " ";
            $numLines = 1;
        }
        $textBlockHeight = $numLines * $lineHeight;
        $calculatedPanelHeight = $textBlockHeight + (2 * $textMargin);
        $panelHeight = max($panelHeight, $calculatedPanelHeight);
        $panelY = 80;
        $canvasHeight = max($panelY + $panelHeight + $textMargin, $bgHeight);
        $canvasWidth = $bgWidth;
        // 1. 尝试加载本地背景图片
        $srcImage = null;
        $localImages = glob(__DIR__ . '/data/tw/*.{jpg,png}', GLOB_BRACE);
        if (!empty($localImages)) {
            $srcImage = @imagecreatefromstring(file_get_contents($localImages[array_rand($localImages)]));
        }
        // 2. 创建主画布
        $im = imagecreatetruecolor($canvasWidth, $canvasHeight);
        $bgColorIm = imagecolorallocate($im, 230, 230, 230);
        imagefill($im, 0, 0, $bgColorIm);
        // 3. 如果有背景图片，等比缩放填充整个画布
        if ($srcImage) {
            $srcWidth = imagesx($srcImage);
            $srcHeight = imagesy($srcImage);
            // 计算缩放比例，保证图片能覆盖整个画布
            $scale = max($canvasWidth / $srcWidth, $canvasHeight / $srcHeight);
            $newWidth = (int) ($srcWidth * $scale);
            $newHeight = (int) ($srcHeight * $scale);
            // 先缩放到大于画布
            $tmpImg = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($tmpImg, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
            // 再居中裁剪到画布
            $srcX = (int) (($newWidth - $canvasWidth) / 2);
            $srcY = (int) (($newHeight - $canvasHeight) / 2);
            imagecopy($im, $tmpImg, 0, 0, $srcX, $srcY, $canvasWidth, $canvasHeight);
            imagedestroy($srcImage);
            imagedestroy($tmpImg);
        }
        // 透明面板
        $panelX = ($canvasWidth - $panelWidth) / 2;
        $transColor = imagecolorallocatealpha($im, 255, 255, 255, $alpha);
        imagefilledrectangle($im, (int) $panelX, (int) $panelY, (int) ($panelX + $panelWidth), (int) ($panelY + $panelHeight), $transColor);
        // 文字
        $textColor = imagecolorallocate($im, 0, 0, 0);
        $currentY = $panelY + $textMargin + $size;
        foreach ($lines as $lineContent) {
            $trimmedLine = trim($lineContent);
            if ($lineContent === ' ' && empty($trimmedLine)) {
                // 占位空行
            } else {
                $box = imagettfbbox($size, 0, $fontFile, $lineContent);
                $textLineWidth = $box[2] - $box[0];
                $actualTextX = $panelX + ($panelWidth - $textLineWidth) / 2;
                imagettftext($im, $size, 0, (int) $actualTextX, (int) $currentY, $textColor, $fontFile, $lineContent);
            }
            $currentY += $lineHeight;
        }
        ob_start();
        imagepng($im);
        $imageData = ob_get_clean();
        imagedestroy($im);
        return $imageData;
    }

    // 删除emoji字符
    private function removeEmoji($text)
    {
        // 匹配大部分emoji的正则
        return preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}\x{1F1E6}-\x{1F1FF}]/u', '', $text);
    }

    // 智能分行
    private function splitTextToLines($text, $maxCharsPerLine, $hhf)
    {
        $lines = [];
        $sections = explode($hhf, $text);
        foreach ($sections as $section) {
            $section = trim($section);
            if (empty($section)) {
                $lines[] = '';
                continue;
            }
            $currentLine = '';
            $charCount = 0;
            $len = mb_strlen($section);
            for ($i = 0; $i < $len; $i++) {
                $char = mb_substr($section, $i, 1);
                $currentLine .= $char;
                $charCount++;
                $forceBreak = ($charCount >= $maxCharsPerLine);
                $punctuationBreak = ($this->isPunctuation($char) && $charCount > 1 && $i < $len - 1);
                if ($forceBreak || $punctuationBreak) {
                    if ($this->isPunctuation($char) && ($i + 1 < $len) && $this->isPunctuation(mb_substr($section, $i + 1, 1))) {
                        // 连续标点
                    } else {
                        $lines[] = $currentLine;
                        $currentLine = '';
                        $charCount = 0;
                    }
                }
            }
            if (!empty($currentLine)) {
                $lines[] = $currentLine;
            }
        }
        if (empty($lines) && trim($text) === '') {
            $lines[] = ' ';
        } else if (empty($lines) && !empty(trim($text))) {
            $lines[] = trim($text);
        }
        return $lines;
    }

    // 判断是否为中文标点
    private function isPunctuation($char)
    {
        $punctuations = ['，', '。', '！', '？', '；', '、', '"', '"', "'", "'", '(', ')', '[', ']', '-'];
        return in_array($char, $punctuations);
    }

    // 通用上传图片到图床，type=qqshare或qqbot
    public function uploadToQQImageBed($imageData, $type = 'qqbot')
    {
        if ($type === 'qqbot' || $type === 'official') { // 添加 official 作为 qqbot 的别名
            return $this->uploadToQQBotImageBed($imageData);
        } elseif ($type === 'qqshare') {
            return $this->uploadToQQShareImageBed($imageData);
        } else {
            return $this->upload_to_bilibili($imageData);
        }
    }

    // QQ机器人官方图床方式
    private function uploadToQQBotImageBed($imageData)
    {
        global $appid; // 从 bot.php 引入的全局变量
        global $secret; // 从 bot.php 引入的全局变量
        global $config;
        $channel = $config['image_upload']['qqbot']['channel'] ?? '';
        $md5Hash = strtoupper(md5($imageData));
        // 获取access_token
        $url = 'https://bots.qq.com/app/getAppAccessToken';
        $data = array('appId' => $appid, 'clientSecret' => $secret);
        $jsonData = json_encode($data);
        $ch2 = curl_init($url);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            )
        ]);
        $response = curl_exec($ch2);
        curl_close($ch2);
        $res = json_decode($response, true);
        $access_token = $res['access_token'] ?? '';
        if (!$access_token)
            return '';
        // 临时文件
        $tempFile = tmpfile();
        fwrite($tempFile, $imageData);
        rewind($tempFile);
        $metaData = stream_get_meta_data($tempFile);
        $tempFilePath = $metaData['uri'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        $filename = 'image.' . explode('/', $mimeType)[1];
        $cfile = new \CURLFile($tempFilePath, $mimeType, $filename);
        $postFields = [
            'msg_id' => '0',
            'file_image' => $cfile
        ];
        $uploadCh = curl_init();
        curl_setopt_array($uploadCh, [
            CURLOPT_URL => 'https://api.sgroup.qq.com/channels/' . $channel . '/messages',
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: multipart/form-data',
                'Authorization: QQBot ' . $access_token
            ]
        ]);
        curl_exec($uploadCh);
        curl_close($uploadCh);
        fclose($tempFile);
        return 'https://gchat.qpic.cn/qmeetpic/0/0-0-' . $md5Hash . '/0';
    }

    // QQShare方式
    private function uploadToQQShareImageBed($imageData)
    {
        global $config;
        $p_uin = $config['image_upload']['qqshare']['p_uin'] ?? '';
        $p_skey = $config['image_upload']['qqshare']['p_skey'] ?? '';

        if (empty($p_uin) || empty($p_skey)) {
            error_log("MessageEvent::uploadToQQShareImageBed: QQShare config (p_uin or p_skey) is missing.");
            return '';
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'qqshare_');
        file_put_contents($tmpFile, $imageData);
        $filename = 'upload_' . time() . '.png';
        $cfile = new \CURLFile($tmpFile, 'image/png', $filename);
        $postFields = [
            'share_image' => $cfile
        ];
        $cookie = "p_uin={$p_uin};p_skey={$p_skey}";
        $ch = curl_init('https://cgi.connect.qq.com/qqconnectopen/upload_share_image');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: android_34_OP5D06L1_14_9.1.5',
                'Referer: http://www.qq.com',
                'Host: cgi.connect.qq.com',
                'Accept-Encoding: gzip',
                'Connection: keep-alive'
            ],
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '', // 自动解压gzip/deflate
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        unlink($tmpFile);
        // 自动检测并转为UTF-8
        $encoding = mb_detect_encoding($response, ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $response = mb_convert_encoding($response, 'UTF-8', $encoding);
        }
        // 去除BOM和首尾空白
        $response = trim($response);
        if (substr($response, 0, 3) === "\xEF\xBB\xBF") {
            $response = substr($response, 3);
        }
        $res = json_decode($response, true);
        if (is_array($res) && isset($res['retcode']) && $res['retcode'] == 0 && isset($res['result']['url'])) {
            return $res['result']['url'];
        }
        return '';
    }
    /**
     * 上传图片到B站图床
     *
     * @param string $imageData 二进制图像数据
     * @return string 成功时返回图片URL，失败时返回空字符串
     */
    private static function upload_to_bilibili($imageData)
    {
        global $config;
        $csrfToken = $config['image_upload']['bilibili']['csrf_token'] ?? '';
        $sessData = $config['image_upload']['bilibili']['sessdata'] ?? '';

        if (empty($csrfToken) || empty($sessData)) { // 检查配置是否存在
            // 可以选择记录日志 error_log('Bilibili CSRF Token 或 SESSDATA 未配置');
            return '';
        }

        if (empty($imageData)) {
            // 可以选择记录日志 error_log('Bilibili 上传：图像数据为空');
            return '';
        }

        $tempFilePath = tempnam(sys_get_temp_dir(), 'bili_up_');
        if ($tempFilePath === false || file_put_contents($tempFilePath, $imageData) === false) {
            if ($tempFilePath && file_exists($tempFilePath))
                @unlink($tempFilePath);
            // error_log('Bilibili 上传：创建或写入临时文件失败');
            return '';
        }

        // 确定MIME类型和文件扩展名
        $fileMimeType = '';
        $fileExt = '';
        $validImageMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        $mimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/bmp' => 'bmp'];

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_buffer($finfo, $imageData); // finfo_buffer 不需要 FILEINFO_MIME_TYPE 作为第三个参数
            finfo_close($finfo);
            if ($detectedMime && in_array($detectedMime, $validImageMimeTypes)) {
                $fileMimeType = $detectedMime;
                $fileExt = $mimeToExt[$fileMimeType];
            }
        }

        if (empty($fileExt)) { // 如果 finfo 失败或不可用，尝试基本检查
            if (substr($imageData, 0, 2) === "\xFF\xD8")
                $fileExt = 'jpg';
            elseif (substr($imageData, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A")
                $fileExt = 'png';
            elseif (substr($imageData, 0, 6) === "GIF87a" || substr($imageData, 0, 6) === "GIF89a")
                $fileExt = 'gif';
            elseif (substr($imageData, 0, 4) === "RIFF" && substr($imageData, 8, 4) === "WEBP")
                $fileExt = 'webp';
            elseif (substr($imageData, 0, 2) === "BM")
                $fileExt = 'bmp';
            else
                $fileExt = 'png'; // 默认扩展名
        }

        if (empty($fileMimeType)) {
            $fileExt = 'png'; // 默认扩展名
            $fileMimeType = array_search($fileExt, $mimeToExt) ?: 'image/png'; // 默认MIME
            if ($fileMimeType === 'image/png' && $fileExt === 'jpg')
                $fileMimeType = 'image/jpeg'; // 修正可能的默认MIME
        }

        $generatedFileName = 'upload_' . time() . '.' . $fileExt;

        $cFile = new CURLFile($tempFilePath, $fileMimeType, $generatedFileName);
        $postData = [
            'file' => $cFile,
            'bucket' => 'openplatform',
            'csrf' => $csrfToken
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.bilibili.com/x/upload/web/image');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Cookie: SESSDATA=' . $sessData . '; bili_jct=' . $csrfToken,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }

        if ($curlError) {
            // error_log("Bilibili 上传 cURL 错误: " . $curlError);
            return '';
        }

        $responseData = json_decode($response, true);

        if ($httpCode == 200 && isset($responseData['code']) && $responseData['code'] === 0 && isset($responseData['data']['location'])) {
            return str_replace('http://', 'https://', $responseData['data']['location']);
        } else {
            return '';
        }
    }
}
function getFileBase64($file)
{
    // 如果是二进制数据（类似于 Uint8Array/Buffer）
    if (is_string($file) && preg_match('/[^\x20-\x7E]/', $file)) {
        return base64_encode($file);
    }
    // 如果已经是 Base64 字符串
    if (substr($file, 0, strlen('base64://')) === 'base64://') {
        return str_replace('base64://', '', $file);
    }
    // 如果是 HTTP/HTTPS URL
    if (substr($file, 0, strlen('http')) === 'http') {
        return getBase64FromWeb($file);
    }
    // 尝试从本地文件读取
    try {
        return getBase64FromLocal($file);
    } catch (Exception $e) {
        // 忽略错误，继续执行
    }
    // 默认返回原数据
    return $file;
}

// 从网络获取 Base64
function getBase64FromWeb($url)
{
    $content = file_get_contents($url);
    return base64_encode($content);
}

// 从本地文件获取 Base64
function getBase64FromLocal($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception("File not found: $filePath");
    }
    $content = file_get_contents($filePath);
    return base64_encode($content);
}