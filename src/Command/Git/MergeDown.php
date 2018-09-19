<?php

namespace Tomato\Command\Git;

use Github\Api\Repo;
use Github\Client;
use Tomato\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MergeDown extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('git:merge-down')
            ->setDescription('Merge down the whole codebase using the provided parameters')
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'Source branch')
            ->addOption('destination', 'd', InputOption::VALUE_OPTIONAL, 'Destination branch')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'Project scope: [all]', 'all')
            ->addOption('then', 't', InputOption::VALUE_OPTIONAL, 'Other branch to merge down to');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Client $git */
        $git = $this->container['service:git'];
        $config = $this->container['config'];
        $source = $input->getOption('source');
        $destination = $input->getOption('destination');
        $scope = $input->getOption('scope');
        $then = $input->getOption('then');

        /** @var array $projects */
        $projects = $config['projects'][$scope];

        $start = null === $source ? 0 : array_search($source, $config['branches'], true);
        $end = null === $destination
            ? count($config['branches'])
            : array_search($destination, $config['branches'], true);

        $branches = array_slice($config['branches'], $start, $end);

        if (isset($then)) {
            $branches[] = $then;
        }

        /** @var Repo $repo */
        $repo = $git->api('repo');

        foreach ($projects as $project) {
            foreach ($branches as $key => $branch) {
                if (!isset($branches[$key + 1])) {
                    continue;
                }

                $output->writeln(
                    '<title>Merging in ' . $project . ': ' . $branch . ' to ' . $branches[$key + 1] . '</title>'
                );

                try {
                    $response = $repo->merge(
                        $config['service']['git']['company'],
                        $project,
                        $branches[$key + 1],
                        $branch,
                        'Automated merge from ' . $source . ' to ' . $destination
                    );
                } catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>' . PHP_EOL);
                    print $e->getTraceAsString();
                    continue;
                }

                if (!isset($response['html_url'])) {
                    $output->writeln('<error>Already up to date</error>' . PHP_EOL);
                    continue;
                }

                $output->writeln('<info>' . $response['html_url'] . '</info>' . PHP_EOL);
            }
        }
    }
}
