PHP SQS Tutorial Demo
=====================

For the article please visit: [http://george.webb.uno/posts/aws-simple-queue-service-php-sdk](http://george.webb.uno/posts/aws-simple-queue-service-php-sdk)

There is a live demo version of this running at: [http://sqs-demo.george.webb.uno/](http://sqs-demo.george.webb.uno/)

To install this demo for your self you will need a PHP web server running PHP 5.3 or later, composer, as well as PHP CLI with access to the imagick extension.

### Instructions: ###

- Clone this git repo
- Run composer install
- Setup config.php with AWS credentials and SQS queue name
- Run the worker with command: "php -d extension=imagick.so worker.php"
- Visit index.php and upload some images.

### Quick explanation ###

For more detail please see my tutorial article linked above, or the tutorial.md file in this repository.

Essentially this is a demo showing a possible usage of SQS within a PHP application and an example implementation of the PHP AWS SDK. The demo involves a web page where a user can upload up to 10 images, which are put into a SQS queue to be watermarked. A separate component of the application is running in the background and watermarks each of these images from the queue. In reality the queue worker component would be running on a/many different servers and the image files would be stored remotely, rather than locally; however, for the sake of this demo, it is all done on the same machine.

Here is a quick breakdown of the important parts of this application:

- src/Queue.php is the class containing all the logic for communicating with SQS
- src/Message.php is the class containing the logic involved with the messages themselves and the processing of them
- index.php is the webpage which images are uploaded to and the results displayed
- images.php is the API call used by the demo JS for displaying queued and watermarked images
- worker.php is the queue worker which is run through the PHP CLI and processes the watermarking messages
