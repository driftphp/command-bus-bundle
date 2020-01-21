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

use Drift\CommandBus\Async\InMemoryAdapter;

/**
 * Class FilesystemAsyncAdapterTest.
 */
class FilesystemAsyncAdapterTest extends AsyncAdapterTest
{
    /**
     * {@inheritdoc}
     */
    protected static function getAsyncConfiguration(): array
    {
        return [
            'adapter' => 'in_memory',
            'in_memory' => [],
        ];
    }

    /**
     * Test that inmemory async adapter is public.
     */
    public function testAdapterIsPublic()
    {
        $this->assertInstanceOf(InMemoryAdapter::class, $this->get(InMemoryAdapter::class));
    }
}
