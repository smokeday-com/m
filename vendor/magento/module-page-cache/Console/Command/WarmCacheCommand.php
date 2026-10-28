<?php
# 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
# "Implement event-driven and prioritized cron Full Page Cache & Cloudflare Edge Cache warmer (page-cache:warm / magento_pagecache_warm)":
# https://github.com/smokeday-com/m/issues/19
# 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
# "Add --all (-a) and --max-time (-t) manual full-site warming mode with rolling-window curl_multi pipelining to page-cache:warm (WarmCacheCommand and Warmer::warm)":
# https://github.com/smokeday-com/m/issues/37
declare(strict_types=1);

namespace Magento\PageCache\Console\Command;

use Magento\PageCache\Model\Cache\Warmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WarmCacheCommand extends Command
{
    private const OPT_ALL = 'all';
    private const OPT_LIMIT = 'limit';
    private const OPT_CONCURRENCY = 'concurrency';
    private const OPT_MAX_LOAD = 'max-load';
    private const OPT_MAX_TIME = 'max-time';
    private const OPT_FORCE = 'force';

    private Warmer $warmer;

    public function __construct(
        Warmer $warmer,
        ?string $name = null
    ) {
        $this->warmer = $warmer;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('page-cache:warm')
            ->setDescription('Warm Built-in Full Page Cache and Cloudflare Edge Cache for invalidated and prioritized canonical URLs')
            ->setDefinition([
                new InputOption(
                    self::OPT_ALL,
                    'a',
                    InputOption::VALUE_NONE,
                    'Warm all uncached URLs in a single run at maximum speed (unlimited URLs, no 35s time cap, no load-average abort, concurrency=' . Warmer::DEFAULT_ALL_CONCURRENCY . ')'
                ),
                new InputOption(
                    self::OPT_LIMIT,
                    'l',
                    InputOption::VALUE_OPTIONAL,
                    'Maximum number of uncached URLs to warm in this run (0 = unlimited; default: ' . Warmer::DEFAULT_LIMIT . ', or 0 with --all)'
                ),
                new InputOption(
                    self::OPT_CONCURRENCY,
                    'c',
                    InputOption::VALUE_OPTIONAL,
                    'Number of concurrent HTTP requests (1..' . Warmer::MAX_CONCURRENCY . '; default: ' . Warmer::DEFAULT_CONCURRENCY . ', or ' . Warmer::DEFAULT_ALL_CONCURRENCY . ' with --all)'
                ),
                new InputOption(
                    self::OPT_MAX_LOAD,
                    'm',
                    InputOption::VALUE_OPTIONAL,
                    'Maximum 1-minute CPU load average allowed before throttling (0 = disabled; default: ' . Warmer::DEFAULT_MAX_LOAD_AVG . ', or 0 with --all)'
                ),
                new InputOption(
                    self::OPT_MAX_TIME,
                    't',
                    InputOption::VALUE_OPTIONAL,
                    'Maximum execution time in seconds before pausing (0 = unlimited; default: ' . Warmer::MAX_RUN_SECONDS . ', or 0 with --all)'
                ),
                new InputOption(
                    self::OPT_FORCE,
                    'f',
                    InputOption::VALUE_NONE,
                    'Warm URLs even if already present in local Built-in FPC (e.g. to warm Cloudflare Edge)'
                ),
            ]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = (bool)$input->getOption(self::OPT_ALL);
        $rawLimit = $input->getOption(self::OPT_LIMIT);
        $rawConcurrency = $input->getOption(self::OPT_CONCURRENCY);
        $rawMaxLoad = $input->getOption(self::OPT_MAX_LOAD);
        $rawMaxTime = $input->getOption(self::OPT_MAX_TIME);
        $force = (bool)$input->getOption(self::OPT_FORCE);

        $limit = $rawLimit !== null
            ? (int)$rawLimit
            : ($all ? 0 : Warmer::DEFAULT_LIMIT);
        $concurrency = $rawConcurrency !== null
            ? max(1, (int)$rawConcurrency)
            : ($all ? Warmer::DEFAULT_ALL_CONCURRENCY : Warmer::DEFAULT_CONCURRENCY);
        $maxLoad = $rawMaxLoad !== null
            ? (float)$rawMaxLoad
            : ($all ? 0.0 : Warmer::DEFAULT_MAX_LOAD_AVG);
        $maxTime = $rawMaxTime !== null
            ? (float)$rawMaxTime
            : ($all ? 0.0 : Warmer::MAX_RUN_SECONDS);

        $result = $this->warmer->warm($limit, $concurrency, $maxLoad, $force, $maxTime);
        $output->writeln((string)json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
