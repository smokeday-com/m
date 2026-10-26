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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Program;

use Magento\Framework\Filter\Input;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Core\Service\CompatibilityService;
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

    public function filter(ProgramInterface $program, array $data): array
    {
        $rule = $program->getRule();

        $conditions = $program->getRule()->getConditions()->asArray();

        if (isset($data['rule']) && isset($data['rule']['conditions'])) {
            $rule->loadPost(['conditions' => $data['rule']['conditions']]);

            $conditions = $rule->getConditions()->asArray();
        }

        if (CompatibilityService::is21()) {
            $conditions = call_user_func_array('serialize', [$conditions]); // phpcs:ignore
        } else {
            $conditions = SerializeService::encode($conditions);
        }

        $data[ProgramInterface::CONDITIONS_SERIALIZED] = $conditions;

        $filterRules = [];

        foreach ([ProgramInterface::ACTIVE_FROM, ProgramInterface::ACTIVE_TO] as $dateField) {
            if (!empty($data[$dateField])) {
                $filterRules[$dateField] = $this->dateFilter;
                $data[$dateField]        = $this->dateFilter->filter($data[$dateField]);
            }
        }
        $data = $this->inputFilter->addFilters($filterRules)->filter($data);

        if (!isset($data[ProgramInterface::TIERS])) {
            $data[ProgramInterface::TIERS] = [];
        }

        return (array)$data;
    }
}
