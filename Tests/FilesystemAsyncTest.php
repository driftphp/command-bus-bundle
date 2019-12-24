<?php


namespace Drift\Bus\Tests;


/**
 * Class FilesystemAsyncAdapterTest
 */
class FilesystemAsyncAdapterTest extends AsyncAdapterTest
{

    /**
     * @inheritDoc
     */
    static protected function getAsyncConfiguration(): array
    {
        return [
            'filesystem' => [
                'file' => '/tmp/file.sock'
            ]
        ];
    }
}