<?php

/*
 * This file is part of the DriftPHP Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\CommandBus\Tests\Async;

/**
 * Class PostgreSQLAsyncTest.
 */
class PostgreSQLAsyncTest extends AsyncAdapterTest
{
    /**
     * {@inheritdoc}
     */
    protected static function getAsyncConfiguration(): array
    {
        return [
            'postgresql' => [
                'host' => '127.0.0.1',
                'database' => 'commands',
                'user' => 'root',
                'password' => 'root',
                'channel' => 'commands',
            ],
        ];
    }
}
