<?php

declare(strict_types=1);

namespace MicroPHP\Swoole;

use MicroPHP\Framework\Config\Config;
use MicroPHP\Framework\Http\Contract\HttpServerInterface;
use MicroPHP\Framework\Http\ServerConfig;
use MicroPHP\Framework\Http\ServerRequest;
use MicroPHP\Framework\Http\Traits\HttpServerTrait;
use MicroPHP\Framework\Router\Router;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\Console\Output\OutputInterface;

class SwooleHttpServer implements HttpServerInterface
{
    use HttpServerTrait;

    public function run(Router $router, OutputInterface $output): void
    {
        $serverConfig = new ServerConfig();
        $http = new Server($serverConfig->getHost(), $serverConfig->getPort());
        $this->createRuntimeDir();
        $config = Config::get('swoole', []);
        $callbacks = $config['callback'] ?? [];
        unset($config['callback']);
        $config['worker_num'] = $serverConfig->getWorkers();
        $http->set($config);

        $http->on('Request', function (Request $request, Response $response) use ($router) {
            $psr7Request = ServerRequest::fromSwoole($request);
            $psr7Response = $this->routeDispatch($router, $psr7Request);
            foreach ($psr7Response->getHeaders() as $name => $value) {
                $response->header($name, $value);
            }
            $response->setStatusCode($psr7Response->getStatusCode());
            $response->end($psr7Response->getBody());
        });
        $http->on('Start', function (Server $server) use ($output, $serverConfig) {
            $output->writeln('<info>MicroPHP server start success by swoole</info>');
            $output->writeln('<info>' . "Listen {$serverConfig->getUri(true)}" . '</info>');
        });
        $http->on('workerStart', function (Server $server) use ($output) {
            if ($server->worker_id > 0) {
                $output->writeln('<info>' . "Worker {$server->getWorkerId()} start success, pid: {$server->getWorkerPid()}" . '</info>');
            }
        });
        foreach ($callbacks as $event => $callback) {
            if (str_starts_with($event, 'on')) {
                $event = substr($event, 2);
            }
            $http->on($event, $callback);
        }
        $http->start();
    }
}
