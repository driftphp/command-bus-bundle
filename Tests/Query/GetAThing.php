<?php

namespace Drift\Bus\Tests\Query;

/**
 * Class DoAThing
 */
class GetAThing
{
    /**
     * @var string
     */
    private $thing;

    /**
     * DoAThing constructor.
     *
     * @param string $thing
     */
    public function __construct(string $thing)
    {
        $this->thing = $thing;
    }

    /**
     * @return string
     */
    public function getThing(): string
    {
        return $this->thing;
    }
}