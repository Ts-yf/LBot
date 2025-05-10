<?php
class OpenIDConverter {
    private $dbPath;
    private $db;

    public function __construct($dbPath = '/data/id_map.json') {
        $this->dbPath = realpath(dirname($dbPath)) . '/' . basename($dbPath);
        $this->db = ['groups' => [], 'users' => []];
        $this->_loadDB();
    }

    private function _loadDB() {
        if (file_exists($this->dbPath)) {
            $this->db = json_decode(file_get_contents($this->dbPath), true);
        } else {
            $this->_saveDB();
        }
    }

    private function _saveDB() {
        if (!is_dir(dirname($this->dbPath))) {
            mkdir(dirname($this->dbPath), 0777, true);
        }
        file_put_contents($this->dbPath, json_encode($this->db, JSON_PRETTY_PRINT));
    }

    public function encode($openid, $type = 'user') {
        if (!in_array($type, ['group', 'user'])) {
            throw new Exception('类型必须是 group 或 user');
        }

        // 检查是否已存在
        $existingId = array_search($openid, $this->db[$type . 's']);
        if ($existingId !== false) return $existingId;

        // 生成数字ID（9位，首字符非0）
        $hash = substr(hash('sha256', $openid), 0, 9);
        $numericId = (hexdec($hash) % 900000000) + 100000000;
        $numericId = strval($numericId);

        // 冲突处理（最多重试3次）
        $retry = 0;
        while (isset($this->db[$type . 's'][$numericId]) && $retry < 3) {
            $hash = substr(hash('sha256', $openid), $retry, 9);
            $numericId = (hexdec($hash) % 900000000) + 100000000;
            $numericId = strval($numericId);
            $retry++;
        }

        if (isset($this->db[$type . 's'][$numericId])) {
            throw new Exception('无法生成唯一ID');
        }

        // 保存
        $this->db[$type . 's'][$numericId] = $openid;
        $this->_saveDB();

        return $numericId;
    }

    public function decode($numericId, $type = 'user') {
        if (!in_array($type, ['group', 'user'])) {
            throw new Exception('类型必须是 group 或 user');
        }
        if (!isset($this->db[$type . 's'][$numericId])) {
            throw new Exception('无效的ID或类型');
        }
        return $this->db[$type . 's'][$numericId];
    }
}

// // 使用示例
// $converter = new OpenIDConverter('/data/id_map.json');

// // 编码用户 OpenID
// $userNumericId = $converter->encode('USER_OPENID_123', 'user');
// echo "用户数字ID: " . $userNumericId . "\n";

// // 解码用户
// echo "用户原始ID: " . $converter->decode($userNumericId, 'user') . "\n";

// // 编码群 OpenID
// $groupNumericId = $converter->encode('GROUP_OPENID_456', 'group');
// echo "群数字ID: " . $groupNumericId . "\n";
?>