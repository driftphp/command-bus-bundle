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

namespace Drift\CommandBus\DependencyInjection\CompilerPass;

use Drift\AMQP\DependencyInjection\CompilerPass\AMQPCompilerPass;
use Drift\CommandBus\Async\AMQPAdapter;
use Drift\CommandBus\Async\AsyncAdapter;
use Drift\CommandBus\Async\InMemoryAdapter;
use Drift\CommandBus\Async\PostgreSQLAdapter;
use Drift\CommandBus\Async\RedisAdapter;
use Drift\CommandBus\Bus\AsyncCommandBus;
use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Bus\InlineCommandBus;
use Drift\CommandBus\Bus\QueryBus;
use Drift\CommandBus\Console\CommandConsumerCommand;
use Drift\CommandBus\Console\DebugCommandBusCommand;
use Drift\CommandBus\Console\InfrastructureCheckCommand;
use Drift\CommandBus\Console\InfrastructureCreateCommand;
use Drift\CommandBus\Console\InfrastructureDropCommand;
use Drift\CommandBus\Exception\InvalidMiddlewareException;
use Drift\CommandBus\Middleware\AsyncMiddleware;
use Drift\CommandBus\Middleware\HandlerMiddleware;
use Drift\CommandBus\Middleware\Middleware;
use Drift\EventBus\Subscriber\EventBusSubscriber;
use Drift\Postgresql\DependencyInjection\CompilerPass\PostgresqlCompilerPass;
use Drift\Redis\DependencyInjection\CompilerPass\RedisCompilerPass;
use Exception;
use React\EventLoop\LoopInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class BusCompilerPass.
 */
class BusCompilerPass implements CompilerPassInterface
{
    const BUS_TYPE_COMMAND = 'command';
    const BUS_TYPE_QUERY = 'query';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $asyncEnabled = static::createAsyncMiddleware($container);
        static:: createBuses(
            $container,
            $asyncEnabled,
            $container->getParameter('bus.query_bus.distribution'),
            $container->getParameter('bus.query_bus.middlewares'),
            $container->getParameter('bus.command_bus.distribution'),
            $container->getParameter('bus.command_bus.middlewares')
        );

