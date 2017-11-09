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
$followers = \Instagram\Followers::getFollowers($testAccountId);
$followersUsername = [];
foreach ($followers as $follower) {
    $followersUsername[] = $follower['username'];
}
if (empty($followersUsername)) {
    die("Подписчики отсутствуют");
}
try {
    Mysql::transaction();
    $dbActiveFollowersUsername = Mysql::selectColl("SELECT account FROM followers WHERE date_out IS NULL");
    $followersForAddingUsername = array_diff($followersUsername,
        $dbActiveFollowersUsername); //тех, которых надо бобавить, или заново активировать
    $followersForRemovingUsername = array_diff($dbActiveFollowersUsername,
        $followersUsername); //тех, которых надо пометить что не активные

    if (count($followersForAddingUsername) > 0) {
        foreach (array_chunk($followersForAddingUsername, Mysql::COUNT_INSERT_ROWS) as $part) {
            $sql = "INSERT INTO followers (account) VALUES ('" . implode("'), ('", $part) . "')
            ON DUPLICATE KEY UPDATE date_out = NULL, active = NULL";
            Mysql::connect()->query($sql);
        }
    }

    if (count($followersForRemovingUsername) > 0) {
        $subscriptions = \Instagram\Subscriptions::getSubscriptions($testAccountId);
        $subscriptionsUsername = [];
        foreach ($subscriptions as $subscription) {
            $subscriptionsUsername[] = $subscription['username'];
        }
        $followersOutSubscription = array_diff($followersForRemovingUsername,
            $subscriptionsUsername); //на которых не подписан
        $followersInSubscription = array_diff($followersForRemovingUsername,
            $followersOutSubscription); //на которых я подписан

        if (count($followersOutSubscription) > 0) {
            $sql = "UPDATE followers SET date_out = CURRENT_TIMESTAMP(), active = 0
            WHERE date_out IS NULL AND account IN ('" . implode("', '", $followersOutSubscription) . "');";
            Mysql::connect()->query($sql);
        }

        if (count($followersInSubscription) > 0) {
            $sql = "UPDATE followers SET date_out = CURRENT_TIMESTAMP(), active = 1
            WHERE date_out IS NULL AND account IN ('" . implode("', '", $followersInSubscription) . "');";
            Mysql::connect()->query($sql);
        }
    }

    Mysql::commit();
} catch (\Exception $e) {
    Mysql::rollback();
    die ($e->getMessage());
}
