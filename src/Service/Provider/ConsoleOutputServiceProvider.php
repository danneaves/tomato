<?php

namespace Tomato\Service\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConsoleOutputServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple): void
    {
        $pimple['service:console-output'] = function () use ($pimple) {
            /** @var array $styles */
            $styles = $pimple['config']['service']['console-output'];
            $output = new ConsoleOutput();
            $formatter = $output->getFormatter();

            foreach ($styles as $name => $style) {
                $styleFormatter = new OutputFormatterStyle(
                    $style['fore'] ?? null,
                    $style['back'] ?? null,
                    $style['options'] ?? []
                );
                $formatter->setStyle($name, $styleFormatter);
            }

            return $output;
        };
    }
}
