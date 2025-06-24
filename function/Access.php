<?php
function 检查Token有效性($cache) {
    if (!isset($cache['timestamp']) || !isset($cache['expires_in'])) {
        return false;
    }
    $currentTime = time() * 1000; // 转换为毫秒
    return ($cache['timestamp'] + $cache['expires_in']) > $currentTime;
}

function 保存Token缓存($tokenData) {
    $cacheFile = __DIR__ . '/../data/bot_token_cache.json';
    $cacheData = [
        'access_token' => $tokenData['access_token'],
        'expires_in' => $tokenData['expires_in'],
        'timestamp' => time() * 1000 // 转换为毫秒
    ];
    file_put_contents($cacheFile, json_encode($cacheData));
}

function BOT凭证()
{
    $cacheFile = __DIR__ . '/../data/bot_token_cache.json';
    
    // 检查缓存是否存在且有效
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true);
        if ($cache && 检查Token有效性($cache)) {
            return $cache['access_token'];
        }
    }

    // 获取新token
    $appid = $GLOBALS['appid'];
    $Secret = $GLOBALS['secret'];
    $url = "https://bots.qq.com/app/getAppAccessToken";
    $json = Json(["appId" => $appid, "clientSecret" => $Secret]);
    $header = array('Content-Type: application/json');
    $response = curl($url, "POST", $header, $json);
    $tokenData = json_decode($response, true);
    
    if (isset($tokenData['access_token']) && isset($tokenData['expires_in'])) {
        保存Token缓存($tokenData);
        return $tokenData['access_token'];
    }
    
    return Json取($response, "access_token"); // 保持原有行为作为fallback
}

function BOTAPI($Address, $me, $json)
{
    $url = "https://api.sgroup.qq.com" . $Address;
    $header = array("Authorization: QQBot " . BOT凭证(), 'Content-Type: application/json');
    return curl($url, $me, $header, $json);
}
function Json($content)
{
    return json_encode($content, JSON_UNESCAPED_UNICODE);
}
function Json取($json, $path)
{
    $data = json_decode($json, true);
    $keys = explode('/', $path);
    foreach ($keys as $key) {
        if (is_array($data) && array_key_exists($key, $data)) {
            $data = $data[$key];
        } else {
            return "null";
        }
    }
    return $data;
}

function curl($url, $method, $headers, $params)
{
    $url = str_replace(" ", "%20", $url);
    if (is_array($params)) {
        $requestString = http_build_query($params);
    } else {
        $requestString = $params ?: '';
    }
    if (empty($headers)) {
        $headers = array('Content-type: text/json');
    } elseif (!is_array($headers)) {
        parse_str($headers, $headers);
    }
    // setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    // setting the POST FIELD to curl
    switch ($method) {
        case "GET":
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            break;
        case "POST":
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
        case "PUT":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
        case "DELETE":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
    }
    // getting response from server
    $response = curl_exec($ch);

    //close the connection
    curl_close($ch);

    //return the response
    if (stristr($response, 'HTTP 404') || $response == '') {
        return array('Error' => '请求错误');
    }
    return $response;
}