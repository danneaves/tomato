<?php

namespace Tomato\Service\Provider;

use Github\Client;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tomato\Command\Git\Branch;
use Tomato\Command\Git\Merge;
use Tomato\Command\Git\MergeDown;
use Tomato\Command\Git\Projects;
use Tomato\Command\Git\PullRequest;
use Tomato\Command\Git\Tag;

class GitCommandsProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     * @throws \Github\Exception\InvalidArgumentException
     */
    public function register(Container $pimple)
    {
        $gitCommands = [
            'branch' => Branch::class,
            'merge' => Merge::class,
            'mergeDown' => MergeDown::class,
            'projects' => Projects::class,
            'pullRequest' => PullRequest::class,
            'tag' => Tag::class,
        ];

        foreach ($gitCommands as $command => $class) {
            $pimple['command:git:' . $command] = function () use ($pimple, $class) {
                /** @var Client $gitHubClient */
                $gitHubClient = $pimple['service:git'];
                return new $class($gitHubClient, $pimple['config']);
            };
        }

    }
}
