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
$publications = $instagram->getPublications(5);
foreach ($publications as $publicationId => $publication) {
    var_dump(count($instagram->getLikes($publication['shortcode'])));
    if ($publication['is_video'] === true) {
        var_dump(count($instagram->getViews($publication['shortcode'])));
    }
}

