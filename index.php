<?php

/**
 * The demo page with bootstrap front end, also handles upload of images and queueing
 *
 * @package     PhpSqsTutorial
 * @author      George Webb <george@webb.uno>
 * @license     http://opensource.org/licenses/MIT MIT License
 * @link        http://george.webb.uno/posts/aws-simple-queue-service-php-sdk
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Gaw508\PhpSqsTutorial\Message;
use Gaw508\PhpSqsTutorial\Queue;

// Array of messages to be displayed to the user.
$warnings = array();

if ( !empty($_FILES)) {
    // check number of files to upload
    $number_of_images = count($_FILES['images']['name']);

    // Only upload a max of 10 files
    if ($number_of_images > 10) {
        $warnings[] = array(
            'class' => 'alert-danger',
            'text' => 'Too many images, please upload a maximum of 10 images.'
        );
    } else {
        $successes = 0;

        // For each upload, check if an image and valid etc.
        for ($i = 0; $i < $number_of_images; $i++) {
            if ($_FILES['images']['error'][$i] > 0) {
                $warnings[] = array('class' => 'alert-danger', 'text' => 'Error uploading file.');
            } elseif ( !filesize($_FILES['images']['tmp_name'][$i])) {
                $warnings[] = array('class' => 'alert-danger', 'text' => 'Error uploading file.');
            } elseif ($_FILES['images']['type'][$i] != 'image/png' and $_FILES['images']['type'][$i] != 'image/jpeg') {
                $warnings[] = array('class' => 'alert-danger', 'text' => 'Invalid file type.');
            } elseif ($_FILES['images']['size'][$i] > 2000000) {
                $warnings[] = array('class' => 'alert-danger', 'text' => 'File too big.');
            } else {
                // Create a new filename for the uploaded image and move it there
                $extension = $_FILES['images']['type'][$i] == 'image/png' ? '.png' : '.jpg';
                $new_name = uniqid() . $extension;
                if ( !move_uploaded_file($_FILES['images']['tmp_name'][$i], __DIR__ . '/images/queued/' . $new_name)) {
                    $warnings[] = array('class' => 'alert-danger', 'text' => 'Error uploading file.');
                } else {
                    // Create a new message with processing instructions and push to SQS queue
                    $message = new Message(array(
                        'input_file_path' => __DIR__ . '/images/queued/' . $new_name,
                        'output_file_path' => __DIR__ . '/images/watermarked/' . $new_name
                    ));
                    $queue = new Queue(QUEUE_NAME, unserialize(AWS_CREDENTIALS));
                    if ($queue->send($message)) {
                        $successes++;
                    } else {
                        $warnings[] = array('class' => 'alert-danger', 'text' => 'Error adding file to queue.');
                    }
                }
            }
        }

        if ($successes > 0) {
            $warnings[] = array('class' => 'alert-success', 'text' => "$successes images uploaded successfully.");
            $warnings[] = array('class' => 'alert-info', 'text' => "Uploaded images added to queue...");
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP SQS Demo</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>
                PHP SQS Demo
                <small>For the tutorial by <a href="http://george.webb.uno/" target="_blank">George Webb</a></small>
            </h1>
        </div>

        <div class="alert alert-info">
            This is a demo built to show the example given in my tutorial article, which can be found at
            <a href="http://george.webb.uno/posts/aws-simple-queue-service-php-sdk" class="alert-link" target="_blank">
                http://george.webb.uno/posts/aws-simple-queue-service-php-sdk
            </a>
        </div>

        <?php foreach ($warnings as $warning) : ?>
            <?php if ( !empty($warning)) : ?>
                <div class="alert <?php echo $warning['class']; ?>"><?php echo $warning['text']; ?></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="row">
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Upload multiple images to have them watermarked</h3>
                    </div>

                    <div class="panel-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="imageUpload">File input</label>
                                <input type="file" multiple="multiple" id="imageUpload" name="images[]">
                                <p class="help-block">Choose multiple jpg or png files.</p>
                            </div>
                            <button type="submit" class="btn btn-default">Submit</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-sm-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Watermarked images</h3>
                    </div>

                    <div class="panel-body">
                        <div class="alert alert-warning">
                            <strong>Heads up!</strong> Images are deleted after one hour
                        </div>

                        <div class="watermarked-images">
                            Loading ...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Poll the server every 2 seconds to look for newly uploaded and processed files.
            setInterval(function() {
                $.ajax({
                    url: 'images.php',
                    method: 'get',
                    dataType: 'json'
                })
                    .done(function(data) {
                        // Display image files on page, show placeholder for unprocessed files.

                        var total = 0;
                        var output = '<div class="row">';

                        for (var i = 0; i < data.waiting.length; i++) {
                            if (data.waiting[i].indexOf(".jpg") > -1) {
                                output += '<div class="col-xs-6 col-xs-3">' +
                                '<img class="img-responsive" src="images/placeholder.png">' +
                                '</div>';
                                total++;
                            }
                        }

                        for (var i = 0; i < data.watermarked.length; i++) {
                            if (data.watermarked[i].indexOf(".jpg") > -1) {
                                output += '<div class="col-xs-6 col-xs-3">' +
                                '<img class="img-responsive" src="images/watermarked/' + data.watermarked[i] + '">' +
                                '</div>';
                                total++;
                            }
                        }

                        output += '</div>';

                        if (total <= 0) {
                            output = 'No images yet...';
                        }

                        $('.watermarked-images').html(output);
                    });
            }, 2000);
        });
    </script>
</body>
</html>
