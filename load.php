<?php
/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 005 05.10
 * Time: 00:30:06
 */
spl_autoload_register(function ($class) {
    require_once __DIR__ . "/App/{$class}.php";
}, true, true);
define('APP_DIR', __DIR__ . '/..');
