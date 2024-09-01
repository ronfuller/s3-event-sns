<?php

namespace Psi\S3EventSns\Services\Aws;

use RuntimeException;

use function json_validate;

class Message extends \Aws\Sns\Message
{
    public static function fromRawPostData(): Message
    {
        if (! request()->hasHeader('x-amz-sns-message-type')) {
            throw new RuntimeException('SNS message type header not provided.');
        }

        // Read the raw POST data and JSON-decode it into a message.
        return self::fromJsonString(request()->getContent());
    }

    /**
     * Creates a Message object from a JSON-decodable string.
     *
     * @param  string  $requestBody
     */
    public static function fromJsonString($requestBody): Message    // @phpstan-ignore-line
    {
        if (json_validate($requestBody) === false) {
            throw new RuntimeException('Invalid POST data.');
        }
        $data = json_decode($requestBody, true);

        if (! is_array($data)) {
            throw new RuntimeException('Invalid POST data.');
        }

        return new Message($data);
    }
}
