<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PageCache\Model\Cache;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

# 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
# "Implement event-driven and prioritized catalog/pagination Full Page Cache Warmer (Magento\PageCache\Model\Cache\Warmer, CLI command page-cache:warm, and cron job magento_pagecache_warm)":
# https://github.com/smokeday-com/m/issues/19
class Warmer
{
    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Calibrate FPC Warmer load threshold (15.0 / 25.0 for priority queue), run every minute, prioritize Category Page 1 URLs, and guard Type::purgeCloudflare() / Warmer::enqueueTags() against empty tag arrays":
    # https://github.com/smokeday-com/m/issues/26
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Ensure Warmer::warm() flushes Cloudflare purge queue first, always warms priority invalidated URLs on Cloudflare Edge, and enforces a 35s time budget to prevent cron lock collisions":
    # https://github.com/smokeday-com/m/issues/34
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Add --all (-a) and --max-time (-t) manual full-site warming mode with rolling-window curl_multi pipelining to page-cache:warm (WarmCacheCommand and Warmer::warm)":
    # https://github.com/smokeday-com/m/issues/37
    public const DEFAULT_LIMIT = 12;
    public const DEFAULT_CONCURRENCY = 1;
    public const DEFAULT_ALL_CONCURRENCY = 8;
    public const MAX_CONCURRENCY = 16;
    public const DEFAULT_MAX_LOAD_AVG = 15.0;
    public const PRIORITY_QUEUE_MAX_LOAD_AVG = 25.0;
    public const MAX_RUN_SECONDS = 35.0;

    private const STORE_ID = 1;
    private const QUEUE_REL_PATH = '/var/page_cache_warm_queue.json';
    private const CATALOG_CACHE_REL_PATH = '/var/page_cache_warm_catalog.json';
    private const CATALOG_CACHE_TTL = 3600;
    private const LOCK_REL_PATH = '/var/page_cache_warmer.lock';
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Track Cloudflare Edge warm state (var/page_cache_edge_warmed.json) by FPC mtime and decouple --all from --force so subsequent page-cache:warm --all runs skip already Edge-warmed URLs in <0.3s":
    # https://github.com/smokeday-com/m/issues/40
    private const EDGE_WARMED_REL_PATH = '/var/page_cache_edge_warmed.json';
    private const EDGE_WARMED_FLUSH_INTERVAL = 100;
    private const EDGE_WARMED_MAX_ENTRIES = 10000;
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Prevent App\Cache::clean() from wiping Redis DB 1 (page_cache) during setup:di:compile and add Origin-direct fallback to Warmer when Cloudflare Edge returns HIT with in_fpc=false":
    # https://github.com/smokeday-com/m/issues/42
    private const EDGE_NEGATIVE_TTL_SECONDS = 3600;
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Remove >90 tags-to-FPC escalation in Magento\PageCache\Model\Cache\Type::purgeCloudflare() and add stack-trace audit logging to var/log/cloudflare_purge.log":
    # https://github.com/smokeday-com/m/issues/31
    private const MAX_QUEUE_TAGS = 5000;
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Prevent Nexcess ModSecurity HTTP 429 greylisting in Warmer::executeRollingWarm() via synthetic X-Forwarded-For IP rotation + 429 retry/backoff, and restore CF-Connecting-IP as REMOTE_ADDR in app/bootstrap.php":
    # https://github.com/smokeday-com/m/issues/38
    private const MAX_429_RETRIES = 3;
    private const SYNTHETIC_IP_POOL_SIZE = 10762;

    /**
     * @var array<string, bool>
     */
    private static array $enqueuedTags = [];

