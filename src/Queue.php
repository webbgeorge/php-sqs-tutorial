<?php
/**
 * Class Queue
 *
 * A wrapper for amazon SQS, using the AWS PHP SDK
 *
 * @package     PhpSqsTutorial
 * @author      George Webb <george@webb.uno>
 * @license     http://opensource.org/licenses/MIT MIT License
 * @link        http://george.webb.uno/posts/aws-simple-queue-service-php-sdk
 */

namespace Gaw508\PhpSqsTutorial;

use Exception;
use Aws\Sqs\SqsClient;

class Queue
{
    /**
     * The name of the SQS queue
     *
     * @var string
     */
    private $name;

    /**
     * The url of the SQS queue
     *
     * @var string
     */
    private $url;

    /**
     * The array of credentials used to connect to the AWS API
     *
     * @var array
     */
    private $aws_credentials;

    /**
     * A SqsClient object from the AWS SDK, used to connect to the AWS SQS API
     *
     * @var SqsClient
     */
    private $sqs_client;

    /**
     * Constructs the wrapper using the name of the queue and the aws credentials
     *
     * @param $name
     * @param $aws_credentials
     */
    public function __construct($name, $aws_credentials)
    {
        try {
            // Setup the connection to the queue
            $this->name = $name;
            $this->aws_credentials = $aws_credentials;
            $this->sqs_client = new SqsClient($this->aws_credentials);

            // Get the queue URL
            $this->url = $this->sqs_client->getQueueUrl(array('QueueName' => $this->name))->get('QueueUrl');
        } catch (Exception $e) {
            echo 'Error getting the queue url ' . $e->getMessage();
        }
    }

    /**
     * Sends a message to SQS using a JSON output from a given Message object
     *
     * @param Message $message  A message object to be sent to the queue
     * @return bool  returns true if message is sent successfully, otherwise false
     */
    public function send(Message $message)
    {
        try {
            // Send the message
            $this->sqs_client->sendMessage(array(
                'QueueUrl' => $this->url,
                'MessageBody' => $message->asJson()
            ));

            return true;
        } catch (Exception $e) {
            echo 'Error sending message to queue ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Receives a message from the queue and puts it into a Message object
     *
     * @return bool|Message  Message object built from the queue, or false if there is a problem receiving message
     */
    public function receive()
    {
        try {
            // Receive a message from the queue
            $result = $this->sqs_client->receiveMessage(array(
                'QueueUrl' => $this->url
            ));

            if ($result['Messages'] == null) {
                // No message to process
                return false;
            }

            // Get the message and return it
            $result_message = array_pop($result['Messages']);
            return new Message($result_message['Body'], $result_message['ReceiptHandle']);
        } catch (Exception $e) {
            echo 'Error receiving message from queue ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Deletes a message from the queue
     *
     * @param Message $message
     * @return bool  returns true if successful, false otherwise
     */
    public function delete(Message $message)
    {
        try {
            // Delete the message
            $this->sqs_client->deleteMessage(array(
                'QueueUrl' => $this->url,
                'ReceiptHandle' => $message->receipt_handle
            ));

            return true;
        } catch (Exception $e) {
            echo 'Error deleting message from queue ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Releases a message back to the queue, making it visible again
     *
     * @param Message $message
     * @return bool  returns true if successful, false otherwise
     */
    public function release(Message $message)
    {
        try {
            // Set the visibility timeout to 0 to make the message visible in the queue again straight away
            $this->sqs_client->changeMessageVisibility(array(
                'QueueUrl' => $this->url,
                'ReceiptHandle' => $message->receipt_handle,
                'VisibilityTimeout' => 0
            ));

            return true;
        } catch (Exception $e) {
            echo 'Error releasing job back to queue ' . $e->getMessage();
            return false;
        }
    }
}
