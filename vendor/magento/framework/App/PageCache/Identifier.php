<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\PageCache;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Page unique identifier
 */
class Identifier implements IdentifierInterface
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $context;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Http\Context $context
     * @param Json|null $serializer
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Http\Context $context,
        Json $serializer = null
    ) {
        $this->request = $request;
        $this->context = $context;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Exact marketing query parameter names stripped before computing the Built-in FPC key.
     *
     * 2026-10-26
     * "Normalize marketing tracking query parameters in Magento\Framework\App\PageCache\Identifier::getValue()
     * for Built-in FPC": https://github.com/smokeday-com/m/issues/13
     */
    private const MARKETING_QUERY_PARAMS = [
        'gclid' => true,
        'fbclid' => true,
        'msclkid' => true,
        'srsltid' => true,
        'gbraid' => true,
        'wbraid' => true,
        'dclid' => true,
        'twclid' => true,
        'ttclid' => true,
        'yclid' => true,
        'mc_cid' => true,
        'mc_eid' => true,
        '_ga' => true,
        '_gl' => true,
        '_ke' => true,
        'kx' => true,
        'dm_i' => true,
        'ef_id' => true,
        's_kwcid' => true,
    ];

    /**
     * Marketing query parameter prefixes stripped before computing the Built-in FPC key.
     */
    private const MARKETING_QUERY_PREFIXES = [
        'utm_',
        '_bta_',
        'trk_',
        'gdf',
        '_ga_',
    ];

    /**
     * Per-process cache mapping multi-segment request paths (`<category>/<product>.html`) to canonical
     * product request paths (`<product>.html`), or `null` when the path is not a category-prefixed product URL.
     *
     * 2026-10-29
     * "Disable catalog/seo/product_use_categories (M-PRODUCT-URL-1) and normalize category-prefixed product URLs
     * to canonical product URLs in Identifier::getValue() and IdentifierForSave::getValue() (M-PRODUCT-URL-2 / M-PRODUCT-URL-3)":
     * https://github.com/smokeday-com/m/issues/45
     *
     * @var array<string, string|null>
     */
    private static array $canonicalPathCache = [];

    /**
     * Return unique page identifier
     *
     * @return string
     */
    public function getValue()
    {
        /**
         * 2026-10-26
         * "Normalize marketing tracking query parameters in Magento\Framework\App\PageCache\Identifier::getValue()
         * for Built-in FPC": https://github.com/smokeday-com/m/issues/13
         *
         * 2026-10-29
         * "Disable catalog/seo/product_use_categories (M-PRODUCT-URL-1) and normalize category-prefixed product URLs
         * to canonical product URLs in Identifier::getValue() and IdentifierForSave::getValue() (M-PRODUCT-URL-2 / M-PRODUCT-URL-3)":
         * https://github.com/smokeday-com/m/issues/45
         * Root Cause:
         * 1) `Identifier::getValue()` previously passed `$this->request->getUriString()` verbatim into the SHA-1
         * cache key array for Built-in FPC (Redis DB 1). Whenever a request contained marketing tracking
         * query parameters (e.g. `gclid`, `fbclid`, `msclkid`, `srsltid`, `utm_*`, `_ga`, `_gl`, `mc_cid`,
         * `mc_eid`, `twclid`, `ttclid`, `yclid`, `_bta_*`, `trk_*`, `gdf*`, `_ke`, `kx`, `dm_i`, `ef_id`,
         * `s_kwcid`), the unique tracking token produced a distinct SHA-1 key and forced a full Magento cache miss.
         * 2) `url_rewrite` contains 18,203 category-prefixed product rows (`/<category-path>/<product>.html`,
         * `metadata IS NOT NULL`) alongside the 4,320 canonical product rows (`/<product>.html`, `metadata IS NULL`)
         * warmed by `Magento\PageCache\Model\Cache\Warmer`. Requests to `/<category-path>/<product>.html`
         * computed a different SHA-1 key (`zc:k:4da_...`) than the warmed canonical product URL and suffered a
         * ~2.37 s - 2.60 s cold render despite rendering identical product HTML with `<link rel="canonical">`
         * pointing to `/<product>.html`.
         * Solution:
         * Normalize category-prefixed product paths to their canonical product path (`/<product>.html`) and strip
         * known marketing tracking query parameters while preserving all functional catalog/store query parameters
         * (`p`, `price`, `cat`, `product_list_*`, `q`, `ea`, `___store`, etc.) before computing the SHA-1 identifier.
         */
        $data = [
            $this->request->isSecure(),
            $this->getNormalizedUriString((string) $this->request->getUriString()),
            $this->request->get(\Magento\Framework\App\Response\Http::COOKIE_VARY_STRING)
                ?: $this->context->getVaryString()
        ];

        return sha1($this->serializer->serialize($data));
    }

    /**
     * Normalize category-prefixed product path to canonical product path and strip marketing tracking
     * query parameters from URI string while preserving functional query parameters.
     *
     * 2026-10-29
     * Made `public` so `Magento\PageCache\Model\App\Request\Http\IdentifierForSave::getValue()` shares the
     * exact same URI normalization and per-process canonical path cache:
     * https://github.com/smokeday-com/m/issues/45
     *
     * @param string $uriString
     * @return string
     */
    public function getNormalizedUriString(string $uriString): string
    {
        $qPos = strpos($uriString, '?');
        if ($qPos === false) {
            $hashPos = strpos($uriString, '#');
            if ($hashPos !== false) {
                $baseUri = $this->normalizeProductCategoryUri(substr($uriString, 0, $hashPos));
                return $baseUri . substr($uriString, $hashPos);
            }
            return $this->normalizeProductCategoryUri($uriString);
        }
        $baseUri = $this->normalizeProductCategoryUri(substr($uriString, 0, $qPos));
        $rest = substr($uriString, $qPos + 1);
        $fragment = '';
        $hashPos = strpos($rest, '#');
        if ($hashPos !== false) {
            $fragment = substr($rest, $hashPos);
            $query = substr($rest, 0, $hashPos);
        } else {
            $query = $rest;
        }
        if ($query === '') {
            return $baseUri . $fragment;
        }
        $keptParts = [];
        foreach (explode('&', $query) as $part) {
            if ($part === '') {
                continue;
            }
            $eqPos = strpos($part, '=');
            $rawKey = $eqPos === false ? $part : substr($part, 0, $eqPos);
            $key = strtolower(rawurldecode($rawKey));
            if ($this->isMarketingQueryParam($key)) {
                continue;
            }
            $keptParts[] = $part;
        }
        if (empty($keptParts)) {
            return $baseUri . $fragment;
        }
        return $baseUri . '?' . implode('&', $keptParts) . $fragment;
    }

    /**
     * Rewrite a multi-segment category-prefixed product URI (`https://host/<category-path>/<product>.html`)
     * to its canonical product URI (`https://host/<product>.html`) when a canonical autogenerated product
     * rewrite (`metadata IS NULL`) exists in `url_rewrite`.
     *
     * Single-segment paths (`/<product>.html`, `/<category>.html`), homepage (`/`), and non-`.html` paths
     * return immediately without touching `ResourceConnection` or querying the database.
     *
     * 2026-10-29
     * https://github.com/smokeday-com/m/issues/45
     *
     * @param string $baseUri
     * @return string
     */
    private function normalizeProductCategoryUri(string $baseUri): string
    {
        $schemePos = strpos($baseUri, '://');
        if ($schemePos !== false) {
            $pathPos = strpos($baseUri, '/', $schemePos + 3);
            if ($pathPos === false) {
                return $baseUri;
            }
            $originPrefix = substr($baseUri, 0, $pathPos);
            $path = substr($baseUri, $pathPos);
        } else {
            $originPrefix = '';
            $path = $baseUri;
        }

        $trimmedPath = ltrim($path, '/');
        if (
            $trimmedPath === ''
            || strpos($trimmedPath, '/') === false
            || !str_ends_with(strtolower($trimmedPath), '.html')
        ) {
            return $baseUri;
        }

        $canonicalPath = $this->resolveCanonicalProductRequestPath($trimmedPath);
        if ($canonicalPath === null) {
            return $baseUri;
        }

        return $originPrefix . '/' . $canonicalPath;
    }

    /**
     * Resolve the canonical root `request_path` (`metadata IS NULL`) for a category-prefixed product
     * `request_path` (`metadata IS NOT NULL`) via indexed `url_rewrite` lookup.
     *
     * Uses composite unique index `URL_REWRITE_REQUEST_PATH_STORE_ID (request_path, store_id)` on `c`
     * and index `URL_REWRITE_STORE_ID_ENTITY_ID (store_id, entity_id)` on `r`.
     *
     * 2026-10-29
     * https://github.com/smokeday-com/m/issues/45
     *
     * @param string $trimmedPath
     * @return string|null
     */
    private function resolveCanonicalProductRequestPath(string $trimmedPath): ?string
    {
        if (array_key_exists($trimmedPath, self::$canonicalPathCache)) {
            return self::$canonicalPathCache[$trimmedPath];
        }

        try {
            /** @var \Magento\Framework\App\ResourceConnection $resource */
            $resource = ObjectManager::getInstance()->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('url_rewrite');
            $sql = sprintf(
                'SELECT r.request_path FROM %s AS c '
                . 'INNER JOIN %s AS r '
                . 'ON r.store_id = c.store_id '
                . 'AND r.entity_id = c.entity_id '
                . 'AND r.entity_type = %s '
                . 'AND r.redirect_type = 0 '
                . 'AND r.is_autogenerated = 1 '
                . 'AND r.metadata IS NULL '
                . 'WHERE c.request_path = :request_path '
                . 'AND c.entity_type = %s '
                . 'AND c.redirect_type = 0 '
                . 'AND c.metadata IS NOT NULL '
                . 'ORDER BY c.store_id ASC '
                . 'LIMIT 1',
                $connection->quoteIdentifier($tableName),
                $connection->quoteIdentifier($tableName),
                $connection->quote('product'),
                $connection->quote('product')
            );
            $canonicalPath = $connection->fetchOne($sql, ['request_path' => $trimmedPath]);
            $resolved = (is_string($canonicalPath) && $canonicalPath !== '')
                ? ltrim($canonicalPath, '/')
                : null;
        } catch (\Throwable $e) {
            return null;
        }

        if (count(self::$canonicalPathCache) >= 1024) {
            self::$canonicalPathCache = [];
        }
        self::$canonicalPathCache[$trimmedPath] = $resolved;

        return $resolved;
    }

    /**
     * Check whether a lowercased query parameter name is a marketing tracking parameter.
     *
     * @param string $key
     * @return bool
     */
    private function isMarketingQueryParam(string $key): bool
    {
        if (isset(self::MARKETING_QUERY_PARAMS[$key])) {
            return true;
        }
        foreach (self::MARKETING_QUERY_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
