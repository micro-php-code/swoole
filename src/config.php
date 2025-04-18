<?php

declare(strict_types=1);

use Swoole\Server;

return [
    // 不开启协程就如同workerman一样
    'enable_coroutine' => true,
    'open_tcp_nodelay' => true,
    /*
     * 设置当前工作进程最大协程数量
     * 在 Server 程序中实际最大可创建协程数量等于 worker_num * max_coroutine
     */
    'max_coroutine' => 10000,
    /*
     * 一个 worker 进程在处理完超过此数值的任务后将自动退出，进程退出后会释放所有内存和资源
     * 不会立刻停止, 停止时间取决于max_wait_time
     */
    'max_request' => 10000,
    'socket_buffer_size' => 1024 * 1024 * 2,
    'buffer_output_size' => 1024 * 1024 * 2,
    /*
     * 设置 Worker 进程收到停止服务通知后最大等待时间【默认值：3】
     */
    'max_wait_time' => 5,
    // 配合Swoole\Server::reload可以实现安全重启
    'reload_async' => true,
    'hook_flags' => SWOOLE_HOOK_ALL,
    'pid_file' => base_path('runtime/microphp.pid'),
    'callback' => [
        'ManagerStart' => static function (Server $server) {},
    ],
];
