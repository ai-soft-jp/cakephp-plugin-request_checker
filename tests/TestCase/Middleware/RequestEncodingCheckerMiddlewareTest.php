<?php
declare(strict_types=1);

namespace RequestChecker\Test\TestCase\Middleware;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RequestChecker\Middleware\RequestEncodingCheckerMiddleware;

class RequestEncodingCheckerMiddlewareTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->middleware = new RequestEncodingCheckerMiddleware();
        $this->factory = new ServerRequestFactory();
        $this->handler = new DummyHandler();
    }

    /**
     * Assert response is 400 Bad Request
     *
     * @param int $code Response Code
     * @param \Psr\Http\Message\ResponseInterface $response Response
     * @return void
     */
    private function assertResponse(int $code, ResponseInterface $response): void
    {
        $this->assertSame($code, $response->getStatusCode());
    }

    /**
     * pathProvider
     */
    public function pathProvider(): array
    {
        return [
            [200, '/'],
            [200, '/%E5%AF%BF%E9%99%90%E7%84%A1'],
            [400, '/%80'],
            [400, '/%00'],
        ];
    }

    /**
     * paramsProvider
     */
    public function paramsProvider(): array
    {
        return [
            [200, ['foo' => '寿限無']],
            [200, ['foo' => ['寿限無']]],
            [200, ['寿限無' => 'deadbeef']],
            [400, ['foo' => "\x80"]],
            [400, ['foo' => ['bar' => "\x80"]]],
            [400, ["\x80" => 'deadbeef']],
            [400, ['foo' => ["\x80" => 'deadbeef']]],
            [400, ['foo' => "\x00"]],
            [400, ['foo' => ['bar' => "\x00"]]],
            [400, ["\x00" => 'deadbeef']],
            [400, ['foo' => ["\x00" => 'deadbeef']]],
        ];
    }

    /**
     * testProcessPath
     *
     * @dataProvider pathProvider
     */
    public function testProcessPath(int $code, string $path): void
    {
        $request = $this->factory->createServerRequest('GET', $path);
        $this->assertResponse($code, $this->middleware->process($request, $this->handler));
    }

    /**
     * testProcessBody
     *
     * @dataProvider paramsProvider
     */
    public function testProcessBody(int $code, array $params): void
    {
        $request = $this->factory->createServerRequest('GET', '/')
            ->withParsedBody($params);
        $this->assertResponse($code, $this->middleware->process($request, $this->handler));
    }

    /**
     * testProcessQuery
     *
     * @dataProvider paramsProvider
     */
    public function testProcessQuery(int $code, array $params): void
    {
        $request = $this->factory->createServerRequest('GET', '/')
            ->withQueryParams($params);
        $this->assertResponse($code, $this->middleware->process($request, $this->handler));
    }

    /**
     * testProcessParams
     *
     * @dataProvider paramsProvider
     */
    public function testProcessParams(int $code, array $params): void
    {
        $request = $this->factory->createServerRequest('GET', '/')
            ->withAttribute('params', $params);
        $this->assertResponse($code, $this->middleware->process($request, $this->handler));
    }

    /**
     * testProcessCookie
     *
     * @dataProvider paramsProvider
     */
    public function testProcessCookie(int $code, array $params): void
    {
        $request = $this->factory->createServerRequest('GET', '/')
            ->withCookieParams($params);
        $this->assertResponse($code, $this->middleware->process($request, $this->handler));
    }

    /**
     * testProcessServer
     *
     * @dataProvider paramsProvider
     */
    public function testProcessServer(int $code, array $params): void
    {
        $request = $this->factory->createServerRequest('GET', '/', $params);
        $this->assertResponse($code, $this->middleware->process($request, $this->handler));
    }
}
