<?php
/**
 * Class Message
 *
 * A class defining the logic around a message for use in SQS. The message is stored as JSON in the queue.
 *
 * @package     PhpSqsTutorial
 * @author      George Webb <george@webb.uno>
 * @license     http://opensource.org/licenses/MIT MIT License
 * @link        http://george.webb.uno/posts/aws-simple-queue-service-php-sdk
 */

namespace Gaw508\PhpSqsTutorial;

use Imagick;
use ImagickDraw;

class Message
{
    /**
     * The path of the uploaded file to be processed
     *
     * @var string
     */
    public $input_file_path;

    /**
     * The path to output the processed file
     *
     * @var string
     */
    public $output_file_path;

    /**
     * The receipt handle from SQS, used to identify the message when interacting with the queue
     *
     * @var string
     */
    public $receipt_handle;

    /**
     * Construct the object with message data and optional receipt_handle if relevant
     *
     * @param string|array $data  JSON String or an assoc array containing the message data
     * @param string $receipt_handle  The sqs receipt handle of the message
     */
    public function __construct($data, $receipt_handle = '')
    {
        // If data is a json string, decode it into an assoc array
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // Assign the data values and receipt handle to the object
        $this->input_file_path = $data['input_file_path'];
        $this->output_file_path = $data['output_file_path'];
        $this->receipt_handle = $receipt_handle;
    }

    /**
     * Returns the data of the message as a JSON string
     *
     * @return string  JSON message data
     */
    public function asJson()
    {
        return json_encode(array(
            'input_file_path' => $this->input_file_path,
            'output_file_path' => $this->output_file_path
        ));
    }

    /**
     * Processes an image given in the input file path, and outputs it in the output file path
     *
     * Takes the input image, creates a 300x300px thumbnail and overlays a text watermark.
     * Then deletes the input image.
     */
    public function process()
    {
        // Crete Imagick object from input image
        $image = new Imagick($this->input_file_path);

        // Crops the image into a 300x300px thumbnail
        $image->cropthumbnailimage(300, 300);

        // Set the watermark text
        $text = 'WATERMARK!!!';

        // Create a new drawing palette
        $draw = new ImagickDraw();

        // Set font properties
        $draw->setFont(__DIR__ . '/../fonts/built_titling_rg.ttf');
        $draw->setFontSize(26);
        $draw->setFillColor('black');
        $draw->setGravity(Imagick::GRAVITY_CENTER);

        // Draw the watermark onto the image
        $image->annotateImage($draw, 10, 12, 0, $text);
        $draw->setFillColor('white');
        $image->annotateImage($draw, 11, 11, 0, $text);

        // Set output image format
        $image->setImageFormat('jpg');

        // Output the processed image to the output path (as .jpg)
        $output_path = explode('.', $this->output_file_path);
        array_pop($output_path);
        $output_path = implode($output_path) . '.jpg';
        $image->writeImage($output_path);

        // Delete the input image
        unlink($this->input_file_path);
    }
}
