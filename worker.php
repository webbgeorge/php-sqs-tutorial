<?php

/**
 * The queue worker script
 *
 * To be run as the application component responsible for watermarking uploaded images. Polls queue for new jobs
 * and will keep polling queue until there are now jobs left, when it will wait for 20 seconds before continuing
 * to poll the queue.
 *
 * @package     PhpSqsTutorial
 * @author      George Webb <george@webb.uno>
 * @license     http://opensource.org/licenses/MIT MIT License
 * @link        http://george.webb.uno/posts/aws-simple-queue-service-php-sdk
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Gaw508\PhpSqsTutorial\Queue;

// Instantiate queue with aws credentials from config.
$queue = new Queue(QUEUE_NAME, unserialize(AWS_CREDENTIALS));

// Continuously poll queue for new messages and process them.
while (true) {
    $message = $queue->receive();
    if ($message) {
        try {
            $message->process();
            $queue->delete($message);
        } catch (Exception $e) {
            $queue->release($message);
            echo $e->getMessage();
        }
    } else {
        // Wait 20 seconds if no jobs in queue to minimise requests to AWS API
        sleep(20);
    }
}
