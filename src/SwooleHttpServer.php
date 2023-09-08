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
        $this->createRuntimeDir();
        $http->set([
            'enable_coroutine' => true,
            'worker_num' => $serverConfig->getWorkers(),
            'open_tcp_nodelay' => true,
            'max_coroutine' => 10000,
            'max_request' => 10000,
            'socket_buffer_size' => 1024 * 1024 * 2,
            'buffer_output_size' => 1024 * 1024 * 2,
            'hook_flags' => SWOOLE_HOOK_ALL,
            'pid_file' => base_path('runtime/microphp.pid'),
        ]);

        $http->on('Request', function (Request $request, Response $response) use ($router) {
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
