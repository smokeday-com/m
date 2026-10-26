<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Affiliate\Service;

use DOMDocument;
use DOMElement;

class WidgetService
{

    public function clearHtml(string $content): ?string
    {
        $domDocument = $this->getDomDocument($content);
        if (!$domDocument) {
            return null;
        }

        $this->removeJs($domDocument);
        $newContent = $domDocument->saveHTML();

        return $newContent ? : null;
    }

    private function removeJs(DOMDocument $domDocument): void
    {
        $scriptTags = $domDocument->getElementsByTagName('script');
        $removeList = [];
        /** @var DOMElement $domElement */
        foreach ($scriptTags as $domElement) {
            if ($this->isRequired($domElement)) {
                continue;
            }
            if ($this->isRequireJsConfig($domElement)) {
                continue;
            }
            $removeList[] = $domElement;
        }
        foreach ($removeList as $domElement) {
            $domElement->parentNode->removeChild($domElement);
        }
    }

    private function getDomDocument(string $html): ?DOMDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loadResult = $dom->loadHTML($html);
        libxml_use_internal_errors(false);
        if ($loadResult) {
            return $dom;
        }

        return null;
    }

    private function isRequired(DOMElement $domElement): bool
    {
        $requiredList = [
            '/requirejs/require.js',
            '/requirejs/mixins.js',
            '/requirejs-config.js',
        ];
        $src          = $domElement->getAttribute('src');
        foreach ($requiredList as $name) {
            if (strpos($src, $name) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isRequireJsConfig(DOMElement $domElement): bool
    {
        $content = $domElement->nodeValue;
        if (strpos($content, 'var BASE_URL =') !== false
            && strpos($content, 'var require =') !== false) {
            return true;
        }

        return false;
    }
}
