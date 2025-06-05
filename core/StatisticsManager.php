<?php
class StatisticsManager
{
    private PDO $pdo;
    private string $bot_appid;

    public function __construct(PDO $pdo, string $bot_appid)
    {
        $this->pdo = $pdo;
        $this->bot_appid = $bot_appid;
    }

    // Call this method once during setup to create necessary tables
    public function initializeSchema(): void
    {
        $commands = [
            "CREATE TABLE IF NOT EXISTS bot_daily_activity (
                id INT AUTO_INCREMENT PRIMARY KEY,
                activity_date DATE NOT NULL,
                user_id VARCHAR(255) NOT NULL,
                bot_appid VARCHAR(255) NOT NULL,
                UNIQUE KEY unique_activity (activity_date, user_id, bot_appid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS bot_message_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                stat_date DATE NOT NULL,
                bot_appid VARCHAR(255) NOT NULL,
                received_count INT DEFAULT 0,
                sent_count INT DEFAULT 0,
                UNIQUE KEY unique_stats (stat_date, bot_appid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS bot_command_invocations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invocation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                bot_appid VARCHAR(255) NOT NULL,
                user_id VARCHAR(255) NOT NULL,
                group_id VARCHAR(255) NULL,
                command VARCHAR(255) NOT NULL,
                full_content TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        ];

        foreach ($commands as $command) {
            $this->pdo->exec($command);
        }
    }

    public function logUserActivity(string $userId): void
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO bot_daily_activity (activity_date, user_id, bot_appid) VALUES (?, ?, ?)");
        $stmt->execute([$today, $userId, $this->bot_appid]);
    }

    public function logMessageReceived(): void
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("
            INSERT INTO bot_message_stats (stat_date, bot_appid, received_count)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE received_count = received_count + 1
        ");
        $stmt->execute([$today, $this->bot_appid]);
    }

    public function logMessageSent(): void
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("
            INSERT INTO bot_message_stats (stat_date, bot_appid, sent_count)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE sent_count = sent_count + 1
        ");
        $stmt->execute([$today, $this->bot_appid]);
    }

    public function logCommandInvocation(string $userId, ?string $groupId, string $command, string $fullContent): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO bot_command_invocations (bot_appid, user_id, group_id, command, full_content)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$this->bot_appid, $userId, $groupId, $command, $fullContent]);
    }

    /**
     * 机器人日活跃用户数量统计
     */
    public function getDailyActiveUsers(string $date): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT user_id) as dau FROM bot_daily_activity WHERE activity_date = ? AND bot_appid = ?");
        $stmt->execute([$date, $this->bot_appid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['dau'] : 0;
    }

    /**
     * 机器人在线情况统计 (Returns bot info from API, implies online if successful)
     */
    public function getBotOnlineStatus(): array
    {
        // This function relies on the BOTAPI function being available globally or passed.
        // For simplicity, assuming BOTAPI is accessible as in xxxx.php
        if (function_exists('BOTAPI')) {
            $me = BOTAPI("/users/@me", "GET", null);
            $botInfo = json_decode($me, true);
            if ($botInfo && isset($botInfo['id'])) {
                return ['status' => 'online', 'bot_name' => $botInfo['username'] ?? '未知', 'bot_id' => $botInfo['id']];
            }
        }
        return ['status' => 'offline', 'message' => 'Could not retrieve bot status'];
    }

    /**
     * 机器人每日收发统计
     */
    public function getDailyMessageStats(string $date): array
    {
        $stmt = $this->pdo->prepare("SELECT received_count, sent_count FROM bot_message_stats WHERE stat_date = ? AND bot_appid = ?");
        $stmt->execute([$date, $this->bot_appid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? [
            'received' => (int)$result['received_count'],
            'sent' => (int)$result['sent_count']
        ] : ['received' => 0, 'sent' => 0];
    }

    /**
     * 机器人指令频次统计
     */
    public function getCommandFrequencyStats(string $date, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT command, COUNT(*) as count
            FROM bot_command_invocations
            WHERE DATE(invocation_time) = ? AND bot_appid = ?
            GROUP BY command
            ORDER BY count DESC
            LIMIT ?
        ");
        $stmt->execute([$date, $this->bot_appid, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 用户每日调用次数统计
     */
    public function getUserDailyInvocationCount(string $date, string $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM bot_command_invocations
            WHERE DATE(invocation_time) = ? AND user_id = ? AND bot_appid = ?
        ");
        $stmt->execute([$date, $userId, $this->bot_appid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * 用户喜好指令分析统计
     */
    public function getUserCommandPreferenceStats(string $userId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT command, COUNT(*) as count
            FROM bot_command_invocations
            WHERE user_id = ? AND bot_appid = ?
            GROUP BY command
            ORDER BY count DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $this->bot_appid, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 群每日调用次数统计
     */
    public function getGroupDailyInvocationCount(string $date, string $groupId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM bot_command_invocations
            WHERE DATE(invocation_time) = ? AND group_id = ? AND bot_appid = ?
        ");
        $stmt->execute([$date, $groupId, $this->bot_appid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Helper function to extract command from message content.
     * This can be made more sophisticated.
     */
    public static function extractCommandFromMessage(string $content, array $predefinedKeywords = []): ?string
    {
        $trimmedContent = trim($content);

        // Check for predefined keywords first (more specific)
        foreach ($predefinedKeywords as $keyword) {
            if (strpos($trimmedContent, $keyword) === 0 || $trimmedContent === $keyword) { // Match if content starts with keyword or is exactly the keyword
                return $keyword;
            }
        }

        // Fallback: use the first word if it's a single word command or starts with a common prefix
        $parts = preg_split('/\s/', $trimmedContent);
        if (!empty($parts[0])) {
            $firstWord = $parts[0];
            // Example: if commands start with '/' or '!'
            // if (in_array(substr($firstWord, 0, 1), ['/', '!'])) {
            //    return $firstWord;
            // }
            // For now, if it's a single word, consider it a command.
            // Or if it's one of the "选择XX" transformed commands that might not be in predefinedKeywords
            // This part needs to align with how your bot identifies commands.
            // A simple approach: if it's the only word, or if it's a known transformed command.
            if (count($parts) == 1) {
                 return $firstWord;
            }
            // If it's a multi-word message, and no predefined keyword matched,
            // it's harder to generically determine the "command" without more rules.
            // For now, we'll return the first word if it's not a long sentence.
            if (mb_strlen($firstWord) < 20) { // Arbitrary length to avoid taking long strings as commands
                return $firstWord;
            }
        }

        return null; // Or return a default/unknown command string
    }

     /**
     * Get all known commands from the bot's logic for better extraction.
     * This should be populated based on your bot's command structure.
     */
    public static function getKnownCommands(): array
    {
        // These should be updated to reflect actual commands your bot uses,
        // especially after transformations like "选择XX" -> "展会指令"
        $transformedCommands = [
            '展会指令', '分配助理', '我的助理', '升级助理', '查看展台', '助理卡池', '一键升级助理',
            '解锁', '世界排行', '快速升级', '炫彩邀约', '黄金邀约', '普通邀约', '一键收取', '展会信息',
            '指令列表', '加载', '开启宝箱', '信息', '查看宝石', '公会', '购买', '献祭', '训练', '游戏',
            '商店', '任务', '成就', '保存', '移动', '技能', '攻击', '装备', '背包'
        ];

        $idleTycoonCommands = ['创建展会', '一键收取', '普通邀约', '黄金邀约', '炫彩邀约', '解锁', '世界排行', '展会信息', '展会指令', '助理卡池', '查看展台', '升级助理', '我的助理', '分配助理', '快速升级'];
        $minesweeperCommands = ['扫雷', '挖开', '标记', '点开', '查看雷区', '我的雷区'];

        return array_unique(array_merge($transformedCommands, $idleTycoonCommands, $minesweeperCommands));
    }
}

?>
