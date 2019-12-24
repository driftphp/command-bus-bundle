<?php

namespace Drift\Bus\Adapter;

use Drift\Bus\Bus;
use League\Tactician\CommandBus;
use React\Promise\PromiseInterface;

/**
 * Class TacticianBus
 */
class TacticianBus implements Bus
{
    /**
     * @var CommandBus
     */
    private $bus;

    /**
     * TacticianBus constructor.
     *
     * @param CommandBus $bus
     */
    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @inheritDoc
     */
    public function handle($object): PromiseInterface
    {
        return $this
            ->bus
            ->handle($object);
    }
}