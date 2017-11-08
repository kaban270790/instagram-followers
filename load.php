<?php
/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 005 05.10
 * Time: 00:30:06
 */
define('APP_DIR', __DIR__);
spl_autoload_register(function ($class) {
    require_once APP_DIR . "/App/{$class}.php";
}, true, true);
