<?php

namespace Tomato\Service\Provider;

use League\CLImate\CLImate;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class CliServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $pimple['service:cli'] = function () {
            return new CLImate();
        };
    }
}
