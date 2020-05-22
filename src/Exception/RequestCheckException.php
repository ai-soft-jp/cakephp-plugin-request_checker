<?php
declare(strict_types=1);

namespace RequestChecker\Exception;

use Cake\Http\Exception\HttpException;

class RequestCheckException extends HttpException
{
    /**
     * @inheritDoc
     */
    protected $_defaultCode = 404;

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Invalid parameter' will be the message
     * @param int|null $code Status code, defaults to 404
     * @param \Throwable|null $previous The previous exception.
     */
    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'Invalid parameter';
        }
        parent::__construct($message, $code, $previous);
    }
}