    /**
     * @var bool
     */
    private static bool $enqueuedAll = false;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Type
     */
    private Type $fullPageCache;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @param ResourceConnection|null $resource
     * @param StoreManagerInterface|null $storeManager
     * @param ScopeConfigInterface|null $scopeConfig
     * @param Config|null $config
     * @param Type|null $fullPageCache
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        ?ResourceConnection $resource = null,
        ?StoreManagerInterface $storeManager = null,
        ?ScopeConfigInterface $scopeConfig = null,
        ?Config $config = null,
        ?Type $fullPageCache = null,
        ?SerializerInterface $serializer = null
    ) {
        $om = ObjectManager::getInstance();
        $this->resource = $resource ?? $om->get(ResourceConnection::class);
        $this->storeManager = $storeManager ?? $om->get(StoreManagerInterface::class);
        $this->scopeConfig = $scopeConfig ?? $om->get(ScopeConfigInterface::class);
        $this->config = $config ?? $om->get(Config::class);
        $this->fullPageCache = $fullPageCache ?? $om->get(Type::class);
        $this->serializer = $serializer ?? $om->get(SerializerInterface::class);
    }

    /**
     * Record invalidated entity cache tags into a lock-safe queue file for priority re-warming.
     *
     * @param string $mode
     * @param array $tags
     * @return void
     */
    public static function enqueueTags(string $mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = []): void
    {
        if (self::$enqueuedAll || !defined('BP')) {
            return;
        }
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Calibrate FPC Warmer load threshold (15.0 / 25.0 for priority queue), run every minute, prioritize Category Page 1 URLs, and guard Type::purgeCloudflare() / Warmer::enqueueTags() against empty tag arrays":
        # https://github.com/smokeday-com/m/issues/26
        if ($mode !== \Zend_Cache::CLEANING_MODE_ALL && empty($tags)) {
            return;
        }
        try {
            $newTags = [];
            $isFlushAll = ($mode === \Zend_Cache::CLEANING_MODE_ALL);
            if ($isFlushAll) {
                self::$enqueuedAll = true;
            } else {
                foreach ($tags as $tag) {
                    $tagStr = strtolower(trim((string)$tag));
                    if ($tagStr === '') {
                        continue;
                    }
                    if ($tagStr === 'fpc' || $tagStr === 'mage') {
                        $isFlushAll = true;
                        self::$enqueuedAll = true;
                        break;
                    }
                    if (
                        preg_match('/^(cat_p_\d+|cat_c_\d+|cat_c_p_\d+|cms_p_\d+|cat_p|cat_c)$/', $tagStr)
                        && !isset(self::$enqueuedTags[$tagStr])
                    ) {
                        self::$enqueuedTags[$tagStr] = true;
                        $newTags[] = $tagStr;
                    }
                }
            }
            if (!$isFlushAll && empty($newTags)) {
                return;
            }

            $queueFile = self::getQueueFilePath();
            $dir = dirname($queueFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $fp = @fopen($queueFile, 'c+');
            if ($fp === false) {
                return;
            }
            if (@flock($fp, LOCK_EX)) {
                $raw = stream_get_contents($fp);
                $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
                if (!is_array($data)) {
                    $data = ['flush_all' => false, 'tags' => [], 'updated_at' => time()];
                }
                if ($isFlushAll) {
                    $data['flush_all'] = true;
                }
                $existingTags = isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : [];
                $merged = array_values(array_unique(array_merge($existingTags, $newTags)));
                # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
                # "Remove >90 tags-to-FPC escalation in Magento\PageCache\Model\Cache\Type::purgeCloudflare() and add stack-trace audit logging to var/log/cloudflare_purge.log":
                # https://github.com/smokeday-com/m/issues/31
                if (count($merged) > self::MAX_QUEUE_TAGS) {
                    $merged = array_slice($merged, 0, self::MAX_QUEUE_TAGS);
                }
                $data['tags'] = $merged;
                $data['updated_at'] = time();

                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, (string)json_encode($data, JSON_UNESCAPED_SLASHES));
                fflush($fp);
                @flock($fp, LOCK_UN);
            }
            fclose($fp);
        } catch (\Throwable $e) {
            // Never interrupt cache cleaning flow
        }
    }

    /**
     * Warm missing canonical pages in Built-in FPC (Redis DB 1 / var/page_cache) and Cloudflare Edge.
     *
     * @param int $limit
     * @param int $concurrency
     * @param float $maxLoadAvg
     * @param bool $force
     * @param float $maxRunSeconds
     * @return array<string, mixed>
     */
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Add --all (-a) and --max-time (-t) manual full-site warming mode with rolling-window curl_multi pipelining to page-cache:warm (WarmCacheCommand and Warmer::warm)":
    # https://github.com/smokeday-com/m/issues/37
    public function warm(
        int $limit = self::DEFAULT_LIMIT,
        int $concurrency = self::DEFAULT_CONCURRENCY,
        float $maxLoadAvg = self::DEFAULT_MAX_LOAD_AVG,
        bool $force = false,
        float $maxRunSeconds = self::MAX_RUN_SECONDS
    ): array {
        $startTime = microtime(true);
        $limit = $limit <= 0 ? PHP_INT_MAX : $limit;
        $concurrency = max(1, min(self::MAX_CONCURRENCY, $concurrency));
        # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Ensure Warmer::warm() flushes Cloudflare purge queue first, always warms priority invalidated URLs on Cloudflare Edge, and enforces a 35s time budget to prevent cron lock collisions":
        # https://github.com/smokeday-com/m/issues/34
        $cfPurgeSummary = Type::flushCloudflareQueue();
        $currentLoad = $this->getCurrentLoadAvg();
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Calibrate FPC Warmer load threshold (15.0 / 25.0 for priority queue), run every minute, prioritize Category Page 1 URLs, and guard Type::purgeCloudflare() / Warmer::enqueueTags() against empty tag arrays":
        # https://github.com/smokeday-com/m/issues/26
        $hasPendingQueue = $this->hasQueuedTags();
        $effectiveMaxLoadAvg = $maxLoadAvg <= 0.0
            ? 0.0
            : (($hasPendingQueue && $maxLoadAvg <= self::DEFAULT_MAX_LOAD_AVG)
                ? self::PRIORITY_QUEUE_MAX_LOAD_AVG
                : $maxLoadAvg);

        $summary = [
            'status' => 'ok',
            'load_avg_1m' => $currentLoad,
            'max_load_avg' => $effectiveMaxLoadAvg,
            'max_run_sec' => $maxRunSeconds,
            'concurrency' => $concurrency,
            'cloudflare_purge' => $cfPurgeSummary,
            'queued_tags_count' => 0,
            'queued_urls_count' => 0,
            'catalog_urls_total' => 0,
            'checked_in_fpc' => 0,
            'skipped_already_cached' => 0,
            'selected_to_warm' => 0,
            'warmed_count' => 0,
            'verified_in_fpc' => 0,
            'elapsed_sec' => 0.0,
            'warmed_urls' => [],
        ];

        if ($this->config->getType() !== Config::BUILT_IN || !$this->config->isEnabled()) {
            $summary['status'] = 'skipped_fpc_disabled';
            $summary['elapsed_sec'] = round(microtime(true) - $startTime, 3);
            return $summary;
        }

        if ($effectiveMaxLoadAvg > 0.0 && $currentLoad !== null && $currentLoad >= $effectiveMaxLoadAvg) {
            $summary['status'] = 'skipped_high_load';
            $summary['elapsed_sec'] = round(microtime(true) - $startTime, 3);
            return $summary;
        }

        $lockFile = str_replace('\\', '/', BP) . self::LOCK_REL_PATH;
        $lockFp = @fopen($lockFile, 'c+');
        $lockFlags = $maxRunSeconds <= 0.0 ? LOCK_EX : (LOCK_EX | LOCK_NB);
        if ($lockFp === false || !@flock($lockFp, $lockFlags)) {
            if (is_resource($lockFp)) {
                fclose($lockFp);
            }
            $summary['status'] = 'skipped_already_running';
            $summary['elapsed_sec'] = round(microtime(true) - $startTime, 3);
            return $summary;
        }

        try {
            $baseUrl = rtrim(
                (string)$this->storeManager->getStore(self::STORE_ID)->getBaseUrl(UrlInterface::URL_TYPE_LINK, true),
                '/'
            ) . '/';

            $queuePayload = $this->consumeQueue();
            $queuedTags = $queuePayload['tags'];
            $summary['queued_tags_count'] = count($queuedTags);

            $resolvedFromTags = !empty($queuedTags) ? $this->resolveUrlsForTags($queuedTags, $baseUrl) : [];
            $queuedUrls = array_values(array_unique(array_merge($queuePayload['urls'], $resolvedFromTags)));
            $summary['queued_urls_count'] = count($queuedUrls);

            $catalogUrls = $this->collectPrioritizedCanonicalUrls($baseUrl);
            $summary['catalog_urls_total'] = count($catalogUrls);

            $toWarm = [];
            $seen = [];
            $warmedPriorityUrls = [];

            // 1. Event-driven invalidated URLs first (M-WARMER-2):
            // Always warm through external HTTPS (Cloudflare Edge) even if already present in local Redis DB 1.
            foreach ($queuedUrls as $url) {
                if (isset($seen[$url])) {
                    continue;
                }
                $seen[$url] = true;
                if (count($toWarm) >= $limit) {
                    break;
                }
                $toWarm[] = $url;
            }

            // 2. Prioritized canonical catalog & pagination URLs (M-WARMER-1)
            # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
            # "Fix synchronous Klevu customerData XHR freeze (682ms) and upgrade page-cache:warm --all to warm Cloudflare Edge across all catalog URLs using persistent curl handles":
            # https://github.com/smokeday-com/m/issues/39
            # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
            # "Track Cloudflare Edge warm state (var/page_cache_edge_warmed.json) by FPC mtime and decouple --all from --force so subsequent page-cache:warm --all runs skip already Edge-warmed URLs in <0.3s":
            # https://github.com/smokeday-com/m/issues/40
            $edgeWarmedMap = $this->loadEdgeWarmedMap();
            if (count($toWarm) < $limit) {
                $cachedInFpcUrls = [];
                foreach ($catalogUrls as $url) {
                    if (isset($seen[$url])) {
                        continue;
                    }
                    $seen[$url] = true;
                    $summary['checked_in_fpc']++;
                    $cacheId = $this->getCacheIdForUrl($url);
                    $fpcMtime = $this->getCacheMtimeById($cacheId);
                    if ($fpcMtime !== false) {
                        $isEdgeWarmed = isset($edgeWarmedMap[$cacheId]) && (int)$edgeWarmedMap[$cacheId] === $fpcMtime;
                        if ($force || !$isEdgeWarmed) {
                            $cachedInFpcUrls[] = $url;
                        } else {
                            $summary['skipped_already_cached']++;
                        }
                        continue;
                    }
                    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
                    # "Prevent App\Cache::clean() from wiping Redis DB 1 (page_cache) during setup:di:compile and add Origin-direct fallback to Warmer when Cloudflare Edge returns HIT with in_fpc=false":
                    # https://github.com/smokeday-com/m/issues/42
                    if (
                        !$force
                        && isset($edgeWarmedMap[$cacheId])
                        && (int)$edgeWarmedMap[$cacheId] < 0
                        && (time() - abs((int)$edgeWarmedMap[$cacheId])) < self::EDGE_NEGATIVE_TTL_SECONDS
                    ) {
                        $summary['skipped_already_cached']++;
                        continue;
                    }
                    $toWarm[] = $url;
                    if (count($toWarm) >= $limit) {
                        break;
                    }
                }
                if (count($toWarm) < $limit && !empty($cachedInFpcUrls)) {
                    foreach ($cachedInFpcUrls as $url) {
                        $toWarm[] = $url;
                        if (count($toWarm) >= $limit) {
                            break;
                        }
                    }
                }
            }

            $summary['selected_to_warm'] = count($toWarm);

            if (!empty($toWarm) && function_exists('curl_multi_init')) {
                $this->executeRollingWarm(
                    $toWarm,
                    $concurrency,
                    $startTime,
                    $maxRunSeconds,
                    $effectiveMaxLoadAvg,
                    $summary,
                    $warmedPriorityUrls,
                    $edgeWarmedMap
                );
            }

            if (!empty($queuedUrls)) {
                $remainingPriorityUrls = [];
                foreach ($queuedUrls as $qUrl) {
                    if (!isset($warmedPriorityUrls[$qUrl])) {
                        $remainingPriorityUrls[] = $qUrl;
                    }
                }
                if (!empty($remainingPriorityUrls)) {
                    $this->requeueUrls($remainingPriorityUrls);
                }
            }
        } finally {
            @flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }

        $summary['elapsed_sec'] = round(microtime(true) - $startTime, 3);
        return $summary;
    }

    /**
     * Compute Built-in FPC SHA-1 identifier for a normalized canonical guest HTTPS URL.
     *
     * @param string $url
     * @return string
     */
    public function getCacheIdForUrl(string $url): string
    {
        return sha1($this->serializer->serialize([true, $url, null]));
    }

    /**
     * Perform O(1) existence check in Built-in FPC (Redis DB 1 or var/page_cache) without loading payload.
     *
     * @param string $url
     * @return bool
     */
    public function isUrlCached(string $url): bool
    {
        return $this->getCacheMtimeById($this->getCacheIdForUrl($url)) !== false;
    }

    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Track Cloudflare Edge warm state (var/page_cache_edge_warmed.json) by FPC mtime and decouple --all from --force so subsequent page-cache:warm --all runs skip already Edge-warmed URLs in <0.3s":
    # https://github.com/smokeday-com/m/issues/40
    /**
     * Return O(1) last-modified Unix timestamp of an FPC record by its SHA-1 cache ID, or false if absent.
     *
     * @param string $cacheId
     * @return int|false
     */
    public function getCacheMtimeById(string $cacheId)
    {
        $mtime = $this->fullPageCache->test($cacheId);
        return ($mtime !== false && (int)$mtime > 0) ? (int)$mtime : false;
    }

    /**
     * Clear the persisted Cloudflare Edge warm-state map when the full page cache / FPC tag is purged.
     *
     * @return void
     */
    public static function clearEdgeWarmedMap(): void
    {
        if (!defined('BP')) {
            return;
        }
        try {
            $file = self::getEdgeWarmedFilePath();
            if (!is_file($file)) {
                return;
            }
            $fp = @fopen($file, 'c+');
            if ($fp === false) {
                return;
            }
            if (@flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, '{}');
                fflush($fp);
                @flock($fp, LOCK_UN);
            }
            fclose($fp);
        } catch (\Throwable $e) {
            // Never interrupt cache cleaning flow
        }
    }

    /**
     * Load the persisted Cloudflare Edge warm-state map ($cacheId => $fpcMtime).
     *
     * @return array<string, int>
     */
    private function loadEdgeWarmedMap(): array
    {
        $file = self::getEdgeWarmedFilePath();
        if (!is_file($file)) {
            return [];
        }
        $fp = @fopen($file, 'r');
        if ($fp === false) {
            return [];
        }
        $map = [];
        if (@flock($fp, LOCK_SH)) {
            $raw = stream_get_contents($fp);
            @flock($fp, LOCK_UN);
            $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                foreach ($decoded as $k => $v) {
                    if (is_string($k) && $k !== '' && (int)$v !== 0) {
                        $map[$k] = (int)$v;
                    }
                }
            }
        }
        fclose($fp);
        return $map;
    }

    /**
     * Atomically persist the Cloudflare Edge warm-state map ($cacheId => $fpcMtime).
     *
     * @param array<string, int> $map
     * @return void
     */
    private function saveEdgeWarmedMap(array $map): void
    {
        try {
            if (count($map) > self::EDGE_WARMED_MAX_ENTRIES) {
                $map = array_slice($map, -self::EDGE_WARMED_MAX_ENTRIES, null, true);
            }
            $file = self::getEdgeWarmedFilePath();
            $dir = dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $fp = @fopen($file, 'c+');
            if ($fp === false) {
                return;
            }
            if (@flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, (string)json_encode($map, JSON_UNESCAPED_SLASHES));
                fflush($fp);
                @flock($fp, LOCK_UN);
            }
            fclose($fp);
        } catch (\Throwable $e) {
            // Never interrupt warmer execution on file write error
        }
    }

    /**
     * Return cross-platform normalized path to the Cloudflare Edge warm-state file.
     *
     * @return string
     */
    private static function getEdgeWarmedFilePath(): string
    {
        return str_replace('\\', '/', BP) . self::EDGE_WARMED_REL_PATH;
    }

    /**
     * Check whether the priority tag queue file currently has pending invalidated tags or URLs.
     *
     * @return bool
     */
    private function hasQueuedTags(): bool
    {
        $queueFile = self::getQueueFilePath();
        if (!is_file($queueFile)) {
            return false;
        }
        $raw = @file_get_contents($queueFile);
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        return is_array($data) && (!empty($data['tags']) || !empty($data['urls']) || !empty($data['flush_all']));
    }

    /**
     * Re-enqueue remaining unwarmed priority URLs into the queue file when a warming run hits $limit or time budget.
     *
     * @param string[] $urls
     * @return void
     */
    private function requeueUrls(array $urls): void
    {
        if (empty($urls)) {
            return;
        }
        $queueFile = self::getQueueFilePath();
        $fp = @fopen($queueFile, 'c+');
        if ($fp === false) {
            return;
        }
        if (@flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = ['flush_all' => false, 'tags' => [], 'urls' => [], 'updated_at' => time()];
            }
            $existingUrls = isset($data['urls']) && is_array($data['urls']) ? $data['urls'] : [];
            $mergedUrls = array_values(array_unique(array_merge($urls, $existingUrls)));
            if (count($mergedUrls) > self::MAX_QUEUE_TAGS) {
                $mergedUrls = array_slice($mergedUrls, 0, self::MAX_QUEUE_TAGS);
            }
            $data['urls'] = $mergedUrls;
            $data['updated_at'] = time();
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string)json_encode($data, JSON_UNESCAPED_SLASHES));
            fflush($fp);
            @flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    /**
     * Atomically consume and clear the event-driven tag and priority URL queue file.
     *
     * @return array{flush_all: bool, tags: string[], urls: string[]}
     */
    private function consumeQueue(): array
    {
        $result = ['flush_all' => false, 'tags' => [], 'urls' => []];
        $queueFile = self::getQueueFilePath();
        if (!is_file($queueFile)) {
            return $result;
        }
        $fp = @fopen($queueFile, 'c+');
        if ($fp === false) {
            return $result;
        }
        if (@flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (is_array($data)) {
                $result['flush_all'] = !empty($data['flush_all']);
                if (!empty($data['tags']) && is_array($data['tags'])) {
                    $result['tags'] = array_values(array_unique(array_map('strval', $data['tags'])));
                }
                if (!empty($data['urls']) && is_array($data['urls'])) {
                    $result['urls'] = array_values(array_unique(array_map('strval', $data['urls'])));
                }
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, '{"flush_all":false,"tags":[],"urls":[],"updated_at":' . time() . '}');
            fflush($fp);
            @flock($fp, LOCK_UN);
        }
        fclose($fp);
        return $result;
    }

    /**
     * Resolve invalidated entity cache tags into canonical storefront URLs.
     *
     * @param string[] $tags
     * @param string $baseUrl
     * @return string[]
     */
    private function resolveUrlsForTags(array $tags, string $baseUrl): array
    {
        $productIds = [];
        $categoryIds = [];
        $cmsIds = [];

        foreach ($tags as $tag) {
            if (preg_match('/^cat_p_(\d+)$/', $tag, $m)) {
                $productIds[] = (int)$m[1];
            } elseif (preg_match('/^cat_c(?:_p)?_(\d+)$/', $tag, $m)) {
                $categoryIds[] = (int)$m[1];
            } elseif (preg_match('/^cms_p_(\d+)$/', $tag, $m)) {
                $cmsIds[] = (int)$m[1];
            }
        }

        $urls = [$baseUrl];
        $conn = $this->resource->getConnection();
        $urlRewriteTable = $this->resource->getTableName('url_rewrite');
        $gridPerPage = max(1, (int)($this->scopeConfig->getValue(
            'catalog/frontend/grid_per_page',
            ScopeInterface::SCOPE_STORE,
            self::STORE_ID
        ) ?: 40));

        if (!empty($productIds)) {
            $productIds = array_values(array_unique($productIds));
            $catIndexTable = $this->getCategoryProductIndexTable();
            if ($catIndexTable !== null) {
                $catSelect = $conn->select()
                    ->distinct()
                    ->from($catIndexTable, ['category_id'])
                    ->where('product_id IN (?)', $productIds)
                    ->where('category_id > 2');
                foreach ($conn->fetchCol($catSelect) as $cid) {
                    $categoryIds[] = (int)$cid;
                }
            }
        }

        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Calibrate FPC Warmer load threshold (15.0 / 25.0 for priority queue), run every minute, prioritize Category Page 1 URLs, and guard Type::purgeCloudflare() / Warmer::enqueueTags() against empty tag arrays":
        # https://github.com/smokeday-com/m/issues/26
        // Prioritize Category Page 1 URLs (sorted by product count DESC) immediately after $baseUrl
        $paginatedUrls = [];
        if (!empty($categoryIds)) {
            $categoryIds = array_values(array_unique($categoryIds));
            $catSelect = $conn->select()
                ->from(['u' => $urlRewriteTable], ['entity_id', 'request_path'])
                ->where('u.store_id = ?', self::STORE_ID)
                ->where('u.redirect_type = 0')
                ->where('u.entity_type = ?', 'category')
                ->where('u.entity_id IN (?)', $categoryIds);
            $catPaths = $conn->fetchPairs($catSelect);

            $catCounts = $this->getCategoryProductCounts($categoryIds);
            $catsSorted = [];
            foreach ($catPaths as $cid => $path) {
                $cleanPath = ltrim((string)$path, '/');
                if ($cleanPath === '') {
                    continue;
                }
                $catsSorted[] = [
                    'id' => (int)$cid,
                    'url' => $baseUrl . $cleanPath,
                    'cnt' => $catCounts[(int)$cid] ?? 0,
                ];
            }
            usort($catsSorted, static fn(array $a, array $b): int => $b['cnt'] <=> $a['cnt']);

            $maxPages = 1;
            foreach ($catsSorted as $cat) {
                $urls[] = $cat['url'];
                $pages = (int)ceil($cat['cnt'] / $gridPerPage);
                if ($pages > $maxPages) {
                    $maxPages = $pages;
                }
            }
            for ($p = 2; $p <= $maxPages; $p++) {
                foreach ($catsSorted as $cat) {
                    $pages = (int)ceil($cat['cnt'] / $gridPerPage);
                    if ($p <= $pages) {
                        $paginatedUrls[] = $cat['url'] . '?p=' . $p;
                    }
                }
            }
        }

        if (!empty($cmsIds)) {
            $cmsIds = array_values(array_unique($cmsIds));
            $cmsSelect = $conn->select()
                ->from(['u' => $urlRewriteTable], ['request_path'])
                ->where('u.store_id = ?', self::STORE_ID)
                ->where('u.redirect_type = 0')
                ->where('u.entity_type = ?', 'cms-page')
                ->where('u.entity_id IN (?)', $cmsIds);
            foreach ($conn->fetchCol($cmsSelect) as $path) {
                $cleanPath = ltrim((string)$path, '/');
                if ($cleanPath !== '' && $cleanPath !== 'home') {
                    $urls[] = $baseUrl . $cleanPath;
                }
            }
        }

        if (!empty($productIds)) {
            $prodSelect = $conn->select()
                ->from(['u' => $urlRewriteTable], ['request_path'])
                ->where('u.store_id = ?', self::STORE_ID)
                ->where('u.redirect_type = 0')
                ->where('u.entity_type = ?', 'product')
                ->where('u.metadata IS NULL')
                ->where('u.entity_id IN (?)', $productIds);
            foreach ($conn->fetchCol($prodSelect) as $path) {
                $cleanPath = ltrim((string)$path, '/');
                if ($cleanPath !== '') {
                    $urls[] = $baseUrl . $cleanPath;
                }
            }
        }

        foreach ($paginatedUrls as $paginatedUrl) {
            $urls[] = $paginatedUrl;
        }

        return array_values(array_unique($urls));
    }

    /**
     * Build prioritized canonical URL list from MySQL:
     * 1. Homepage & /blog
     * 2. Active CMS pages
     * 3. Active Category first pages (sorted by product count DESC)
     * 4. Active Category paginated pages ?p=2..N (breadth-first by page number p=2, p=3, ...)
     * 5. Enabled visible canonical Product URLs (metadata IS NULL), ordered by In-Stock first & 90-day order volume DESC
     *
     * @param string $baseUrl
     * @return string[]
     */
    public function collectPrioritizedCanonicalUrls(string $baseUrl): array
    {
        $catalogCacheFile = str_replace('\\', '/', BP) . self::CATALOG_CACHE_REL_PATH;
        if (is_file($catalogCacheFile) && (time() - (int)@filemtime($catalogCacheFile)) < self::CATALOG_CACHE_TTL) {
            $cachedRaw = @file_get_contents($catalogCacheFile);
            $cachedData = is_string($cachedRaw) && $cachedRaw !== '' ? json_decode($cachedRaw, true) : null;
            if (
                is_array($cachedData)
                && ($cachedData['base_url'] ?? '') === $baseUrl
                && !empty($cachedData['urls'])
                && is_array($cachedData['urls'])
            ) {
                return $cachedData['urls'];
            }
        }

        $conn = $this->resource->getConnection();
        $urlRewriteTable = $this->resource->getTableName('url_rewrite');
        $eavAttrTable = $this->resource->getTableName('eav_attribute');
        $gridPerPage = max(1, (int)($this->scopeConfig->getValue(
            'catalog/frontend/grid_per_page',
            ScopeInterface::SCOPE_STORE,
            self::STORE_ID
        ) ?: 40));

        $urls = [$baseUrl, $baseUrl . 'blog'];

        // 1. Active blog posts if Magefan_Blog table exists
        $blogPostTable = $this->resource->getTableName('magefan_blog_post');
        if ($conn->isTableExists($blogPostTable)) {
            $blogSelect = $conn->select()
                ->from($blogPostTable, ['identifier'])
                ->where('is_active = 1');
            foreach ($conn->fetchCol($blogSelect) as $ident) {
                $identStr = trim((string)$ident, '/');
                if ($identStr !== '') {
                    $urls[] = $baseUrl . 'blog/post/' . $identStr;
                }
            }
        }

        // 2. Active CMS pages
        $cmsPageTable = $this->resource->getTableName('cms_page');
        $cmsSelect = $conn->select()
            ->from(['u' => $urlRewriteTable], ['request_path'])
            ->joinInner(['p' => $cmsPageTable], 'p.page_id = u.entity_id AND p.is_active = 1', [])
            ->where('u.store_id = ?', self::STORE_ID)
            ->where('u.redirect_type = 0')
            ->where('u.entity_type = ?', 'cms-page')
            ->order('u.entity_id ASC');
        foreach ($conn->fetchCol($cmsSelect) as $path) {
            $cleanPath = ltrim((string)$path, '/');
            if ($cleanPath !== '' && $cleanPath !== 'home' && $cleanPath !== 'no-route') {
                $urls[] = $baseUrl . $cleanPath;
            }
        }

        // 3 & 4. Active Categories (Page 1 + Paginated Pages ?p=2..N)
        $catActiveAttrId = (int)$conn->fetchOne(
            $conn->select()
                ->from($eavAttrTable, ['attribute_id'])
                ->where('entity_type_id = 3')
                ->where('attribute_code = ?', 'is_active')
        );
        $catIntTable = $this->resource->getTableName('catalog_category_entity_int');
        $catSelect = $conn->select()
            ->from(['u' => $urlRewriteTable], ['entity_id', 'request_path'])
            ->joinInner(
                ['ci' => $catIntTable],
                'ci.entity_id = u.entity_id AND ci.attribute_id = ' . $catActiveAttrId . ' AND ci.store_id = 0 AND ci.value = 1',
                []
            )
            ->where('u.store_id = ?', self::STORE_ID)
            ->where('u.redirect_type = 0')
            ->where('u.entity_type = ?', 'category');
        $catRows = $conn->fetchPairs($catSelect);
        $catCounts = $this->getCategoryProductCounts(array_map('intval', array_keys($catRows)));

        $categoriesSorted = [];
        foreach ($catRows as $cid => $path) {
            $cleanPath = ltrim((string)$path, '/');
            if ($cleanPath === '') {
                continue;
            }
            $categoriesSorted[] = [
                'id' => (int)$cid,
                'url' => $baseUrl . $cleanPath,
                'cnt' => $catCounts[(int)$cid] ?? 0,
            ];
        }
        usort($categoriesSorted, static fn(array $a, array $b): int => $b['cnt'] <=> $a['cnt']);

        $maxPages = 1;
        foreach ($categoriesSorted as $cat) {
            $urls[] = $cat['url'];
            $pages = (int)ceil($cat['cnt'] / $gridPerPage);
            if ($pages > $maxPages) {
                $maxPages = $pages;
            }
        }

        for ($page = 2; $page <= $maxPages; $page++) {
            foreach ($categoriesSorted as $cat) {
                $pages = (int)ceil($cat['cnt'] / $gridPerPage);
                if ($page <= $pages) {
                    $urls[] = $cat['url'] . '?p=' . $page;
                }
            }
        }

        // 5. Canonical Product URLs (metadata IS NULL), ordered by In-Stock first + 90-day order volume DESC
        $statusAttrId = (int)$conn->fetchOne(
            $conn->select()
                ->from($eavAttrTable, ['attribute_id'])
                ->where('entity_type_id = 4')
                ->where('attribute_code = ?', 'status')
        );
        $visAttrId = (int)$conn->fetchOne(
            $conn->select()
                ->from($eavAttrTable, ['attribute_id'])
                ->where('entity_type_id = 4')
                ->where('attribute_code = ?', 'visibility')
        );

        $prodIntTable = $this->resource->getTableName('catalog_product_entity_int');
        $stockTable = $this->resource->getTableName('cataloginventory_stock_status');
        $orderTable = $this->resource->getTableName('sales_order');
        $orderItemTable = $this->resource->getTableName('sales_order_item');

        $salesCounts = [];
        if ($conn->isTableExists($orderTable) && $conn->isTableExists($orderItemTable)) {
            $minOrderId = (int)$conn->fetchOne(
                $conn->select()
                    ->from($orderTable, [new \Zend_Db_Expr('MIN(entity_id)')])
                    ->where('created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)')
            );
            if ($minOrderId > 0) {
                $salesSelect = $conn->select()
                    ->from($orderItemTable, ['product_id', 'cnt' => new \Zend_Db_Expr('COUNT(*)')])
                    ->where('order_id >= ?', $minOrderId)
                    ->group('product_id');
                foreach ($conn->fetchAll($salesSelect) as $row) {
                    $salesCounts[(int)$row['product_id']] = (int)$row['cnt'];
                }
            }
        }

        $prodSelect = $conn->select()
            ->from(['u' => $urlRewriteTable], ['entity_id', 'request_path'])
            ->joinInner(
                ['st' => $prodIntTable],
                'st.entity_id = u.entity_id AND st.attribute_id = ' . $statusAttrId . ' AND st.store_id = 0 AND st.value = 1',
                []
            )
            ->joinInner(
                ['vis' => $prodIntTable],
                'vis.entity_id = u.entity_id AND vis.attribute_id = ' . $visAttrId . ' AND vis.store_id = 0 AND vis.value IN (2, 3, 4)',
                []
            )
            ->joinLeft(
                ['ss' => $stockTable],
                'ss.product_id = u.entity_id AND ss.website_id = 0',
                ['stock_status' => new \Zend_Db_Expr('COALESCE(ss.stock_status, 0)')]
            )
            ->where('u.is_autogenerated = 1')
            ->where('u.metadata IS NULL')
            ->where('u.store_id = ?', self::STORE_ID)
            ->where('u.redirect_type = 0')
            ->where('u.entity_type = ?', 'product');

        $products = [];
        foreach ($conn->fetchAll($prodSelect) as $row) {
            $pid = (int)$row['entity_id'];
            $cleanPath = ltrim((string)$row['request_path'], '/');
            if ($cleanPath === '') {
                continue;
            }
            $products[] = [
                'id' => $pid,
                'url' => $baseUrl . $cleanPath,
                'in_stock' => (int)$row['stock_status'],
                'orders' => $salesCounts[$pid] ?? 0,
            ];
        }

        usort($products, static function (array $a, array $b): int {
            if ($a['in_stock'] !== $b['in_stock']) {
                return $b['in_stock'] <=> $a['in_stock'];
            }
            if ($a['orders'] !== $b['orders']) {
                return $b['orders'] <=> $a['orders'];
            }
            return $b['id'] <=> $a['id'];
        });

        foreach ($products as $prod) {
            $urls[] = $prod['url'];
        }

        $finalUrls = array_values(array_unique($urls));
        @file_put_contents(
            $catalogCacheFile,
            (string)json_encode(['base_url' => $baseUrl, 'updated_at' => time(), 'urls' => $finalUrls], JSON_UNESCAPED_SLASHES)
        );
        return $finalUrls;
    }

    /**
     * Get visible product counts per category from catalog_category_product_index_store1.
     *
     * @param int[] $categoryIds
     * @return array<int, int>
     */
    private function getCategoryProductCounts(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }
        $conn = $this->resource->getConnection();
        $indexTable = $this->getCategoryProductIndexTable();
        if ($indexTable === null) {
            return [];
        }
        $select = $conn->select()
            ->from($indexTable, ['category_id', 'cnt' => new \Zend_Db_Expr('COUNT(DISTINCT product_id)')])
            ->where('category_id IN (?)', $categoryIds)
            ->where('visibility IN (2, 4)')
            ->group('category_id');

        $counts = [];
        foreach ($conn->fetchAll($select) as $row) {
            $counts[(int)$row['category_id']] = (int)$row['cnt'];
        }
        return $counts;
    }

    /**
     * Resolve category-product index table for store 1.
     *
     * @return string|null
     */
    private function getCategoryProductIndexTable(): ?string
    {
        $conn = $this->resource->getConnection();
        $store1Table = $this->resource->getTableName('catalog_category_product_index_store1');
        if ($conn->isTableExists($store1Table)) {
            return $store1Table;
        }
        $baseTable = $this->resource->getTableName('catalog_category_product_index');
        if ($conn->isTableExists($baseTable)) {
            return $baseTable;
        }
        return null;
    }

    /**
     * Execute concurrent guest GET requests using a persistent rolling-window curl_multi handle.
     *
     * Immediately refills freed concurrency slots as each URL completes and reuses TLS/HTTP2 connections.
     *
     * @param string[] $urls
     * @param int $concurrency
     * @param float $startTime
     * @param float $maxRunSeconds
     * @param float $effectiveMaxLoadAvg
     * @param array<string, mixed> $summary
     * @param array<string, bool> $warmedPriorityUrls
     * @return void
     */
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Add --all (-a) and --max-time (-t) manual full-site warming mode with rolling-window curl_multi pipelining to page-cache:warm (WarmCacheCommand and Warmer::warm)":
    # https://github.com/smokeday-com/m/issues/37
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Prevent Nexcess ModSecurity HTTP 429 greylisting in Warmer::executeRollingWarm() via synthetic X-Forwarded-For IP rotation + 429 retry/backoff, and restore CF-Connecting-IP as REMOTE_ADDR in app/bootstrap.php":
    # https://github.com/smokeday-com/m/issues/38
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Fix synchronous Klevu customerData XHR freeze (682ms) and upgrade page-cache:warm --all to warm Cloudflare Edge across all catalog URLs using persistent curl handles":
    # https://github.com/smokeday-com/m/issues/39
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Track Cloudflare Edge warm state (var/page_cache_edge_warmed.json) by FPC mtime and decouple --all from --force so subsequent page-cache:warm --all runs skip already Edge-warmed URLs in <0.3s":
    # https://github.com/smokeday-com/m/issues/40
    private function executeRollingWarm(
        array $urls,
        int $concurrency,
        float $startTime,
        float $maxRunSeconds,
        float $effectiveMaxLoadAvg,
        array &$summary,
        array &$warmedPriorityUrls,
        array &$edgeWarmedMap
    ): void {
        $mh = curl_multi_init();
        if (defined('CURLMOPT_PIPELINING') && defined('CURLPIPE_MULTIPLEX')) {
            @curl_multi_setopt($mh, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX);
        }
        if (defined('CURLMOPT_MAXCONNECTS')) {
            @curl_multi_setopt($mh, CURLMOPT_MAXCONNECTS, max(32, $concurrency * 2));
        }

        $total = count($urls);
        $nextIndex = 0;
        $retryQueue = [];
        $retryCounts = [];
        $inFlight = [];
        $handlePool = [];
        $headersMap = [];
        $originFallbackMap = [];
        $edgeMapDirtyCount = 0;
        $stopEnqueueReason = null;
        try {
            $requestSequence = random_int(0, self::SYNTHETIC_IP_POOL_SIZE - 1);
        } catch (\Throwable $e) {
            $requestSequence = (int)(microtime(true) * 1000) % self::SYNTHETIC_IP_POOL_SIZE;
        }

        while ($nextIndex < $total || !empty($retryQueue) || !empty($inFlight)) {
            while (
                $stopEnqueueReason === null
                && (!empty($retryQueue) || $nextIndex < $total)
                && count($inFlight) < $concurrency
            ) {
                if ($maxRunSeconds > 0.0 && (microtime(true) - $startTime) >= $maxRunSeconds) {
                    $stopEnqueueReason = 'paused_time_budget';
                    break;
                }
                if ($effectiveMaxLoadAvg > 0.0) {
                    $loadNow = $this->getCurrentLoadAvg();
                    if ($loadNow !== null && $loadNow >= $effectiveMaxLoadAvg) {
                        $stopEnqueueReason = 'paused_high_load';
                        $summary['load_avg_1m'] = $loadNow;
                        break;
                    }
                }

                $url = !empty($retryQueue) ? array_shift($retryQueue) : $urls[$nextIndex++];
                $ch = !empty($handlePool) ? array_pop($handlePool) : curl_init();
                if ($ch === false) {
                    continue;
                }
                $handleKey = is_object($ch) ? spl_object_id($ch) : (int)$ch;
                $headersMap[$handleKey] = ['cf_cache_status' => 'NONE'];
                $syntheticIp = $this->getSyntheticForwardedIp($requestSequence++);
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_TIMEOUT => 12,
                    CURLOPT_DNS_CACHE_TIMEOUT => 3600,
                    CURLOPT_ENCODING => '',
                    CURLOPT_HTTPHEADER => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.9',
                        'X-Forwarded-For: ' . $syntheticIp,
                        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 SmokedayCacheWarmer/1.0',
                    ],
                    CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use ($handleKey, &$headersMap): int {
                        if (stripos($headerLine, 'cf-cache-status:') === 0) {
                            $headersMap[$handleKey]['cf_cache_status'] = trim(substr($headerLine, 16));
                        }
                        return strlen($headerLine);
                    },
                ]);
                curl_multi_add_handle($mh, $ch);
                $inFlight[$handleKey] = ['handle' => $ch, 'url' => $url];
            }

            if (empty($inFlight)) {
                break;
            }

            $active = null;
            do {
                $status = curl_multi_exec($mh, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            while ($info = curl_multi_info_read($mh)) {
                if (($info['msg'] ?? null) !== CURLMSG_DONE || !isset($info['handle'])) {
                    continue;
                }
                $ch = $info['handle'];
                $handleKey = is_object($ch) ? spl_object_id($ch) : (int)$ch;
                $url = $inFlight[$handleKey]['url'] ?? '';
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $totalTime = round((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME), 3);
                $cfStatus = $headersMap[$handleKey]['cf_cache_status'] ?? 'NONE';

                curl_multi_remove_handle($mh, $ch);
                $handlePool[] = $ch;
                unset($inFlight[$handleKey], $headersMap[$handleKey]);

                if ($url !== '') {
                    if ($httpCode === 429 && ($retryCounts[$url] ?? 0) < self::MAX_429_RETRIES) {
                        $retryCounts[$url] = ($retryCounts[$url] ?? 0) + 1;
                        $requestSequence += 97;
                        $retryQueue[] = $url;
                        usleep(150000);
                        continue;
                    }
                    $warmedPriorityUrls[$url] = true;
                    $cacheId = $this->getCacheIdForUrl($url);
                    $newMtime = $this->getCacheMtimeById($cacheId);
                    $inFpc = ($newMtime !== false);
                    $isCacheableStatus = ($httpCode === 200 || $httpCode === 404);
                    $cfEdgeWarmed = ($isCacheableStatus && in_array($cfStatus, ['HIT', 'MISS', 'EXPIRED', 'STALE', 'REVALIDATED'], true));
                    $warmedEntryIndex = count($summary['warmed_urls']);
                    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
                    # "Prevent App\Cache::clean() from wiping Redis DB 1 (page_cache) during setup:di:compile and add Origin-direct fallback to Warmer when Cloudflare Edge returns HIT with in_fpc=false":
                    # https://github.com/smokeday-com/m/issues/42
                    if ($inFpc && $isCacheableStatus && ($cfEdgeWarmed || $cfStatus === 'DYNAMIC' || $cfStatus === 'NONE')) {
                        $edgeWarmedMap[$cacheId] = $newMtime;
                        $edgeMapDirtyCount++;
                    } elseif (!$inFpc && $httpCode === 200 && in_array($cfStatus, ['HIT', 'STALE', 'REVALIDATED'], true)) {
                        $originFallbackMap[$url] = $warmedEntryIndex;
                    } elseif (!$inFpc && $isCacheableStatus) {
                        $edgeWarmedMap[$cacheId] = -time();
                        $edgeMapDirtyCount++;
                    }
                    if ($edgeMapDirtyCount >= self::EDGE_WARMED_FLUSH_INTERVAL) {
                        $this->saveEdgeWarmedMap($edgeWarmedMap);
                        $edgeMapDirtyCount = 0;
                    }
                    if ($httpCode === 200) {
                        $summary['warmed_count']++;
                    }
                    if ($inFpc) {
                        $summary['verified_in_fpc']++;
                    }
                    $summary['warmed_urls'][] = [
                        'url' => $url,
                        'http_code' => $httpCode,
                        'cf_cache_status' => $cfStatus,
                        'cf_edge_warmed' => $cfEdgeWarmed,
                        'time_sec' => $totalTime,
                        'in_fpc' => $inFpc,
                    ];
                }
            }

            if (!empty($inFlight) && $active && $status === CURLM_OK) {
                if (curl_multi_select($mh, 0.2) === -1) {
                    usleep(10000);
                }
            }
        }

        if ($edgeMapDirtyCount > 0) {
            $this->saveEdgeWarmedMap($edgeWarmedMap);
        }
        foreach ($handlePool as $ch) {
            curl_close($ch);
        }
        curl_multi_close($mh);
        if (!empty($originFallbackMap) && $stopEnqueueReason === null) {
            $this->warmOriginDirect(
                $originFallbackMap,
                $concurrency,
                $startTime,
                $maxRunSeconds,
                $effectiveMaxLoadAvg,
                $summary,
                $edgeWarmedMap,
                $stopEnqueueReason
            );
        }
        if ($stopEnqueueReason !== null) {
            $summary['status'] = $stopEnqueueReason;
        }
    }

    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Prevent App\Cache::clean() from wiping Redis DB 1 (page_cache) during setup:di:compile and add Origin-direct fallback to Warmer when Cloudflare Edge returns HIT with in_fpc=false":
    # https://github.com/smokeday-com/m/issues/42
    /**
     * Warm local Origin (127.0.0.1) directly via CURLOPT_RESOLVE for URLs where Cloudflare Edge served a cached HIT
     * without forwarding the request to Origin while the local Built-in FPC (Redis DB 1) record was absent.
     *
     * @param array<string, int> $originFallbackMap Map of URL => index in $summary['warmed_urls']
     * @param int $concurrency
     * @param float $startTime
     * @param float $maxRunSeconds
     * @param float $effectiveMaxLoadAvg
     * @param array<string, mixed> $summary
     * @param array<string, int> $edgeWarmedMap
     * @param string|null $stopEnqueueReason
     * @return void
     */
    private function warmOriginDirect(
        array $originFallbackMap,
        int $concurrency,
        float $startTime,
        float $maxRunSeconds,
        float $effectiveMaxLoadAvg,
        array &$summary,
        array &$edgeWarmedMap,
        ?string &$stopEnqueueReason
    ): void {
        $urls = array_keys($originFallbackMap);
        $firstUrl = $urls[0] ?? '';
        $host = (string)parse_url($firstUrl, PHP_URL_HOST);
        $scheme = strtolower((string)parse_url($firstUrl, PHP_URL_SCHEME));
        $port = (int)(parse_url($firstUrl, PHP_URL_PORT) ?: ($scheme === 'http' ? 80 : 443));
        if (!$this->canWarmOriginDirect($host, $port)) {
            return;
        }

        $resolveEntry = $host . ':' . $port . ':127.0.0.1';
        $mh = curl_multi_init();
        if (defined('CURLMOPT_MAXCONNECTS')) {
            @curl_multi_setopt($mh, CURLMOPT_MAXCONNECTS, max(32, $concurrency * 2));
        }

        $total = count($urls);
        $nextIndex = 0;
        $retryQueue = [];
        $retryCounts = [];
        $inFlight = [];
        $handlePool = [];
        $edgeMapDirtyCount = 0;
        try {
            $requestSequence = random_int(0, self::SYNTHETIC_IP_POOL_SIZE - 1);
        } catch (\Throwable $e) {
            $requestSequence = (int)(microtime(true) * 1000) % self::SYNTHETIC_IP_POOL_SIZE;
        }

        while ($nextIndex < $total || !empty($retryQueue) || !empty($inFlight)) {
            while (
                $stopEnqueueReason === null
                && (!empty($retryQueue) || $nextIndex < $total)
                && count($inFlight) < $concurrency
            ) {
                if ($maxRunSeconds > 0.0 && (microtime(true) - $startTime) >= $maxRunSeconds) {
                    $stopEnqueueReason = 'paused_time_budget';
                    break;
                }
                if ($effectiveMaxLoadAvg > 0.0) {
                    $loadNow = $this->getCurrentLoadAvg();
                    if ($loadNow !== null && $loadNow >= $effectiveMaxLoadAvg) {
                        $stopEnqueueReason = 'paused_high_load';
                        $summary['load_avg_1m'] = $loadNow;
                        break;
                    }
                }

                $url = !empty($retryQueue) ? array_shift($retryQueue) : $urls[$nextIndex++];
                $ch = !empty($handlePool) ? array_pop($handlePool) : curl_init();
                if ($ch === false) {
                    continue;
                }
                $handleKey = is_object($ch) ? spl_object_id($ch) : (int)$ch;
                $syntheticIp = $this->getSyntheticForwardedIp($requestSequence++);
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RESOLVE => [$resolveEntry],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_TIMEOUT => 12,
                    CURLOPT_ENCODING => '',
                    CURLOPT_HTTPHEADER => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.9',
                        'X-Forwarded-For: ' . $syntheticIp,
                        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 SmokedayCacheWarmer/1.0',
                    ],
                ]);
                curl_multi_add_handle($mh, $ch);
                $inFlight[$handleKey] = ['handle' => $ch, 'url' => $url];
            }

            if (empty($inFlight)) {
                break;
            }

            $active = null;
            do {
                $status = curl_multi_exec($mh, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            while ($info = curl_multi_info_read($mh)) {
                if (($info['msg'] ?? null) !== CURLMSG_DONE || !isset($info['handle'])) {
                    continue;
                }
                $ch = $info['handle'];
                $handleKey = is_object($ch) ? spl_object_id($ch) : (int)$ch;
                $url = $inFlight[$handleKey]['url'] ?? '';
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_multi_remove_handle($mh, $ch);
                $handlePool[] = $ch;
                unset($inFlight[$handleKey]);

                if ($url !== '') {
                    if ($httpCode === 429 && ($retryCounts[$url] ?? 0) < self::MAX_429_RETRIES) {
                        $retryCounts[$url] = ($retryCounts[$url] ?? 0) + 1;
                        $requestSequence += 97;
                        $retryQueue[] = $url;
                        usleep(150000);
                        continue;
                    }
                    $cacheId = $this->getCacheIdForUrl($url);
                    $newMtime = $this->getCacheMtimeById($cacheId);
                    $inFpc = ($newMtime !== false);
                    $entryIdx = $originFallbackMap[$url] ?? null;
                    if ($inFpc) {
                        $summary['verified_in_fpc']++;
                        if ($entryIdx !== null && isset($summary['warmed_urls'][$entryIdx])) {
                            $summary['warmed_urls'][$entryIdx]['in_fpc'] = true;
                        }
                        $edgeWarmedMap[$cacheId] = $newMtime;
                        $edgeMapDirtyCount++;
                    } elseif ($httpCode === 200 || $httpCode === 404) {
                        $edgeWarmedMap[$cacheId] = -time();
                        $edgeMapDirtyCount++;
                    }
                    if ($edgeMapDirtyCount >= self::EDGE_WARMED_FLUSH_INTERVAL) {
                        $this->saveEdgeWarmedMap($edgeWarmedMap);
                        $edgeMapDirtyCount = 0;
                    }
                }
            }

            if (!empty($inFlight) && $active && $status === CURLM_OK) {
                if (curl_multi_select($mh, 0.2) === -1) {
                    usleep(10000);
                }
            }
        }

        if ($edgeMapDirtyCount > 0) {
            $this->saveEdgeWarmedMap($edgeWarmedMap);
        }
        foreach ($handlePool as $ch) {
            curl_close($ch);
        }
        curl_multi_close($mh);
    }

    /**
     * Check whether local Origin loopback (127.0.0.1:$port) accepts TCP connections.
     *
     * @param string $host
     * @param int $port
     * @return bool
     */
    private function canWarmOriginDirect(string $host, int $port = 443): bool
    {
        if ($host === '' || $port <= 0) {
            return false;
        }
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1.0);
        if (!is_resource($fp)) {
            return false;
        }
        fclose($fp);
        return true;
    }

    /**
     * Generate a deterministic synthetic non-routable IPv4 address from RFC 2544 / RFC 5737 pools (10,762 IPs)
     * so Nexcess Apache mod_remoteip / ModSecurity greylisting (rule 4012024) never throttles warmer bursts.
     *
     * @param int $sequence
     * @return string
     */
    private function getSyntheticForwardedIp(int $sequence): string
    {
        $idx = (($sequence % self::SYNTHETIC_IP_POOL_SIZE) + self::SYNTHETIC_IP_POOL_SIZE) % self::SYNTHETIC_IP_POOL_SIZE;
        if ($idx < 10000) {
            $o3 = 100 + intdiv($idx, 100);
            $o4 = 100 + ($idx % 100);
            return '198.18.' . $o3 . '.' . $o4;
        }
        if ($idx < 10254) {
            return '192.0.2.' . (1 + ($idx - 10000));
        }
        if ($idx < 10508) {
            return '198.51.100.' . (1 + ($idx - 10254));
        }
        return '203.0.113.' . (1 + ($idx - 10508));
    }

    /**
     * Get 1-minute system load average on Linux; returns null on Windows.
     *
     * @return float|null
     */
    private function getCurrentLoadAvg(): ?float
    {
        $isWin = 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
        if (!$isWin && function_exists('sys_getloadavg')) {
            $load = @sys_getloadavg();
            if (is_array($load) && isset($load[0])) {
                return round((float)$load[0], 2);
            }
        }
        return null;
    }

    /**
     * Return cross-platform normalized path to the warmer tag queue file.
     *
     * @return string
     */
    private static function getQueueFilePath(): string
    {
        return str_replace('\\', '/', BP) . self::QUEUE_REL_PATH;
    }
}
