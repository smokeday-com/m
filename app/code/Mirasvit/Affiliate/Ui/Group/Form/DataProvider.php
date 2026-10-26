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

namespace Mirasvit\Affiliate\Ui\Group\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirasvit\Affiliate\Api\Data\GroupInterface;
use Mirasvit\Affiliate\Repository\GroupRepository;

class DataProvider extends AbstractDataProvider
{
    private $accountModifier;

    public function __construct(
        GroupRepository          $groupRepository,
        Modifier\AccountModifier $accountModifier,
        string                   $name,
        string                   $primaryFieldName,
        string                   $requestFieldName,
        array                    $meta = [],
        array                    $data = []
    ) {
        $this->accountModifier = $accountModifier;
        $this->collection      = $groupRepository->getCollection();

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getMeta(): array
    {
        $meta = parent::getMeta();

        $meta = $this->accountModifier->modifyMeta($meta);

        return $meta;
    }

    public function getData(): array
    {
        $result = [];

        foreach ($this->getCollection() as $item) {
            $result[$item->getId()] = [
                GroupInterface::ID             => $item->getId(),
                GroupInterface::NAME           => $item->getName(),
                GroupInterface::IS_DEFAULT     => $item->getIsDefault() ? '1' : '0',
                GroupInterface::IS_AUTO_UPDATE => $item->getIsAutoUpdate() ? '1' : '0',
                GroupInterface::IS_VISIBLE     => $item->getIsVisible() ? '1' : '0',
                GroupInterface::PRIORITY       => $item->getPriority(),
                GroupInterface::DESCRIPTION    => $item->getDescription(),
            ];

            $result = $this->accountModifier->modifyData($result, $item->getId());
        }

        return $result;
    }
}
