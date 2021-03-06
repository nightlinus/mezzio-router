<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DispatchMiddlewareTest extends TestCase
{
    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    /** @var DispatchMiddleware */
    private $middleware;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    protected function setUp(): void
    {
        $this->response   = $this->prophesize(ResponseInterface::class)->reveal();
        $this->request    = $this->prophesize(ServerRequestInterface::class);
        $this->handler    = $this->prophesize(RequestHandlerInterface::class);
        $this->middleware = new DispatchMiddleware();
    }

    public function testInvokesHandlerIfRequestDoesNotContainRouteResult()
    {
        $this->request->getAttribute(RouteResult::class, false)->willReturn(false);
        $this->handler->handle($this->request->reveal())->willReturn($this->response);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame($this->response, $response);
    }

    public function testInvokesRouteResultWhenPresent()
    {
        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult
            ->process(
                Argument::that([$this->request, 'reveal']),
                Argument::that([$this->handler, 'reveal'])
            )
            ->willReturn($this->response);

        $this->request->getAttribute(RouteResult::class, false)->will([$routeResult, 'reveal']);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame($this->response, $response);
    }
}
