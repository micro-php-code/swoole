<?php

declare(strict_types=1);
namespace MicroPHP\Swoole;
use MicroPHP\Framework\Http\Contract\HttpServerInterface;
use MicroPHP\Framework\Http\ServerConfig;
use MicroPHP\Framework\Http\ServerRequest;
use MicroPHP\Framework\Http\Traits\HttpServerTrait;
use MicroPHP\Framework\Router\Router;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class SwooleHttpServer implements HttpServerInterface
{
    use HttpServerTrait;
    public function run(Router $router): void
    {
        $serverConfig = new ServerConfig();
        $http = new Server($serverConfig->getHost(), $serverConfig->getPort());
        $http->set([
            'worker_num' => $serverConfig->getWorkers(),
        ]);

        $http->on('Request', function (Request $request, Response $response) use($router) {
            $psr7Request = ServerRequest::fromSwoole($request);
            $psr7Response = $this->routeDispatch($router, $psr7Request);
            foreach ($psr7Response->getHeaders() as $name => $value) {
                $response->header($name, $value);
            }
            $response->end($psr7Response->getBody());
        });

        $http->start();
    }
}