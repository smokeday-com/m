<?php

namespace Macrew\MegamenuFix\Block\Html;

use Magento\Framework\DataObject\IdentityInterface;

class Megamenu extends \Olegnax\MegaMenu\Block\Html\Megamenu implements IdentityInterface
{
    public function getIdentities()
    {
        return ['CATALOG_CATEGORY'];
    }
}