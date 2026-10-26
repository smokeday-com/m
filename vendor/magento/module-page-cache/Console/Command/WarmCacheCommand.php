<?php
# 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
# "Implement event-driven and prioritized cron Full Page Cache & Cloudflare Edge Cache warmer (page-cache:warm / magento_pagecache_warm)":
# https://github.com/smokeday-com/m/issues/19
declare(strict_types=1);

namespace Magento\PageCache\Console\Command;

use Magento\PageCache\Model\Cache\Warmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WarmCacheCommand extends Command
{
    private const OPT_LIMIT = 'limit';
    private const OPT_CONCURRENCY = 'concurrency';
    private const OPT_MAX_LOAD = 'max-load';
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
                    self::OPT_LIMIT,
                    'l',
                    InputOption::VALUE_OPTIONAL,
                    'Maximum number of uncached URLs to warm in this run',
                    (string)Warmer::DEFAULT_LIMIT
                ),
                new InputOption(
                    self::OPT_CONCURRENCY,
                    'c',
                    InputOption::VALUE_OPTIONAL,
                    'Number of concurrent HTTP requests',
                    (string)Warmer::DEFAULT_CONCURRENCY
                ),
                new InputOption(
                    self::OPT_MAX_LOAD,
                    'm',
                    InputOption::VALUE_OPTIONAL,
                    'Maximum 1-minute CPU load average allowed before throttling',
                    (string)Warmer::DEFAULT_MAX_LOAD_AVG
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
        $limit = max(1, (int)$input->getOption(self::OPT_LIMIT));
        $concurrency = max(1, (int)$input->getOption(self::OPT_CONCURRENCY));
        $maxLoad = (float)$input->getOption(self::OPT_MAX_LOAD);
        $force = (bool)$input->getOption(self::OPT_FORCE);

        $result = $this->warmer->warm($limit, $concurrency, $maxLoad, $force);
        $output->writeln((string)json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
