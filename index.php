<?php
/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 007 07.11
 * Time: 10:15:39
 */

ini_set('display_errors', true);
error_reporting(E_ALL);

require_once "load.php";
$instagram = new Instagram();
$testAccountId = Config::getConfig('instagram.test_account_id');
if (empty($testAccountId)) {
    die('Не указан аккаунт для исследования');
}
