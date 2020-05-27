<?php
declare(strict_types=1);

namespace RequestChecker\Middleware;

use Cake\Http\Response;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Check request encoding
 */
class RequestEncodingCheckerMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->checkEncoding(
                rawurldecode($request->getUri()->getPath()),
                $request->getParsedBody(),
                $request->getQueryParams(),
                $request->getAttribute('params'),
                $request->getCookieParams(),
                $request->getServerParams()
            );
        } catch (Exception $e) {
            return new Response([
                'status' => 400,
                'type' => 'text/plain',
                'body' => "400 - Bad Encoding\n",
            ]);
        }

        return $handler->handle($request);
    }

    /**
     * Check encoding in targets
     *
     * @param ...$targets Targets
     * @return void
     */
    private function checkEncoding(...$targets): void
    {
        array_walk_recursive($targets, function ($value, $key) {
            $this->check($key);
            $this->check($value);
        });
    }

    /**
     * Check encoding
     *
     * @param mixed $value Value to be checked
     * @return void
     */
    private function check($value): void
    {
        if ($value instanceof UploadedFileInterface || $value === null || $value === '' || is_int($value)) {
            return;
        }
        $value = (string)$value;
        if (!mb_check_encoding($value, 'UTF-8') || strpos($value, "\0") !== false) {
            throw new Exception();
        }
    }
}
