<?php
/**
 * Contains configuration options for the demo
 *
 * @package     PhpSqsTutorial
 * @author      George Webb <george@webb.uno>
 * @license     http://opensource.org/licenses/MIT MIT License
 * @link        http://george.webb.uno/posts/aws-simple-queue-service-php-sdk
 */

/**
 * The name of the SQS queue
 */
define('QUEUE_NAME', 'watermarker');

/**
 * AWS Credentials array for accessing the API
 *
 * It is a serialised array, which is then unserialised when used.
 */
define('AWS_CREDENTIALS', serialize(array(
    'region' => "[[AWS_REGION]]",
    'version' => "[[AWS_VERSION]]",
    'credentials' => array(
        'key'    => "[[AWS_ACCESS_KEY_ID]]",
        'secret' => "[[AWS_SECRET_ACCESS_KEY]]"
    )
)));
