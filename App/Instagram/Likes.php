<?php
/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 009 09.11
 * Time: 14:59:15
 */

namespace Instagram;


use Exceptions\LikesInstagramException;

class Likes
{
    const QUERY_ID = 17864450716183058;
    const COUNT_LOAD = 100;

    public static function getLikes($shortCode)
    {
        $likes = [];
        if (empty($shortCode)) {
            return $likes;
        }

        try {
            $cursor = null;
            do {
                $result = self::curlGetLikes($shortCode, $cursor);
                foreach ($result['edges'] as $edge) {
                    $likes[$edge['node']['id']] = $edge['node'];
                }
                $cursor = $result['page_info']['end_cursor'];
            } while ($result['page_info']['has_next_page'] === true);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        return $likes;
    }

    private static function curlGetLikes($short_code, $cursor)
    {

        $curl2 = new \Curl();
        $curl2->setUrl('https://www.instagram.com/graphql/query/')
            ->setHeader('Content-Type', 'text/html')
            ->setMethod('GET')
            ->run([
                'query_id'  => self::QUERY_ID,
                'variables' => json_encode([
                    'shortcode' => $short_code,
                    'first'     => self::COUNT_LOAD,
                    'after'     => $cursor,
                ]),
            ]);
        $result = $curl2->getjson();
        if (!empty($result['data']['shortcode_media']['edge_liked_by'])) {
            $result = $result['data']['shortcode_media']['edge_liked_by'];
            return $result;
        } else {
            throw new LikesInstagramException('Ошибка получения списка лайков');
        }
    }
}
