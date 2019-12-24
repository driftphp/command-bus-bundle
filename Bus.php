<?php


namespace Drift\Bus;

use React\Promise\PromiseInterface;

/**
 * Interface Bus
 */
interface Bus
{
    /**
     * Handle
     *
     * @param Object $object
     * @param
     *
     * @return PromiseInterface
     */
    public function handle($object) : PromiseInterface;
}