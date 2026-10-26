<?php
# 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
# "Implement event-driven and prioritized cron Full Page Cache & Cloudflare Edge Cache warmer (page-cache:warm / magento_pagecache_warm)":
# https://github.com/smokeday-com/m/issues/19
declare(strict_types=1);

namespace Magento\PageCache\Model\Cron;

use Magento\PageCache\Model\Cache\Warmer;

class WarmCache
{
    private Warmer $warmer;

    public function __construct(Warmer $warmer)
    {
        $this->warmer = $warmer;
    }

    /**
     * Warm invalidated and highest-priority uncached pages in batches every 5 minutes
     *
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        return $this->warmer->warm(
            Warmer::DEFAULT_LIMIT,
            Warmer::DEFAULT_CONCURRENCY,
            Warmer::DEFAULT_MAX_LOAD_AVG,
            false
        );
    }
}
