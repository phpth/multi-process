<?php
// +----------------------------------------------------------------------
// | multi-process
// +----------------------------------------------------------------------
// | Copyright (c) 2022
// +----------------------------------------------------------------------
// | Licensed MIT
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | Date: 2022-07-09
// +----------------------------------------------------------------------
// | Time: 下午 03:22
// +----------------------------------------------------------------------

namespace phpth\process;

spl_autoload_register(function ($class_name) {
    if (stripos($class_name, 'phpth\process') === false) {
        return false;
    }
    $class_name = str_ireplace('phpth\process', '', $class_name);
    $class_name = str_replace('\\', '/', ltrim($class_name, '\\'));
    $path = realpath(__DIR__);
    $file = $path . "/{$class_name}.php";
    if (file_exists($file)) {
        return require $file;
    } else {
        return false;
    }
});
