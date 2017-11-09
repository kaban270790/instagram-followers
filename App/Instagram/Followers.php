<?php
/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 009 09.11
 * Time: 11:07:57
 */

namespace Instagram;


use Exceptions\FollowersInstagramException;

class Followers
{
    const QUERY_ID = 17851374694183129;
    const COUNT_LOAD = 1000;

    public static function getFollowers($instagramUserId)
    {
        $followers = [];

        try {
            $cursor = null;
            do {
                $edge_followed_by = self::curlGetFollowers($instagramUserId, $cursor);
                foreach ($edge_followed_by['edges'] as $edge) {
                    $followers[$edge['node']['id']] = $edge['node'];
                }
                $cursor = $edge_followed_by['page_info']['end_cursor'];
            } while ($edge_followed_by['page_info']['has_next_page'] === true);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        return $followers;
    }

    private static function curlGetFollowers($instagramUserId, $cursor)
    {

        $curl2 = new \Curl();
        $curl2->setUrl('https://www.instagram.com/graphql/query/')
            ->setHeader('Content-Type', 'text/html')
            ->setMethod('GET')
            ->run([
                'query_id'  => self::QUERY_ID,
                'variables' => json_encode([
                    'id'    => $instagramUserId,
                    'first' => self::COUNT_LOAD,
                    'after' => $cursor,
                ]),
            ]);
        $result = $curl2->getjson();
        if (!empty($result['data']['user']['edge_followed_by'])) {
            $edge_followed_by = $result['data']['user']['edge_followed_by'];
            return $edge_followed_by;
        } else {
            throw new FollowersInstagramException('Ошибка получения списка подписчиков');
        }
    }
}
