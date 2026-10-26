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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Group;

use Magento\Framework\Filter\Input;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Mirasvit\Affiliate\Api\Data\GroupInterface;
use Mirasvit\Core\Service\SerializeService;

class PostDataProcessor
{
    private $dateFilter;

    private $inputFilter;

    public function __construct(
        Input $inputFilter,
        Date  $dateFilter
    ) {
        $this->inputFilter = $inputFilter;
        $this->dateFilter  = $dateFilter;
    }

    public function filter(GroupInterface $program, array $data): array
    {
        $rule = $program->getRule();

        $conditions = $program->getRule()->getConditions()->asArray();

        if (isset($data['rule']) && isset($data['rule']['conditions'])) {
            $rule->loadPost(['conditions' => $data['rule']['conditions']]);

            $conditions = $rule->getConditions()->asArray();
        }

        $conditions = SerializeService::encode($conditions);

        $data[GroupInterface::CONDITIONS_SERIALIZED] = $conditions;

        $filterRules = [];

        $data = $this->inputFilter->addFilters($filterRules)->filter($data);

        return (array)$data;
    }
}
