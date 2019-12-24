<?php


namespace Drift\Bus;

use React\Promise\PromiseInterface;

/**
 * Class AsyncMiddleware
 */
class AsyncMiddleware
{
    /**
     * @var AsyncAdapter
     */
    private $asyncAdapter;

    /**
     * AsyncMiddleware constructor.
     *
     * @param AsyncAdapter $asyncAdapter
     */
    public function __construct(AsyncAdapter $asyncAdapter)
    {
        $this->asyncAdapter = $asyncAdapter;
    }

    /**
     * Handle
     *
     * @param object $command
     * @param object $next
     *
     * @return PromiseInterface
     */
    public function execute($command, $next) : PromiseInterface
    {
        return $this
            ->asyncAdapter
            ->enqueue($command);
    }
}