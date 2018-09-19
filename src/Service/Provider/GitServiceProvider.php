<?php

namespace Tomato\Service\Provider;

use Github\Client;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class GitServiceProvider implements ServiceProviderInterface
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
        $pimple['service:git'] = function () use ($pimple) {
            $config = $pimple['config']['service']['git'];
            $client = new Client();
            $client->authenticate($config['username'], $config['password'], Client::AUTH_HTTP_PASSWORD);

            return $client;
        };
    }
}
