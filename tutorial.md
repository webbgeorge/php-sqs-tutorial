Using AWS Simple Queue Service with the PHP SDK
===============================================

Amazon Web Services Simple Queue Service is awesome.

For those of you who don't know, SQS is a highly scalable and reliable distributed queueing system, which can be used to separate components of an application. This article goes into how to utilise SQS using the PHP SDK and concludes with an example of how it might be used.

**Contents:**

- Setting up the PHP SDK
- Creating queues
- Sending messages
- Receiving messages
- Dealing with failures
- An example

## Setting up the PHP SDK ##

<br>

Amazon provides a fantastic PHP SDK for its web services, more info can be found at [http://aws.amazon.com/sdk-for-php/](http://aws.amazon.com/sdk-for-php/)

**Installing the SDK**

The best way to install the SDK is by using Composer, all you will need to do is require aws/aws-sdk-php and include the autoloader. If you are unfamiliar with composer, I thoroughly recommend that you take a look at [https://getcomposer.org/](https://getcomposer.org/), otherwise, you can install the SDK by downloading a zip from github [https://github.com/aws/aws-sdk-php/releases](https://github.com/aws/aws-sdk-php/releases)

**Authentication**

When communicating with the AWS API, the SDK requires the following:

- The AWS region (e.g. 'eu-west-1')
- The version of the AWS API to use ('latest' will usually be fine)
- Your AWS Access Key ID (Under 'Your Security Credentials')
- Your AWS Secret Access key (Under 'Your Security Credentials')

These are passed to the SDK in the form of an array:

    $aws_credentials = array(
        'region' => AWS_REGION,
        'version' => AWS_VERSION,
        'credentials' => array(
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        )
    );
    
**Handling errors**

In the case of errors the SDK will throw exceptions, therefore we need to use try catch blocks in our code.

## Creating queues ##

<br>

Queues have a small number of settings we can use to configure it for our needs, however the only mandatory one is a name, which is used to identify the queue when sending and receiving messages. There are other options including how long messages should retained and the size of messages. For the sake of simplicity I will only use the name here. Queues can be created easily either in the AWS console, or by using the API.

To interact with our queues, we will be using the SqsClient class provided by the SDK, all this requires is our credentials array mentioned above, and its that simple. To create a queue we will use the SqsClient::createQueue() method, nice and straightforward, ey? The below code creates a queue called "our_queue". In reality this code will be used infrequently, compared to the sending and receiving of messages, but it is included in case your application makes use of dynamically creating queues.

    try {
        $sqs_credentials = array(
            'region' => '[[YOUR_AWS_REGION]]',
            'version' => 'latest',
            'credentials' => array(
                'key'    => '[[YOUR_AWS_ACCESS_KEY_ID]]',
                'secret' => '[[YOUR_AWS_SECRET_ACCESS_KEY]]',
            )
        );
    
        // Instantiate the client
        $sqs_client = new SqsClient($sqs_credentials);
        
        // Create the queue
        $queue_options = array(
            'QueueName' => 'our_queue'
        );
        $sqs_client->createQueue($queue_options);
    } catch (Exception $e) {
        die('Error creating new queue ' . $e->getMessage());
    }

## Sending messages ##

<br>

The next part is to send messages to the queue, this will be performed by the component(s) of the application which is effectively "delegating" jobs to another component(s) of the application. The messages will need to contain the information required by the other component of the application to process the job, a good way to do this is to use JSON, but XML or other methods could be used.

Once again it is a very simple operation thanks to Amazon's excellent SDK. The below code adds a JSON message to "our_queue" using the SqsClient::sendMessage() method.

    try {
        $sqs_credentials = array(
            'region' => '[[YOUR_AWS_REGION]]',
            'version' => 'latest',
            'credentials' => array(
                'key'    => '[[YOUR_AWS_ACCESS_KEY_ID]]',
                'secret' => '[[YOUR_AWS_SECRET_ACCESS_KEY]]',
            )
        );
    
        // Instantiate the client
        $sqs_client = new SqsClient($sqs_credentials);
    
        // Get the queue URL from the queue name.
        $result = $sqs_client->getQueueUrl(array('QueueName' => "our_queue"));
        $queue_url = $result->get('QueueUrl');
    
        // The message we will be sending
        $our_message = array('foo' => 'blah', 'bar' => 'blah blah');
    
        // Send the message
        $sqs_client->sendMessage(array(
            'QueueUrl' => $queue_url,
            'MessageBody' => json_encode($our_message)
        ));
    } catch (Exception $e) {
        die('Error sending message to queue ' . $e->getMessage());
    }

## Receiving messages ##

<br>

The component of the application whose purpose is to process the messages needs to be able to get them from the queue, this is done by using the SqsClient::receiveMessage() method.

    try {
        $sqs_credentials = array(
            'region' => '[[YOUR_AWS_REGION]]',
            'version' => 'latest',
            'credentials' => array(
                'key'    => '[[YOUR_AWS_ACCESS_KEY_ID]]',
                'secret' => '[[YOUR_AWS_SECRET_ACCESS_KEY]]',
            )
        );
    
        // Instantiate the client
        $sqs_client = new SqsClient($sqs_credentials);
    
        // Get the queue URL from the queue name.
        $result = $sqs_client->getQueueUrl(array('QueueName' => "our_queue"));
        $queue_url = $result->get('QueueUrl');
    
        // Receive a message from the queue
        $result = $sqs_client->receiveMessage(array(
            'QueueUrl' => $queue_url
        ));
    
        if ($result['Messages'] == null) {
            // No message to process
            exit;
        }
    
        // Get the message information
        $result_message = array_pop($result['Messages']);
        $queue_handle = $result_message['ReceiptHandle'];
        $message_json = $result_message['Body'];
    
        // Do some processing...
    
    } catch (Exception $e) {
        die('Error receiving message to queue ' . $e->getMessage());
    }

From the message received, we have got the message JSON, which we use to process the message relating to our application, and the receipt handle, which we use to close off the message when we successfully finish processing it. Closing off the message (deleting it) is shown below using the SqsClient::deleteMessage() method.

    try {
         $sqs_credentials = array(
             'region' => '[[YOUR_AWS_REGION]]',
             'version' => 'latest',
             'credentials' => array(
                 'key'    => '[[YOUR_AWS_ACCESS_KEY_ID]]',
                 'secret' => '[[YOUR_AWS_SECRET_ACCESS_KEY]]',
             )
         );
     
         // Instantiate the client
         $sqs_client = new SqsClient($sqs_credentials); 
     
         // Get the queue URL from the queue name.
         $result = $sqs_client->getQueueUrl(array('QueueName' => "our_queue"));
         $queue_url = $result->get('QueueUrl');
     
         $sqs_client->deleteMessage(array(
             'QueueUrl' => $queue_url,
             'ReceiptHandle' => $queue_handle
         ));
     } catch (Exception $e) {
         die('Error deleting job from queue ' . $e->getMessage());
     }

## Dealing with failures ##

<br>

There are several issues that can arise in this process, which can all be easily mitigated using careful thinking:

1) The component receiving and processing the messages fails whilst processing a message. If the message is deleted from the queue after this event it will never be processed, so it is important these errors are handled correctly, and jobs are given back to the queue. This can be done by setting the message visibility timeout to 0, making it instantly visible in the queue to be tried again.

    try {
        $sqs_credentials = array(
             'region' => '[[YOUR_AWS_REGION]]',
             'version' => 'latest',
             'credentials' => array(
                 'key'    => '[[YOUR_AWS_ACCESS_KEY_ID]]',
                 'secret' => '[[YOUR_AWS_SECRET_ACCESS_KEY]]',
             )
         );
     
         // Instantiate the client
         $sqs_client = new SqsClient($sqs_credentials); 
     
         // Get the queue URL from the queue name.
         $result = $sqs_client->getQueueUrl(array('QueueName' => "our_queue"));
         $queue_url = $result->get('QueueUrl');
    
        $sqs_client->changeMessageVisibility(array(
            'QueueUrl' => $queue_url,
            'ReceiptHandle' => $queue_handle,
            'VisibilityTimeout' => 0
        ));
    } catch (Exception $e) {
        die('Error releasing job back to queue ' . $e->getMessage());
    }

2) The receiving and processing component gets stuck processing a message and it is never deleted or released back to the queue. For this scenario, the queue has a visibilityTimeout setting, which is how long after a message is received before it is automatically added back to the queue if it is not released or deleted. This can be set depending on how long the message processing is expected to take in the application.

