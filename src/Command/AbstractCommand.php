<?php

namespace Tomato\Command;

use Github\Client;
use Pimple\Container;
use Symfony\Component\Console\Command\Command;

/**
 * Class AbstractCommand
 * @package Command
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var Container
     */
    protected $gitHubClient;

    /**
     * @var array
     */
    protected $config;

    /**
     * AbstractCommand constructor.
     * @param Client $gitHubClient
     * @param array $config
     * @param string|null $name
     */
    public function __construct(Client $gitHubClient, array $config, string $name = null)
    {
        $this->gitHubClient = $gitHubClient;
        $this->config = $config;
        parent::__construct($name);
    }
}
