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

use Magento\Rule\Model\Condition\AbstractCondition;
use Mirasvit\Affiliate\Controller\Adminhtml\Group;
use Mirasvit\Affiliate\Model\Group\Rule\GroupRule;

class NewConditionHtml extends Group
{
    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));

        $class     = $typeArr[0];
        $attribute = false;
        if (count($typeArr) == 2) {
            $attribute = $typeArr[1];
        }

        $model = $this->context->getObjectManager()->create($class)
            ->setId($id)
            ->setType($class)
            ->setRule($this->context->getObjectManager()->create(GroupRule::class))
            ->setPrefix('conditions')
            ->setFormName($this->getRequest()->getParam('form_name', GroupRule::FORM_NAME));

        $model->setAttribute($attribute);

        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }

        $this->getResponse()->setBody($html);
    }
}
