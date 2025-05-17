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

    $payload = json_encode([
      "msg_type" => 0,
      "content" => $content,
      "msg_seq" => rand(10000, 999999)
    ]);
    if (empty($content)) {
      $payload = Json($raw);
    }
    switch ($this->event_type) {
      case "GROUP_AT_MESSAGE_CREATE": //群消息
        $payload['msg_id'] = $this->message_id;
        $response = BOTAPI("/v2/groups/{$this->group_id}/messages", "POST", $payload);
      case "C2C_MESSAGE_CREATE": //私聊消息
        $payload['msg_id'] = $this->message_id;
        $response = BOTAPI("/v2/users/{$this->user_id}/messages", "POST", $payload);
      case "GROUP_ADD_ROBOT": //群添加机器人
        $payload['event_id'] = $this->get('d/id');
        $response = BOTAPI("/v2/groups/{$this->group_id}/messages", "POST", $payload);
      case "INTERACTION_CREATE": //按钮消息
        $payload['event_id'] = $this->get('d/id');
        $response = BOTAPI("/v2/groups/{$this->group_id}/messages", "POST", $payload);
      case "test": //测试
        echo $content;
        $response = '{}';
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
