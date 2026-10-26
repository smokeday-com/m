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

namespace Mirasvit\Affiliate\Model\Program\Rule;

use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\AbstractModel;
use Mirasvit\Affiliate\Model\Program\Condition\CombineFactory as ConditionCombineFactory;

class ProgramRule extends AbstractModel
{
    public const FORM_NAME = 'affiliate_program_form';

    private $conditionCombineFactory;

    public function __construct(
        ConditionCombineFactory $conditionCombineFactory,
        Context                 $context,
        Registry                $registry,
        FormFactory             $formFactory,
        TimezoneInterface       $localeDate
    ) {
        $this->conditionCombineFactory = $conditionCombineFactory;

        parent::__construct($context, $registry, $formFactory, $localeDate);
    }

    // phpcs:disable
    /**
     * @return \Magento\Rule\Model\Action\Collection|void
     */
    public function getActionsInstance()
    {
    }
    // phpcs:enable

    /**
     * @return \Magento\Rule\Model\Condition\Combine|\Mirasvit\Affiliate\Model\Program\Condition\Combine
     */
    public function getConditionsInstance()
    {
        return $this->conditionCombineFactory->create();
    }
}
