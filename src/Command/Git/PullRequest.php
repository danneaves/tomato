<?php

namespace Tomato\Command\Git;

use Github\Api\PullRequest\ReviewRequest;
use Github\Client;
use Tomato\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequest extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('git:pull-request')
            ->setDescription('Creates a new Pull Request using the provided parameters')
            ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'Source branch')
            ->addOption('destination', 'd', InputOption::VALUE_REQUIRED, 'Destination branch')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'Project scope: [all]', 'all')
            ->addOption(
                'projects',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Array of projects, comma separated',
                []
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Client $git */
        $git = $this->container['service:git'];
        $config = $this->container['config'];
        $source = $input->getOption('source');
        $destination = $input->getOption('destination');
        $scope = $input->getOption('scope');

        /** @var array $projects */
        $projects = [] === $input->getOption('projects')
            ? $config['projects'][$scope]
            : $input->getOption('projects');

        foreach ($projects as $project) {
            $output->writeln(
                '<title>New Pull Request: ' . $project . ': ' . $source . ' >> ' . $destination . '</title>'
            );

            /** @var ReviewRequest $request */
            $request = $git->api('pull_request');

            try {
                /** @var array $response */
                $response = $request->create($config['service']['git']['company'], $project, [
                    'base'  => $destination,
                    'head'  => $source,
                    'title' => 'Auto generated request: ' . $source . ' >> ' . $destination,
                    'body'  => ''
                ], []);
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>' . PHP_EOL);
                continue;
            }

            $output->writeln('<info>' . $response['html_url'] . '</info>' . PHP_EOL);
        }
    }
}