        if ($asyncEnabled) {
            static::createAsyncBuses($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param bool             $asyncBus
     * @param string           $queryBusDistribution
     * @param array            $queryMiddlewares
     * @param string           $commandBusDistribution
     * @param array            $commandMiddlewares
     */
    public static function createBuses(
        ContainerBuilder $container,
        bool $asyncBus,
        string $queryBusDistribution,
        array $queryMiddlewares,
        string $commandBusDistribution,
        array $commandMiddlewares
    ) {
        static::createQueryHandlerMiddleware($container);
        static::createCommandHandlerMiddleware($container);
        static::createQueryBus($container, $queryBusDistribution, $queryMiddlewares);
        static::createCommandBus($container, $commandBusDistribution, $commandMiddlewares, $asyncBus);
        static::createInlineCommandBus($container, $commandBusDistribution, $commandMiddlewares);
        static::createBusDebugger($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public static function createAsyncBuses(ContainerBuilder $container)
    {
        static::createCommandConsumer($container);
        static::createInfrastructureCreateCommand($container);
        static::createInfrastructureDropCommand($container);
        static::createInfrastructureCheckCommand($container);
    }

    /**
     * @param ContainerBuilder $container
     * @param array|null       $adapter
     *
     * @return bool
     */
    public static function createAsyncMiddleware(
        ContainerBuilder $container,
        array $adapter = null
    ): bool {
        if (is_array($adapter)) {
            $adapterType = $adapter['type'];
        } else {
            $asyncAdapters = $container->getParameter('bus.command_bus.async_adapter');

            if (
                empty($asyncAdapters) ||
                (
                    isset($asyncAdapters['adapter']) &&
                    !isset($asyncAdapters[$asyncAdapters['adapter']])
                )
            ) {
                return false;
            }

            $adapterType = $asyncAdapters['adapter'] ?? array_key_first($asyncAdapters);
            $adapterType = $container->resolveEnvPlaceholders($adapterType, true);
            $adapter = $asyncAdapters[$adapterType];
        }

        switch ($adapterType) {
            case 'amqp':
                static::createAMQPAsyncAdapter($container, $adapter);
                break;
            case 'in_memory':
                static::createInMemoryAsyncAdapter($container);
                break;
            case 'redis':
                static::createRedisAsyncAdapter($container, $adapter);
                break;
            case 'postgresql':
                static::createPostgreSQLAsyncAdapter($container, $adapter);
                break;
            default:
                throw new Exception('Wrong adapter. Please use one of this list: amqp, in_memory, redis, postgresql.');
        }

        $container->setDefinition(AsyncMiddleware::class,
            new Definition(
                AsyncMiddleware::class, [
                    new Reference(AsyncAdapter::class),
                ]
            )
        );

        return true;
    }

    /**
     * Create query handler middleware.
     *
     * @param ContainerBuilder $container
     *
     * @throws InvalidMiddlewareException
     * @throws ReflectionException
     */
    private static function createQueryHandlerMiddleware(ContainerBuilder $container)
    {
        $handlerMiddlewareId = 'bus.query_bus.handler_middleware';
        $handlerMiddleware = new Definition(HandlerMiddleware::class);
        $handlerMap = static::createHandlersMap($container, 'query_handler');

        foreach ($handlerMap as $command => list($reference, $method)) {
            $handlerMiddleware->addMethodCall('addHandler', [
                $command, $reference, $method,
            ]);
        }

        $container->setDefinition($handlerMiddlewareId, $handlerMiddleware);
    }

    /**
     * Create command handler middleware.
     *
     * @param ContainerBuilder $container
     *
     * @throws InvalidMiddlewareException
     * @throws ReflectionException
     */
    private static function createCommandHandlerMiddleware(ContainerBuilder $container)
    {
        $handlerMiddlewareId = 'bus.command_bus.handler_middleware';
        $handlerMiddleware = new Definition(HandlerMiddleware::class);
        $handlerMap = static::createHandlersMap($container, 'command_handler');

        foreach ($handlerMap as $command => list($reference, $method)) {
            $handlerMiddleware->addMethodCall('addHandler', [
                $command, $reference, $method,
            ]);
        }

        $container->setDefinition($handlerMiddlewareId, $handlerMiddleware);
    }

    /**
     * Create query bus.
     *
     * @param ContainerBuilder $container
     * @param string           $queryBusDistribution
     * @param array            $queryMiddlewares
     */
    private static function createQueryBus(
        ContainerBuilder $container,
        string $queryBusDistribution,
        array $queryMiddlewares
    ) {
        $container->setDefinition('drift.query_bus', (new Definition(
            QueryBus::class, [
                new Reference(LoopInterface::class),
                static::createMiddlewaresArray(
                    $container,
                    $queryMiddlewares,
                    false,
                    self::BUS_TYPE_QUERY
                ),
                $queryBusDistribution,
            ]
        ))
            ->addTag('preload')
            ->setLazy(true)
        );

        $container->setAlias(QueryBus::class, 'drift.query_bus');
    }

    /**
     * Create command bus.
     *
     * @param ContainerBuilder $container
     * @param string           $commandBusDistribution
     * @param array            $commandMiddlewares
     * @param bool             $asyncBus
     */
    private static function createCommandBus(
        ContainerBuilder $container,
        string $commandBusDistribution,
        array $commandMiddlewares,
        bool $asyncBus
    ) {
        $class = $asyncBus ? AsyncCommandBus::class : CommandBus::class;
        $container->setDefinition('drift.command_bus', (new Definition(
            $class, [
                new Reference(LoopInterface::class),
                static::createMiddlewaresArray(
                    $container,
                    $commandMiddlewares,
                    $asyncBus,
                    self::BUS_TYPE_COMMAND
                ),
                $commandBusDistribution,
            ]
        ))
            ->addTag('preload')
            ->setLazy(true)
        );

        $container->setAlias(CommandBus::class, 'drift.command_bus');

        if ($asyncBus) {
            $container->setAlias(AsyncCommandBus::class, 'drift.command_bus');
        }
    }

    /**
     * Create command bus.
     *
     * @param ContainerBuilder $container
     * @param string           $commandBusDistribution
     * @param array            $commandMiddlewares
     */
    private static function createInlineCommandBus(
        ContainerBuilder $container,
        string $commandBusDistribution,
        array $commandMiddlewares
    ) {
        $container->setDefinition('drift.inline_command_bus', (new Definition(
            InlineCommandBus::class, [
                new Reference(LoopInterface::class),
                static::createMiddlewaresArray(
                    $container,
                    $commandMiddlewares,
                    false,
                    self::BUS_TYPE_COMMAND
                ),
                $commandBusDistribution,
            ]
        ))
            ->addTag('preload')
            ->setLazy(true)
        );

        $container->setAlias(InlineCommandBus::class, 'drift.inline_command_bus');
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $definedMiddlewares
     * @param bool             $isAsync
     * @param string           $type
     *
     * @return array
     */
    private static function createMiddlewaresArray(
        ContainerBuilder $container,
        array $definedMiddlewares,
        bool $isAsync,
        string $type
    ) {
        $asyncFound = array_search('@async', $definedMiddlewares);
        $middlewares = [];

        if (!$asyncFound && $isAsync) {
            $middlewares[] = new Reference(AsyncMiddleware::class);

            return $middlewares;
        }

        foreach ($definedMiddlewares as $middleware) {
            if ('@async' === $middleware) {
                if (
                    true === $isAsync &&
                    self::BUS_TYPE_COMMAND === $type
                ) {
                    $middlewares[] = new Reference(AsyncMiddleware::class);

                    return $middlewares;
                }

                continue;
            }

            $method = 'execute';
            $splitted = explode('::', $middleware, 2);
            if (2 === count($splitted)) {
                $middleware = $splitted[0];
                $method = $splitted[1];
            }

            if (!$container->has($middleware)) {
                $container->setDefinition($middleware, new Definition($middleware));
            }

            $middlewareWrapperName = "{$middleware}\\Wrapper";
            $middlewareWrapper = new Definition(Middleware::class, [
                new Reference($middleware),
                $method,
            ]);

            $container->setDefinition($middlewareWrapperName, $middlewareWrapper);
            $middlewares[] = new Reference($middlewareWrapperName);
        }

        $handlerMiddlewareId = "bus.{$type}_bus.handler_middleware";
        $middlewares[] = new Reference($handlerMiddlewareId);

        return $middlewares;
    }

    /**
     * Create handlers map.
     *
     * @param ContainerBuilder $container
     * @param string           $tag
     *
     * @return array
     *
     * @throws InvalidMiddlewareException
     * @throws ReflectionException
     */
    private static function createHandlersMap(
        ContainerBuilder $container,
        string $tag
    ): array {
        $handlers = $container->findTaggedServiceIds($tag);
        $handlersMap = [];
        foreach ($handlers as $serviceId => $handler) {
            $serviceNamespace = $container->getDefinition($serviceId)->getClass();
            $reflectionClass = new ReflectionClass($serviceNamespace);

            try {
                $reflectionMethod = $reflectionClass->getMethod($handler[0]['method'] ?? 'handle');
            } catch (ReflectionException $exception) {
                throw new InvalidMiddlewareException();
            }

            $reflectionParameter = $reflectionMethod->getParameters()[0];
            $handlersMap[$reflectionParameter->getType()->getName()] = [new Reference($serviceId), $reflectionMethod->getName()];
        }

        return $handlersMap;
    }

    /**
     * Console Commands.
     */

    /**
     * Create command consumer.
     *
     * @param ContainerBuilder $container
     */
    private static function createCommandConsumer(ContainerBuilder $container)
    {
        $consumer = new Definition(CommandConsumerCommand::class, [
            new Reference(AsyncAdapter::class),
            new Reference('drift.inline_command_bus'),
            new Reference(EventBusSubscriber::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

        $consumer->addTag('console.command', [
            'command' => 'command-bus:consume-commands',
        ]);

        $container->setDefinition(CommandConsumerCommand::class, $consumer);
    }

    /**
     * Create command consumer.
     *
     * @param ContainerBuilder $container
     */
    private static function createBusDebugger(ContainerBuilder $container)
    {
        $consumer = new Definition(DebugCommandBusCommand::class, [
            new Reference('drift.command_bus'),
            new Reference('drift.inline_command_bus'),
            new Reference('drift.query_bus'),
        ]);

        $consumer->addTag('console.command', [
            'command' => 'debug:command-bus',
        ]);

        $container->setDefinition(DebugCommandBusCommand::class, $consumer);
    }

    /**
     * Create infrastructure creator.
     *
     * @param ContainerBuilder $container
     */
    private static function createInfrastructureCreateCommand(ContainerBuilder $container)
    {
        $consumer = new Definition(InfrastructureCreateCommand::class, [
            new Reference(AsyncAdapter::class),
            new Reference('reactphp.event_loop'),
        ]);

        $consumer->addTag('console.command', [
            'command' => 'command-bus:infra:create',
        ]);

        $container->setDefinition(InfrastructureCreateCommand::class, $consumer);
    }

    /**
     * Create infrastructure dropper.
     *
     * @param ContainerBuilder $container
     */
    private static function createInfrastructureDropCommand(ContainerBuilder $container)
    {
        $consumer = new Definition(InfrastructureDropCommand::class, [
            new Reference(AsyncAdapter::class),
            new Reference('reactphp.event_loop'),
        ]);

        $consumer->addTag('console.command', [
            'command' => 'command-bus:infra:drop',
        ]);

        $container->setDefinition(InfrastructureDropCommand::class, $consumer);
    }

    /**
     * Create infrastructure checker.
     *
     * @param ContainerBuilder $container
     */
    private static function createInfrastructureCheckCommand(ContainerBuilder $container)
    {
        $consumer = new Definition(InfrastructureCheckCommand::class, [
            new Reference(AsyncAdapter::class),
            new Reference('reactphp.event_loop'),
        ]);

        $consumer->addTag('console.command', [
            'command' => 'command-bus:infra:check',
        ]);

        $container->setDefinition(InfrastructureCheckCommand::class, $consumer);
    }

    /**
     * ADAPTERS.
     */

    /**
     * Create in_memory async adapter.
     *
     * @param ContainerBuilder $container
     */
    private static function createInMemoryAsyncAdapter(ContainerBuilder $container)
    {
        $container->setDefinition(
            AsyncAdapter::class,
            new Definition(InMemoryAdapter::class, [
                new Reference('reactphp.event_loop'),
            ])
        );

        $container->setAlias(InMemoryAdapter::class, AsyncAdapter::class)->setPublic(true);
    }

    /**
     * Create redis async adapter.
     *
     * @param ContainerBuilder $container
     * @param array            $adapter
     */
    private static function createRedisAsyncAdapter(
        ContainerBuilder $container,
        array $adapter
    ) {
        $adapter['preload'] = true;
        RedisCompilerPass::createClient($container, 'command_bus', $adapter);

        $container->setDefinition(
            AsyncAdapter::class,
            (
                new Definition(RedisAdapter::class, [
                    new Reference('redis.command_bus_client'),
                    new Reference('reactphp.event_loop'),
                    $adapter['key'] ?? 'commands',
                ])
            )->setLazy(true)
        );
    }

    /**
     * Create postgresql async adapter.
     *
     * @param ContainerBuilder $container
     * @param array            $adapter
     */
    private static function createPostgreSQLAsyncAdapter(
        ContainerBuilder $container,
        array $adapter
    ) {
        $channel = $adapter['channel'] ?? 'commands';
        unset($adapter['channel']);

        PostgresqlCompilerPass::createclient($container, 'command_bus', $adapter);

        $container->setDefinition(
            AsyncAdapter::class,
            (
                new Definition(PostgreSQLAdapter::class, [
                    new Reference('postgresql.command_bus_client'),
                    new Reference('reactphp.event_loop'),
                    $channel,
                ])
            )->setLazy(true)
        );
    }

    /**
     * Create amqp async adapter.
     *
     * @param ContainerBuilder $container
     * @param array            $adapter
     */
    private static function createAMQPAsyncAdapter(
        ContainerBuilder $container,
        array $adapter
    ) {
        $adapter['preload'] = true;
        AMQPCompilerPass::registerClient($container, 'command_bus', $adapter);

        $container->setDefinition(
            AsyncAdapter::class,
            (
                new Definition(AMQPAdapter::class, [
                    new Reference('amqp.command_bus_channel'),
                    new Reference('reactphp.event_loop'),
                    $adapter['queue'] ?? 'commands',
                ])
            )->setLazy(true)
        );
    }
}
