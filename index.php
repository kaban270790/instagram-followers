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
    $dbActiveFollowersUsername = Mysql::selectColl("SELECT account FROM followers WHERE date_out IS NULL");
    $followersForAddingUsername = array_diff($followersUsername,
        $dbActiveFollowersUsername); //тех, которых надо бобавить, или заново активировать
    $followersForRemovingUsername = array_diff($dbActiveFollowersUsername,
        $followersUsername); //тех, которых надо пометить что не активные
    Mysql::connect(true);
    if (count($followersForAddingUsername) > 0) {
        foreach (array_chunk($followersForAddingUsername, Mysql::COUNT_INSERT_ROWS) as $part) {
            $sql = "INSERT INTO followers (account) VALUES ('" . implode("'), ('", $part) . "')
            ON DUPLICATE KEY UPDATE date_out = NULL, active = NULL";
            Mysql::connect()->query($sql);
        }
    }

    if (count($followersForRemovingUsername) > 0) {
        $sql = "UPDATE followers SET date_out = CURRENT_TIMESTAMP()
            WHERE date_out IS NULL AND account IN ('" . implode("', '", $followersForRemovingUsername) . "');";
        Mysql::connect()->query($sql);
    }

    $subscriptions = \Instagram\Subscriptions::getSubscriptions($testAccountId);
    $subscriptionsUsername = [];
    foreach ($subscriptions as $subscription) {
        $subscriptionsUsername[] = $subscription['username'];
    }
    $followersOutSubscription = array_diff($followersUsername,
        $subscriptionsUsername); //на которых не подписан
    Mysql::connect(true);
    if (count($subscriptionsUsername) > 0) {
        $sql = "UPDATE followers SET active = 1
            WHERE date_out IS NULL AND account IN ('" . implode("', '", $subscriptionsUsername) . "');";
        Mysql::connect()->query($sql);
    }
    if (count($followersOutSubscription) > 0) {
        $sql = "UPDATE followers SET active = 0
            WHERE date_out IS NULL AND account IN ('" . implode("', '", $followersOutSubscription) . "');";
        Mysql::connect()->query($sql);
    }

    $publications = \Instagram\Publications::getPublications($testAccountId,
        Config::getConfig('instagram.publication_count'));
    $rowInsert = [];
    foreach ($publications as $publication) {
        $countOfFollowers = 0;
        $countOfOthers = 0;
        $likes = \Instagram\Likes::getLikes($publication['shortcode']);
        foreach ($likes as $like) {
            if (in_array($like['username'], $followersUsername)) {
                $countOfFollowers++;
            } else {
                $countOfOthers++;
            }
        }
        if ($publication['is_video'] === true) {
            $countActions = $publication['video_view_count'];
        } else {
            $countActions = $publication['edge_media_preview_like']['count'];
        }
        $sql = "INSERT INTO posts (post_id, type, actions) 
                VALUES ('{$publication['id']}', '{$publication['__typename']}', '{$countActions}') 
                ON DUPLICATE KEY UPDATE actions = '{$countActions}'";
        Mysql::connect()->query($sql);
        $rowInsert[] = "(CURRENT_TIMESTAMP(), '{$publication['id']}', {$countOfFollowers}, {$countOfOthers})";
    }
    Mysql::connect(true);
    if (!empty($rowInsert)) {
        foreach (array_chunk($rowInsert, Mysql::COUNT_INSERT_ROWS) as $part) {
            $sql = "INSERT INTO `action` (`datetime`, `post_id`, `followers`, `other_users`)
                VALUES " . implode(', ', $part);
            Mysql::connect()->query($sql);
        }
    }

    $countFollowers = count($followersUsername);
    $sql = "INSERT INTO `log‐scan` (`datetime`, `account`, `followers`) 
            VALUES (CURRENT_TIMESTAMP(), {$testAccountId}, {$countFollowers})";
    Mysql::connect()->query($sql);


} catch (\Exception $e) {
    die ($e->getMessage());
}
