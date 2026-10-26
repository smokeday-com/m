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

namespace Mirasvit\Affiliate\Model\CouponGenerate;

class PatternPart extends \Magento\Framework\DataObject
{
    public const CHAR           = 'char';
    public const GENERATOR      = 'generator';
    public const TYPE           = 'type';
    public const TYPE_CHAR      = 'type_char';
    public const TYPE_GENERATOR = 'type_generator';

    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    public function getChar(): string
    {
        return (string)$this->getData(self::CHAR);
    }

    public function getGenerator(): ?CharGenerator
    {
        return $this->getData(self::GENERATOR);
    }
}
