<?php

class Autoloader
{

    /**
     * 类库自动加载，写死路径，确保不加载其他文件。
     * @param string $class 对象类名
     * @return void
     */
    public static function autoload($class)
    {
        $name = $class;
        if (false !== strpos($name, '\\')) {
            $name = strstr($class, '\\', true);
        }

        $filename = JD_AUTOLOADER_PATH . "/cps/" . $name . ".php";
        if (is_file($filename)) {
            include $filename;

            return;
        }

        $filename = JD_AUTOLOADER_PATH . "/cps/request/" . $name . ".php";
        if (is_file($filename)) {
            include $filename;

            return;
        }

        $filename = JD_AUTOLOADER_PATH . "/cps/domain/" . $name . ".php";
        if (is_file($filename)) {
            include $filename;

            return;
        }
    }
}

spl_autoload_register('Autoloader::autoload', true, true);
