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
    public const DEFAULT_LIMIT = 15;
    public const DEFAULT_CONCURRENCY = 1;
    public const DEFAULT_MAX_LOAD_AVG = 15.0;
    public const PRIORITY_QUEUE_MAX_LOAD_AVG = 25.0;

    private const STORE_ID = 1;
    private const QUEUE_REL_PATH = '/var/page_cache_warm_queue.json';
    private const CATALOG_CACHE_REL_PATH = '/var/page_cache_warm_catalog.json';
    private const CATALOG_CACHE_TTL = 3600;
    private const LOCK_REL_PATH = '/var/page_cache_warmer.lock';
    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Remove >90 tags-to-FPC escalation in Magento\PageCache\Model\Cache\Type::purgeCloudflare() and add stack-trace audit logging to var/log/cloudflare_purge.log":
    # https://github.com/smokeday-com/m/issues/31
    private const MAX_QUEUE_TAGS = 5000;

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
     * @return array<string, mixed>
     */
    public function warm(
        int $limit = self::DEFAULT_LIMIT,
        int $concurrency = self::DEFAULT_CONCURRENCY,
        float $maxLoadAvg = self::DEFAULT_MAX_LOAD_AVG,
        bool $force = false
    ): array {
        $startTime = microtime(true);
        $limit = max(1, $limit);
        $concurrency = max(1, min(8, $concurrency));
        $currentLoad = $this->getCurrentLoadAvg();
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Calibrate FPC Warmer load threshold (15.0 / 25.0 for priority queue), run every minute, prioritize Category Page 1 URLs, and guard Type::purgeCloudflare() / Warmer::enqueueTags() against empty tag arrays":
        # https://github.com/smokeday-com/m/issues/26
        $hasPendingQueue = $this->hasQueuedTags();
        $effectiveMaxLoadAvg = ($hasPendingQueue && $maxLoadAvg <= self::DEFAULT_MAX_LOAD_AVG)
            ? self::PRIORITY_QUEUE_MAX_LOAD_AVG
            : $maxLoadAvg;

        $summary = [
            'status' => 'ok',
            'load_avg_1m' => $currentLoad,
            'max_load_avg' => $effectiveMaxLoadAvg,
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

        if ($currentLoad !== null && $currentLoad >= $effectiveMaxLoadAvg) {
            $summary['status'] = 'skipped_high_load';
            $summary['elapsed_sec'] = round(microtime(true) - $startTime, 3);
            return $summary;
        }

        $lockFile = str_replace('\\', '/', BP) . self::LOCK_REL_PATH;
        $lockFp = @fopen($lockFile, 'c+');
        if ($lockFp === false || !@flock($lockFp, LOCK_EX | LOCK_NB)) {
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

            $queuedUrls = [];
            if (!empty($queuedTags)) {
                $queuedUrls = $this->resolveUrlsForTags($queuedTags, $baseUrl);
                $summary['queued_urls_count'] = count($queuedUrls);
            }

            $catalogUrls = $this->collectPrioritizedCanonicalUrls($baseUrl);
            $summary['catalog_urls_total'] = count($catalogUrls);

            $toWarm = [];
            $seen = [];
            $hasRemainingQueuedUrls = false;

            // 1. Event-driven invalidated URLs first (M-WARMER-2)
            foreach ($queuedUrls as $url) {
                if (isset($seen[$url])) {
                    continue;
                }
                $seen[$url] = true;
                $summary['checked_in_fpc']++;
                if (!$force && $this->isUrlCached($url)) {
                    $summary['skipped_already_cached']++;
                    continue;
                }
                if (count($toWarm) >= $limit) {
                    $hasRemainingQueuedUrls = true;
                    break;
                }
                $toWarm[] = $url;
            }

            // 2. Prioritized canonical catalog & pagination URLs (M-WARMER-1)
            if (count($toWarm) < $limit) {
                foreach ($catalogUrls as $url) {
                    if (isset($seen[$url])) {
                        continue;
                    }
                    $seen[$url] = true;
                    $summary['checked_in_fpc']++;
                    if (!$force && $this->isUrlCached($url)) {
                        $summary['skipped_already_cached']++;
                        continue;
                    }
                    $toWarm[] = $url;
                    if (count($toWarm) >= $limit) {
                        break;
                    }
                }
            }

            $summary['selected_to_warm'] = count($toWarm);

            if (!empty($toWarm) && function_exists('curl_multi_init')) {
                foreach (array_chunk($toWarm, $concurrency) as $batch) {
                    $loadNow = $this->getCurrentLoadAvg();
                    if ($loadNow !== null && $loadNow >= $effectiveMaxLoadAvg) {
                        $summary['status'] = 'paused_high_load';
                        $summary['load_avg_1m'] = $loadNow;
                        if (!empty($queuedTags)) {
                            $hasRemainingQueuedUrls = true;
                        }
                        break;
                    }
                    $batchResults = $this->executeBatchRequests($batch);
                    foreach ($batchResults as $url => $info) {
                        $inFpc = $this->isUrlCached($url);
                        if ($info['http_code'] === 200) {
                            $summary['warmed_count']++;
                        }
                        if ($inFpc) {
                            $summary['verified_in_fpc']++;
                        }
                        $summary['warmed_urls'][] = [
                            'url' => $url,
                            'http_code' => $info['http_code'],
                            'cf_cache_status' => $info['cf_cache_status'],
                            'time_sec' => $info['time_sec'],
                            'in_fpc' => $inFpc,
                        ];
                    }
                }
            }

            if ($hasRemainingQueuedUrls && !empty($queuedTags)) {
                $this->requeueTags($queuedTags);
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
        return (bool)$this->fullPageCache->test($this->getCacheIdForUrl($url));
    }

    /**
     * Check whether the priority tag queue file currently has pending invalidated tags.
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
        return is_array($data) && (!empty($data['tags']) || !empty($data['flush_all']));
    }

    /**
     * Re-enqueue tags into the priority queue file when a warming run hits $limit or pauses.
     *
     * @param string[] $tags
     * @return void
     */
    private function requeueTags(array $tags): void
    {
        if (empty($tags)) {
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
                $data = ['flush_all' => false, 'tags' => [], 'updated_at' => time()];
            }
            $existingTags = isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : [];
            $merged = array_values(array_unique(array_merge($tags, $existingTags)));
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
    }

    /**
     * Atomically consume and clear the event-driven tag queue file.
     *
     * @return array{flush_all: bool, tags: string[]}
     */
    private function consumeQueue(): array
    {
        $result = ['flush_all' => false, 'tags' => []];
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
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, '{"flush_all":false,"tags":[],"updated_at":' . time() . '}');
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
     * Execute concurrent guest GET requests for a small batch of URLs using curl_multi_init().
     *
     * @param string[] $urls
     * @return array<string, array{http_code: int, cf_cache_status: string, time_sec: float}>
     */
    private function executeBatchRequests(array $urls): array
    {
        $mh = curl_multi_init();
        $handles = [];
        $headersMap = [];

        foreach ($urls as $url) {
            $ch = curl_init($url);
            if ($ch === false) {
                continue;
            }
            $headersMap[$url] = ['cf_cache_status' => 'NONE'];
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_ENCODING => '',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 SmokedayCacheWarmer/1.0',
                ],
                CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use ($url, &$headersMap): int {
                    if (stripos($headerLine, 'cf-cache-status:') === 0) {
                        $headersMap[$url]['cf_cache_status'] = trim(substr($headerLine, 16));
                    }
                    return strlen($headerLine);
                },
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$url] = $ch;
        }

        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh, 1.0);
            }
        } while ($active && $status === CURLM_OK);

        $results = [];
        foreach ($handles as $url => $ch) {
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $totalTime = round((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME), 3);
            $results[$url] = [
                'http_code' => $httpCode,
                'cf_cache_status' => $headersMap[$url]['cf_cache_status'] ?? 'NONE',
                'time_sec' => $totalTime,
            ];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $results;
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
