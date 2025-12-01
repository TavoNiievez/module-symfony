<?php

declare(strict_types=1);

namespace Tests\_app\Logger;

use Psr\Log\AbstractLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

final class ArrayLogger extends AbstractLogger implements DebugLoggerInterface
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $logs = [];

    public function log($level, $message, array $context = []): void
    {
        $priorityMap = [
            'DEBUG' => 100,
            'INFO' => 200,
            'NOTICE' => 250,
            'WARNING' => 300,
            'ERROR' => 400,
            'CRITICAL' => 500,
            'ALERT' => 550,
            'EMERGENCY' => 600,
        ];

        $priorityName = strtoupper((string) $level);
        $priority = $priorityMap[$priorityName] ?? 200;
        $timestamp = microtime(true);

        $this->logs[] = [
            'message' => (string) $message,
            'context' => $context,
            'priority' => $priority,
            'priorityName' => $priorityName,
            'channel' => 'app',
            'timestamp' => $timestamp,
            'timestamp_rfc3339' => date(DATE_RFC3339, (int) $timestamp),
            'errorCount' => 1,
        ];
    }

    public function getLogs(?Request $request = null): array
    {
        return $this->logs;
    }

    public function countErrors(?Request $request = null): int
    {
        return count(array_filter(
            $this->logs,
            static fn(array $log): bool => $log['priority'] >= 400,
        ));
    }

    public function clear(): void
    {
        $this->logs = [];
    }
}
