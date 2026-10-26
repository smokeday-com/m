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

namespace Mirasvit\Affiliate\Plugin;

use Magento\Email\Model\AbstractTemplate;
use Magento\Framework\Model\AbstractModel;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Core\Service\CompatibilityService;

class SetMailTemplateLegacyFlagPlugin
{
    private $configProvider;

    private $templateIds;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param AbstractTemplate $subject
     * @param AbstractModel    $result
     *
     * @return AbstractModel
     */
    public function afterLoad(AbstractTemplate $subject, AbstractModel $result)
    {
        $version = CompatibilityService::getVersion();
        if (version_compare($version, '2.3.4', '<') || version_compare($version, '2.4.4', '>=')) {
            return $result;
        }

        $modelId = $result->getId();
        if (in_array($modelId, $this->getTemplateIds())) {
            $result->setData('is_legacy', 1);
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getTemplateIds()
    {
        if (!$this->templateIds) {
            $ids = [
                $this->configProvider->getTemplateIdAdminAccountNew(),
                $this->configProvider->getTemplateIdAdminWithdrawNew(),
                $this->configProvider->getTemplateIdCustomerAccountApproved(),
                $this->configProvider->getTemplateIdCustomerAccountNew(),
                $this->configProvider->getTemplateIdCustomerTransactionNew(),
                $this->configProvider->getTemplateIdCustomerUpdateBalance(),
            ];

            $ids = array_filter($ids, function ($id) {
                return is_numeric($id);
            });

            $this->templateIds = array_values($ids);
        }

        return $this->templateIds;
    }
}
