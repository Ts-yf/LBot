<?php
interface Plugin
{
    /**
     * 注册正则规则与处理函数
     * @return array [正则表达式 => 处理函数名]
     */
    public static function getRegexHandlers();
}
class PluginManager
{
    private static $regexHandlers = [];

    // 加载所有插件
    public static function loadPlugins()
    {
        foreach (glob(__DIR__ . '/../../plugins/*/main.php') as $pluginFile) {
            require_once $pluginFile;
            $className = basename(dirname($pluginFile)) . '_Plugin';
            if (class_exists($className)) {
                self::registerPlugin($className);
            }
        }
    }

    private static function registerPlugin($className)
    {
        foreach ($className::getRegexHandlers() as $pattern => $handler) {
            self::$regexHandlers[$pattern] = [$className, $handler];
        }
    }

    // 匹配消息并触发处理函数
    public static function dispatchMessage(MessageEvent $e)
    {
        foreach (self::$regexHandlers as $pattern => $callback) {
            if (preg_match($pattern, $e->content, $matches)) {
                $e->matches = $matches;
                call_user_func($callback, $e);
                return true; // 匹配成功则终止后续匹配
            }
        }
        return false; // 无匹配规则
    }
}