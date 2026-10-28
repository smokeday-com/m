<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PageCache\Model\Cache;

/**
 * System / Cache Management / Cache type "Full Page Cache"
 *
 */
class Type extends \Magento\Framework\Cache\Frontend\Decorator\TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    const TYPE_IDENTIFIER = 'full_page';

    /**
     * Cache tag used to distinguish the cache type from all other cache
     */
    const CACHE_TAG = 'FPC';

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Emit Cache-Tag HTTP header from Magento\Framework\App\PageCache\Kernel and automatically purge Cloudflare Edge Cache on cache invalidation events":
    # https://github.com/smokeday-com/m/issues/12
    private array $purgedCloudflareTags = [];
    private bool $purgedCloudflareAll = false;

    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Decouple Cloudflare Cache-Tag purge HTTP requests into an asynchronous lock-safe queue (var/cloudflare_purge_queue.json) with rate-limited batch flushing":
    # https://github.com/smokeday-com/m/issues/33
    private const CF_QUEUE_REL_PATH = '/var/cloudflare_purge_queue.json';
    private const CF_MAX_QUEUE_TAGS = 5000;
    private const CF_CHUNK_DELAY_US = 220000;

    /**
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
        $this->eventManager = $eventManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $mode
     * @param array $tags
     * @return bool
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Calibrate FPC Warmer load threshold (15.0 / 25.0 for priority queue), run every minute, prioritize Category Page 1 URLs, and guard Type::purgeCloudflare() / Warmer::enqueueTags() against empty tag arrays":
        # https://github.com/smokeday-com/m/issues/26
        if ($mode !== \Zend_Cache::CLEANING_MODE_ALL && empty($tags)) {
            return true;
        }
        $this->eventManager->dispatch('adminhtml_cache_refresh_type');
        $result = parent::clean($mode, $tags);
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Emit Cache-Tag HTTP header from Magento\Framework\App\PageCache\Kernel and automatically purge Cloudflare Edge Cache on cache invalidation events":
        # https://github.com/smokeday-com/m/issues/12
        $this->purgeCloudflare((string)$mode, $tags);
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Implement event-driven and prioritized cron Full Page Cache & Cloudflare Edge Cache warmer (page-cache:warm / magento_pagecache_warm)":
        # https://github.com/smokeday-com/m/issues/19
        Warmer::enqueueTags((string)$mode, $tags);
        return $result;
    }

    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Emit Cache-Tag HTTP header from Magento\Framework\App\PageCache\Kernel and automatically purge Cloudflare Edge Cache on cache invalidation events":
    # https://github.com/smokeday-com/m/issues/12
    public function purgeCloudflare(string $mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = []): void
    {
        if ($this->purgedCloudflareAll || !function_exists('curl_init')) {
            return;
        }
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Calibrate FPC Warmer load threshold (15.0 / 25.0 for priority queue), run every minute, prioritize Category Page 1 URLs, and guard Type::purgeCloudflare() / Warmer::enqueueTags() against empty tag arrays":
        # https://github.com/smokeday-com/m/issues/26
        if ($mode !== \Zend_Cache::CLEANING_MODE_ALL && empty($tags)) {
            return;
        }
        try {
            $tagsToPurge = [];
            if ($mode === \Zend_Cache::CLEANING_MODE_ALL) {
                $tagsToPurge = [self::CACHE_TAG];
                $this->purgedCloudflareAll = true;
            } else {
                foreach ($tags as $tag) {
                    $tagStr = trim((string)$tag);
                    if ($tagStr === '') {
                        continue;
                    }
                    $lower = strtolower($tagStr);
                    if ($lower === 'fpc' || $lower === 'mage') {
                        $tagsToPurge = [self::CACHE_TAG];
                        $this->purgedCloudflareAll = true;
                        break;
                    }
                    if (!isset($this->purgedCloudflareTags[$lower])) {
                        $this->purgedCloudflareTags[$lower] = true;
                        $tagsToPurge[] = $tagStr;
                    }
                }
                # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
                # "Remove >90 tags-to-FPC escalation in Magento\PageCache\Model\Cache\Type::purgeCloudflare() and add stack-trace audit logging to var/log/cloudflare_purge.log":
                # https://github.com/smokeday-com/m/issues/31
            }

            if (empty($tagsToPurge)) {
                return;
            }

            # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
            # "Decouple Cloudflare Cache-Tag purge HTTP requests into an asynchronous lock-safe queue (var/cloudflare_purge_queue.json) with rate-limited batch flushing":
            # https://github.com/smokeday-com/m/issues/33
            self::enqueueCloudflarePurgeTags($tagsToPurge);
            self::logCloudflarePurge('enqueued', $mode, $tagsToPurge);
        } catch (\Throwable $e) {
            // Do not interrupt application flow if queue write fails
        }
    }

    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Decouple Cloudflare Cache-Tag purge HTTP requests into an asynchronous lock-safe queue (var/cloudflare_purge_queue.json) with rate-limited batch flushing":
    # https://github.com/smokeday-com/m/issues/33
    public static function enqueueCloudflarePurgeTags(array $tagsToPurge): void
    {
        if (empty($tagsToPurge) || !defined('BP')) {
            return;
        }
        $queueFile = self::getCloudflareQueueFilePath();
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
            $existing = (is_array($data) && isset($data['tags']) && is_array($data['tags'])) ? $data['tags'] : [];
            $byLower = [];
            foreach ($existing as $t) {
                $ts = trim((string)$t);
                if ($ts !== '') {
                    $byLower[strtolower($ts)] = $ts;
                }
            }
            foreach ($tagsToPurge as $t) {
                $ts = trim((string)$t);
                if ($ts !== '') {
                    $byLower[strtolower($ts)] = $ts;
                }
            }
            if (isset($byLower['fpc'])) {
                $merged = [self::CACHE_TAG];
            } else {
                $merged = array_values($byLower);
                if (count($merged) > self::CF_MAX_QUEUE_TAGS) {
                    $merged = array_slice($merged, 0, self::CF_MAX_QUEUE_TAGS);
                }
            }
            $payload = [
                'tags' => $merged,
                'updated_at' => time(),
            ];
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string)json_encode($payload, JSON_UNESCAPED_SLASHES));
            fflush($fp);
            @flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    /**
     * Atomically drain var/cloudflare_purge_queue.json and send 30-tag chunks to Cloudflare API
     * respecting the 5 requests/sec rate limit.
     *
     * @return array<string, mixed>
     */
    public static function flushCloudflareQueue(): array
    {
        $summary = [
            'queued_tags' => 0,
            'chunks_sent' => 0,
            'purged_tags' => 0,
            'requeued_tags' => 0,
            'http_codes' => [],
        ];
        if (!defined('BP') || !function_exists('curl_init')) {
            return $summary;
        }
        $queueFile = self::getCloudflareQueueFilePath();
        if (!is_file($queueFile)) {
            return $summary;
        }
        $tagsToPurge = [];
        $fp = @fopen($queueFile, 'c+');
        if ($fp === false) {
            return $summary;
        }
        if (@flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (is_array($data) && !empty($data['tags']) && is_array($data['tags'])) {
                $tagsToPurge = array_values(array_unique(array_map('strval', $data['tags'])));
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, '{"tags":[],"updated_at":' . time() . '}');
            fflush($fp);
            @flock($fp, LOCK_UN);
        }
        fclose($fp);

        if (empty($tagsToPurge)) {
            return $summary;
        }
        $summary['queued_tags'] = count($tagsToPurge);

        try {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Framework\App\DeploymentConfig $deploymentConfig */
            $deploymentConfig = $om->get(\Magento\Framework\App\DeploymentConfig::class);
            $cfConfig = $deploymentConfig->get('cloudflare');
            if (!is_array($cfConfig) || empty($cfConfig['zone_id']) || empty($cfConfig['api_token'])) {
                return $summary;
            }
            $zoneId = (string)$cfConfig['zone_id'];
            $apiToken = (string)$cfConfig['api_token'];
            $endpoint = sprintf('https://api.cloudflare.com/client/v4/zones/%s/purge_cache', $zoneId);

            $failedTags = [];
            foreach (array_values(array_chunk($tagsToPurge, 30)) as $idx => $chunk) {
                if ($idx > 0) {
                    usleep(self::CF_CHUNK_DELAY_US);
                }
                $ch = curl_init($endpoint);
                if ($ch === false) {
                    $failedTags = array_merge($failedTags, $chunk);
                    continue;
                }
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_TIMEOUT => 6,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $apiToken,
                        'Content-Type: application/json',
                    ],
                    CURLOPT_POSTFIELDS => json_encode(['tags' => array_values($chunk)]),
                ]);
                $respBody = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $summary['http_codes'][] = $httpCode;

                $decoded = is_string($respBody) && $respBody !== '' ? json_decode($respBody, true) : null;
                $ok = ($httpCode === 200) && (!is_array($decoded) || !empty($decoded['success']));
                if ($ok) {
                    $summary['chunks_sent']++;
                    $summary['purged_tags'] += count($chunk);
                } else {
                    $failedTags = array_merge($failedTags, $chunk);
                }
            }

            if (!empty($failedTags)) {
                $summary['requeued_tags'] = count($failedTags);
                self::enqueueCloudflarePurgeTags($failedTags);
            }

            self::logCloudflarePurge('queue_flushed', 'ASYNC_QUEUE', $tagsToPurge, [
                'chunks_sent' => $summary['chunks_sent'],
                'purged_tags' => $summary['purged_tags'],
                'requeued_tags' => $summary['requeued_tags'],
                'http_codes' => $summary['http_codes'],
            ]);
        } catch (\Throwable $e) {
            self::enqueueCloudflarePurgeTags($tagsToPurge);
            $summary['requeued_tags'] = count($tagsToPurge);
        }

        return $summary;
    }

    private static function getCloudflareQueueFilePath(): string
    {
        return str_replace('\\', '/', BP) . self::CF_QUEUE_REL_PATH;
    }

    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Remove >90 tags-to-FPC escalation in Magento\PageCache\Model\Cache\Type::purgeCloudflare() and add stack-trace audit logging to var/log/cloudflare_purge.log":
    # https://github.com/smokeday-com/m/issues/31
    public static function logCloudflarePurge(string $event, string $mode, array $tags, array $extra = []): void
    {
        if (!defined('BP')) {
            return;
        }
        try {
            $basePath = str_replace('\\', '/', BP);
            $logDir = $basePath . '/var/log';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $frames = [];
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15) as $idx => $frame) {
                if ($idx === 0) {
                    continue;
                }
                $cls = isset($frame['class']) ? ($frame['class'] . ($frame['type'] ?? '::')) : '';
                $fn = $cls . ($frame['function'] ?? '');
                $file = isset($frame['file'])
                    ? ltrim(str_replace($basePath, '', str_replace('\\', '/', (string)$frame['file'])), '/')
                    : '';
                $line = $frame['line'] ?? 0;
                $frames[] = $file !== '' ? sprintf('%s (%s:%d)', $fn, $file, $line) : $fn;
            }
            $entry = array_merge([
                'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                'event' => $event,
                'mode' => $mode,
                'tags_count' => count($tags),
                'tags_sample' => array_slice(array_values($tags), 0, 50),
                'sapi' => PHP_SAPI,
                'uri' => $_SERVER['REQUEST_URI'] ?? (isset($_SERVER['argv']) ? implode(' ', (array)$_SERVER['argv']) : 'CLI'),
                'pid' => (int)getmypid(),
            ], $extra, [
                'stack' => $frames,
            ]);
            @file_put_contents(
                $logDir . '/cloudflare_purge.log',
                json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n",
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable $e) {
            // Never interrupt application flow on logging error
        }
    }
}
