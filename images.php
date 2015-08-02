<?php

/**
 * This is an API call just for the demo to get all images which are waiting to be watermarked,
 * as well as images which have been, so that they can be displayed on page
 *
 * @package     PhpSqsTutorial
 * @author      George Webb <george@webb.uno>
 * @license     http://opensource.org/licenses/MIT MIT License
 * @link        http://george.webb.uno/posts/aws-simple-queue-service-php-sdk
 */

$waiting_images = scandir(__DIR__ . '/images/queued');
$watermarked_images = scandir(__DIR__ . '/images/watermarked');

$output = array('waiting' => $waiting_images, 'watermarked' => $watermarked_images);

echo json_encode($output);
