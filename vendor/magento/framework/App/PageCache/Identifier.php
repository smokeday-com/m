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
         * Root Cause:
         * `Identifier::getValue()` previously passed `$this->request->getUriString()` verbatim into the SHA-1
         * cache key array for Built-in FPC (Redis DB 1). Whenever a request contained marketing tracking
         * query parameters (e.g. `gclid`, `fbclid`, `msclkid`, `srsltid`, `utm_*`, `_ga`, `_gl`, `mc_cid`,
         * `mc_eid`, `twclid`, `ttclid`, `yclid`, `_bta_*`, `trk_*`, `gdf*`, `_ke`, `kx`, `dm_i`, `ef_id`,
         * `s_kwcid`), including on paginated or filtered category URLs (`/bongs-water-pipes.html?p=2&gclid=...`)
         * or when `X-Magento-Vary` bypassed edge caching, the unique tracking token produced a distinct SHA-1
         * key and forced a full Magento cache miss (~19.75 s on large category pages).
         * Solution:
         * Strip known marketing tracking query parameters while preserving all functional catalog/store
         * query parameters (`p`, `price`, `cat`, `product_list_*`, `q`, `ea`, `___store`, etc.) in their
         * exact original order and encoding before computing the SHA-1 identifier.
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
     * Strip marketing tracking query parameters from URI string while preserving functional query parameters.
     *
     * @param string $uriString
     * @return string
     */
    private function getNormalizedUriString(string $uriString): string
    {
        $qPos = strpos($uriString, '?');
        if ($qPos === false) {
            return $uriString;
        }
        $baseUri = substr($uriString, 0, $qPos);
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
