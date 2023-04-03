<?php

namespace Drift\CommandBus\Console;

abstract class CommandMessage
{
    /**
     * @param string $elapsedType
     *
     * @return string
     */
    protected function styledPerformance(string $elapsedType = null): string
    {
        $info = array_filter([
            $elapsedType ? $this->toLength($elapsedType, str_contains($elapsedType, 'μ') ? 8 : 7) : null,
            $this->toLength((string) (int) (memory_get_usage() / 1048576), 4),
            $this->toLength((string) (int) (memory_get_usage(true) / 1048576), 4),
        ]);

        return '<performance>['.implode('|', $info).']</performance>';
    }

    /**
     * @param string $string
     * @param int    $length
     *
     * @return string
     */
    protected function toLength(string $string, int $length): string
    {
        return str_pad($string, $length, ' ', STR_PAD_LEFT);
    }
}
