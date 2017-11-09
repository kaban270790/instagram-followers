<?php
/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 009 09.11
 * Time: 11:07:57
 */

namespace Instagram;


use Exceptions\PublicationsInstagramException;

class Publications
{
    const QUERY_ID = 17888483320059182;
    const COUNT_LOAD = 30;

    public static function getPublications($count = false)
    {
        $publications = [];

        try {
            $i = 0;
            $cursor = null;
            do {
                if ($count !== false && count($publications) >= $count) {
                    break;
                }
                $result = self::curlGetPublications($cursor);
                foreach ($result['edges'] as $edge) {
                    if ($count !== false && count($publications) >= $count) {
                        break;
                    }
                    $publications[$edge['node']['id']] = $edge['node'];
                }
                $cursor = $result['page_info']['end_cursor'];
                $i++;
            } while ($result['page_info']['has_next_page'] === true);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        return $publications;
    }

    private static function curlGetPublications($cursor)
    {

        $curl2 = new \Curl();
        $curl2->setUrl('https://www.instagram.com/graphql/query/')
            ->setHeader('Content-Type', 'text/html')
            ->setMethod('GET')
            ->run([
                'query_id'  => self::QUERY_ID,
                'variables' => json_encode([
                    'id'    => \Instagram::getUserId(),
                    'first' => self::COUNT_LOAD,
                    'after' => $cursor,
                ]),
            ]);
        $result = $curl2->getjson();
        if (!empty($result['data']['user']['edge_owner_to_timeline_media'])) {
            $edge_owner_to_timeline_media = $result['data']['user']['edge_owner_to_timeline_media'];
            return $edge_owner_to_timeline_media;
        } else {
            throw new PublicationsInstagramException('Ошибка получения списка матриалов');
        }
    }
}
