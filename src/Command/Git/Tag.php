<?php

namespace Tomato\Command\Git;

use Composer\Semver\Semver;
use Github\Api\GitData;
use Github\Api\Repo;
use Github\Client;
use Github\Exception\ExceptionInterface;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tomato\Command\AbstractCommand;

class Tag extends AbstractCommand
{
    private const TAG_LEVEL_ALPHA = 'alpha';
    private const TAG_LEVEL_BETA = 'beta';
    private const TAG_LEVEL_RC = 'rc';
    private const TAG_LEVEL_STABLE = 'stable';

    private const SOURCE_TAG_SUFFIXES = [
        self::TAG_LEVEL_ALPHA => '-alpha',
        self::TAG_LEVEL_BETA => '-beta',
        self::TAG_LEVEL_RC => '-rc',
    ];

    private const DESTINATION_TAG_SUFFIXES = [
        self::TAG_LEVEL_BETA => '-beta.1',
        self::TAG_LEVEL_RC => '-rc.1',
        self::TAG_LEVEL_STABLE => '',
    ];

    /**
     * @var Client
     */
    protected $git;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string[]
     */
    protected $projects;


    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('git:tag')
            ->setDescription('Control tags for a project or for a group of projects')
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'Source branch')
            ->addOption('name', 'a', InputOption::VALUE_OPTIONAL, 'Name for the tag')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'Project scope: all|root|sub', 'all')
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_OPTIONAL,
                'Delete the tag named in `name`, you may specify a range to remove',
                false
            )
            ->addOption(
                'prune',
                null,
                InputOption::VALUE_OPTIONAL,
                'Prune all tags below latest major versions',
                false
            )
            ->addOption(
                'safe-prune',
                null,
                InputOption::VALUE_OPTIONAL,
                'Only prune non-stable versions',
                true
            )
            ->addOption(
                'beta-release',
                null,
                InputOption::VALUE_OPTIONAL,
                'Construct a beta from the latest alpha in a range of projects',
                false
            )
            ->addOption(
                'rc-release',
                null,
                InputOption::VALUE_OPTIONAL,
                'Construct a release candidate from the latest beta in a range of projects',
                false
            )
            ->addOption(
                'stable-release',
                null,
                InputOption::VALUE_OPTIONAL,
                'Construct a stable release from the latest release candidate in a range of projects',
                false
            )->addOption(
                'projects',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Array of projects, comma separated',
                []
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Github\Exception\InvalidArgumentException
     * @throws ConsoleRuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Client $git */
        $this->git = $this->container['service:git'];
        $this->config = $this->container['config'];
        $this->output = $output;

        $source = $input->getOption('source');
        $name = $input->getOption('name');
        $scope = $input->getOption('scope');
        $prune = $input->getOption('prune');
        $safePrune = (bool)$input->getOption('safe-prune');
        $betaRelease = $input->getOption('beta-release');
        $releaseCandidate = $input->getOption('rc-release');
        $stableRelease = $input->getOption('stable-release');
        $delete = $input->getOption('delete');

        $counts = array_count_values([
            false === $prune ? 0 : 1,
            false === $betaRelease ? 0 : 1,
            false === $releaseCandidate ? 0 : 1,
            false === $stableRelease ? 0 : 1,
            false === $delete ? 0 : 1,
        ]);

        if (1 < $counts[1]) {
            $output->writeln(
                '<error>Only one of --prune --delete --release-candidate --stable-release allowed</error>'
            );
            return;
        }

        $this->projects = [] === $input->getOption('projects')
            ? $this->config['projects'][$scope]
            : $input->getOption('projects');

        if (false !== $prune) {
            $this->prune($safePrune);
        }

        if (false !== $betaRelease) {
            $this->bumpTag(self::TAG_LEVEL_ALPHA, self::TAG_LEVEL_BETA);
        }

        if (false !== $releaseCandidate) {
            $this->bumpTag(self::TAG_LEVEL_BETA, self::TAG_LEVEL_RC);
        }

        if (false !== $stableRelease) {
            $this->bumpTag(self::TAG_LEVEL_RC, self::TAG_LEVEL_STABLE);
        }
    }

    /**
     * @param bool $safe
     * @throws \Github\Exception\InvalidArgumentException
     */
    private function prune($safe = true): void
    {
        /** @var GitData $git */
        $git = $this->git->api('git');

        /** @var GitData\References $refs */
        $refs = $git->references();

        /** @var Repo $repos */
        $repos = $this->git->api('repos');
        $releases = $repos->releases();

        foreach ($this->projects as $project) {
            $tagObjects = $refs->tags($this->config['service']['git']['company'], $project);
            $tags = [];

            foreach ($tagObjects as $tagObject) {
                $uriParts = explode('/', $tagObject['ref']);
                $tags[] = $uriParts[2];
            }

            $this->output->writeln([
                '<info>Found tags:</info>',
                '<info>' . implode(', ', $tags) . '</info>',
            ]);

            // Get the major versions so we can sort
            $majorVersions = [];
            foreach ($tags as $tag) {
                $tag = ltrim($tag, 'v');

                if (!array_key_exists($tag[0], $majorVersions)) {
                    $majorVersions[$tag[0]] = [];
                }

                $majorVersions[$tag[0]][] = $tag;
            }

            // Decide what to keep and what to remove
            $keep = [];
            foreach ($majorVersions as $majorVersion => $versions) {
                $majorVersions[$majorVersion] = Semver::rsort($versions);

                $keep[] = array_shift($majorVersions[$majorVersion]);
            }

            $remove = array_diff($tags, $keep);

            if ($safe) {
                $remove = array_filter($remove, function ($tag) {
                    return preg_match('/' . implode('|', [
                        self::TAG_LEVEL_ALPHA,
                        self::TAG_LEVEL_BETA,
                        self::TAG_LEVEL_RC,
                    ]) . '/', $tag);
                });

                $keep = array_diff($tags, $remove);
            }

            $this->output->writeln([
                '<info>Keeping tags:</info>',
                '<info>' . implode(', ', $keep) . '</info>',
            ]);

            $this->output->writeln([
                '<error>Removing tags:</error>',
                '<error>' . implode(', ', $remove) . '</error>',
            ]);

            foreach ($remove as $item) {
                $refs->remove($this->config['service']['git']['company'], $project, 'tags/' . $item);

                $release = $releases->tag($this->config['service']['git']['company'], $project, $item);
                $releases->remove($this->config['service']['git']['company'], $project, $release['id']);
            }

            $this->output->writeln('<info>Finished pruning ' . $project . '</info>');
        }
    }

    /**
     * @param string $from
     * @param string $to
     * @throws ConsoleRuntimeException
     * @throws \Github\Exception\InvalidArgumentException
     */
    private function bumpTag(string $from, string $to): void
    {
        if (!array_key_exists($from, self::SOURCE_TAG_SUFFIXES)) {
            throw new ConsoleRuntimeException('Unknown tag from: ' . $from);
        }

        if (!array_key_exists($to, self::DESTINATION_TAG_SUFFIXES)) {
            throw new ConsoleRuntimeException('Unknown tag to: ' . $to);
        }

        /** @var GitData $git */
        $git = $this->git->api('git');
        /** @var Repo $repo */
        $repo = $this->git->api('repo');

        $refs = $git->references();
        $tagApi = $git->tags();
        $releases = $repo->releases();

        foreach ($this->projects as $project) {
            try {
                $tagObjects = $refs->tags($this->config['service']['git']['company'], $project);
            } catch (RuntimeException $e) {
                $this->output->writeln(
                    '<error>Could not retrieve tags for ' . $project . ': ' . $e->getMessage() .  '</error>'
                );
                continue;
            }

            $tags = [];
            foreach ($tagObjects as $tagObject) {
                $uriParts = explode('/', $tagObject['ref']);
                $tags[$uriParts[2]] = $tagObject;
            }

            $tagList = Semver::rsort(array_keys($tags));
            $latest = &$tagList[0];
            if (false === strpos($latest, self::SOURCE_TAG_SUFFIXES[$from])) {
                $this->output->writeln(
                    '<info>' . $from . ' tag is not the latest tag for ' . $project .
                    ', found ' . $latest . '</info>'
                );
                continue;
            }

            $next = substr($latest, 0, strpos($latest, self::SOURCE_TAG_SUFFIXES[$from])) .
                self::DESTINATION_TAG_SUFFIXES[$to];
            $commit = $tags[$latest]['object']['sha'];

            $this->output->writeln(
                '<info>Creating ' . $next . ' for ' . $project . ' from ' . $latest . ' [' . $commit . ']</info>'
            );

            $message = 'Automated tag creation from [' . $latest . '] -> [' . $next . ']';

            try {
                $tagApi->create($this->config['service']['git']['company'], $project, [
                    'tag' => $next,
                    'message' => $message,
                    'object' => $commit,
                    'type' => 'commit',
                    'tagger' => [
                        'name' => $this->config['service']['git']['username'],
                        'email' => $this->config['service']['git']['username'],
                        'date' => date(DATE_ATOM),
                    ],
                ]);

                $refs->create($this->config['service']['git']['company'], $project, [
                    'ref' => 'refs/tags/' . $next,
                    'sha' => $commit,
                ]);

                $response = $releases->create($this->config['service']['git']['company'], $project, [
                    'tag_name' => $next,
                    'target_commitish' => $commit,
                    'name' => $next,
                    'body' => $message,
                    'prerelease' => $to !== self::TAG_LEVEL_STABLE,
                ]);
            } catch (ExceptionInterface $e) {
                $this->output->writeln('<error>Unable to create references: ' . $e->getMessage() . '</error>');
                continue;
            }

            if (!isset($response['html_url'])) {
                $this->output->writeln('<error>Url not returned in response: ' . json_encode($response) . '</error>');
                continue;
            }

            $this->output->writeln(
                '<info>Created tag: ' . $response['html_url'] . '</info>'
            );
        }
    }
}