3) A message is repeatedly received and failed, maybe because it is erroneous or corrupted. In this situation, a maximum number of receives can be set on a queue, which prevents a message from being attempted any more times than this value. You can let this drop the message off the queue, or you can configure a separate queue called a dead letter queue, into which these repeatedly failed messages will be put. You can then process this queue accordingly to handle this scenario.

## An example ##

<br>

Say we have an application where users can upload multiple images to a web page, and those images would subsequently be watermarked before being displayed back to the user. If the user is uploading multiple images, it could take time and processing power, so we may want to watermark the images in the background, potentially using other servers dedicated to this task alone. We can achieve this by using SQS as a centralised queue containing a message for each of the images which need to be watermarked, giving information such as the path of the image and the path to save the output image.

So, we have our web page where users can upload images, which are then saved, and messages sent to the queue for watermarking. We then also have a worker script, which polls the queue for images to watermark and then watermarks them and saves them in the relevant location. The final images are then displayed to the user.

I have created a demo for this example, which can be viewed at [http://sqs-demo.george.webb.uno/](http://sqs-demo.george.webb.uno/) and the code can be found at [https://github.com/gaw508/php-sqs-tutorial](https://github.com/gaw508/php-sqs-tutorial)

Information on how to install your own version of the demo and briefly how it works can be seen in the readme.md in the git repository.

If you have any questions about this tutorial or the demo, please get in touch either by email or in the comments below.