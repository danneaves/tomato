<?php

namespace Tomato\Command;

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
    protected $container;

    public function __construct(Container $container, $name = null)
    {
        $this->container = $container;

        parent::__construct($name);
    }
}
