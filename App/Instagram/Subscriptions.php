<?php
/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 009 09.11
 * Time: 11:07:57
 */

namespace Instagram;


use Exceptions\SubscriptionsInstagramException;

class Subscriptions
{
    const QUERY_ID = 17874545323001329;
    const COUNT_LOAD = 100;

    public static function getSubscriptions($instagramUserId)
    {
        $subscriptions = [];

        try {
            $cursor = null;
            do {
                $edge_follow = self::curlGetSubscriptions($cursor);
                foreach ($edge_follow['edges'] as $edge) {
                    $subscriptions[$edge['node']['id']] = $edge['node'];
                }
                $cursor = $edge_follow['page_info']['end_cursor'];
            } while ($edge_follow['page_info']['has_next_page'] === true);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        return $subscriptions;
    }

    private static function curlGetSubscriptions($instagramUserId, $cursor)
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
        if (!empty($result['data']['user']['edge_follow'])) {
            $edge_followed_by = $result['data']['user']['edge_follow'];
            return $edge_followed_by;
        } else {
            throw new SubscriptionsInstagramException('Ошибка получения списка подписок');
        }
    }
}
