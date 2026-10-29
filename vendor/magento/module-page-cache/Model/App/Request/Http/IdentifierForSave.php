<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PageCache\Model\App\Request\Http;

use Magento\Framework\App\Http\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\PageCache\Identifier;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\PageCache\IdentifierInterface;

/**
 * Page unique identifier
 */
class IdentifierForSave implements IdentifierInterface
{
    /**
     * @param Http $request
     * @param Context $context
     * @param Json $serializer
     */
    public function __construct(
        private Http $request,
        private Context $context,
        private Json $serializer
    ) {
    }

    /**
     * Return unique page identifier
     *
     * @return string
     */
    public function getValue()
    {
        /**
         * 2026-10-29
         * "Disable catalog/seo/product_use_categories (M-PRODUCT-URL-1) and normalize category-prefixed product URLs
         * to canonical product URLs in Identifier::getValue() and IdentifierForSave::getValue() (M-PRODUCT-URL-2 / M-PRODUCT-URL-3)":
         * https://github.com/smokeday-com/m/issues/45
         * Root Cause:
         * `Magento\Framework\App\PageCache\Kernel::process()` uses `IdentifierForSave::getValue()` when saving a
         * rendered page into Built-in FPC (Redis DB 1), whereas `Kernel::load()` uses `Identifier::getValue()`
         * when looking up the cached page. Passing raw `$this->request->getUriString()` here caused cold requests
         * on category-prefixed product URLs (`/<category>/<product>.html`) or marketing-tagged URLs to save under
         * an unnormalized SHA-1 key instead of the canonical product SHA-1 key used by `Identifier::getValue()`.
         * Solution:
         * Normalize `$this->request->getUriString()` through `Identifier::getNormalizedUriString()` so both read
         * (`Kernel::load()`) and write (`Kernel::process()`) paths compute the exact same SHA-1 cache identifier.
         */
        /** @var Identifier $identifier */
        $identifier = ObjectManager::getInstance()->get(Identifier::class);
        $data = [
            $this->request->isSecure(),
            $identifier->getNormalizedUriString((string) $this->request->getUriString()),
            $this->context->getVaryString()
        ];

        return sha1($this->serializer->serialize($data));
    }
}
