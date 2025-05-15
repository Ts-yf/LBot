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
  public function __construct($data)
  {
    $this->raw_data = $data;
    $this->event_type = $this->get('t'); // 事件类型，如GROUP_AT_MESSAGE_CREATE, INTERACTION_CREATE
    $this->message_id = $this->get('d/id');
    $this->content = $this->sanitize_content($this->get('d/content'));
    $this->sender_id = $this->get('d/author/id') ?? null;
    $this->timestamp = $this->get('d/timestamp');

    // 动态提取群/频道信息
    $this->group_id = $this->get('d/group_id') ?? null;
    $this->user_id = $this->get('d/author/id') ?? $this->sender_id;
    $this->channel_id = $this->get('d/channel_id') ?? null;
    $this->guild_id = $this->get('d/guild_id') ?? null;
  }

  public function reply($content = '', array $raw = [])
  {
    $ark = [
      'template_id' => 23,
      'kv' => [
        ['key' => '#DESC#', 'value' => 'TSmoe'],
        ['key' => '#PROMPT#', 'value' => '闲仁Bot'],
        [
          'key' => '#LIST#',
          'obj' => [
            [
              'obj_kv' => [
                ['key' => 'desc', 'value' => $content]
              ]
            ],
            [
              'obj_kv' => [
                ['key' => 'desc', 'value' => '点击邀我进群'],
                ['key' => 'link', 'value' => 'https://QUN.QQ.COM/qunpro/robot/qunshare?robot_uin=3889042293']
              ]
            ]
          ]
        ]
      ]
    ];
    $payload = json_encode([
      "msg_id" => $this->message_id,
      "msg_type" => 3,
      "ark" => $ark,
      "msg_seq" => rand(10000, 999999)
    ]);
    if (empty($content)) {
      $payload = Json($raw);
    }
    switch ($this->event_type) {
      case "GROUP_AT_MESSAGE_CREATE":
        $response = BOTAPI("/v2/groups/{$this->group_id}/messages", "POST", $payload);
      case "GROUP_ADD_ROBOT":
        $response = BOTAPI("/v2/groups/{$this->group_id}/messages", "POST", $payload);
      case "test":
        echo $content;
        $response = '{}';
      case "C2C_MESSAGE_CREATE":
        $response = BOTAPI("/v2/users/{$this->user_id}/messages", "POST", $payload);
    }
    return $response;
  }

  public function get($path)
  {
    return Json取($this->raw_data, $path);
  }

  private function sanitize_content($content)
  {
    return rtrim(ltrim(ltrim($content, "/")));
  }
}