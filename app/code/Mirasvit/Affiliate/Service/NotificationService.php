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

use Magento\Email\Model\Transport;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\Config\NotificationConfig;
use Mirasvit\Core\Service\CompatibilityService;

class NotificationService
{
    private $accountService;

    private $transportBuilder;

    private $storeManager;

    private $configProvider;

    private $scopeConfig;

    public function __construct(
        AccountService        $accountService,
        TransportBuilder      $transportBuilder,
        StoreManagerInterface $storeManager,
        NotificationConfig    $configProvider,
        ScopeConfigInterface  $scopeConfig
    ) {
        $this->accountService   = $accountService;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager     = $storeManager;
        $this->configProvider   = $configProvider;
        $this->scopeConfig      = $scopeConfig;
    }

    public function notifyAccount(AccountInterface $account, string $template, array $vars): bool
    {
        $template = $this->scopeConfig->getValue($template);

        if (!$template || $template == 'none') {
            return true;
        }

        $customer = $this->accountService->getCustomer($account);

        if (!$customer) {
            return false;
        }

        $store = $this->storeManager->getStore($customer->getStoreId());

        try {
            /** @var Transport $transport */
            $transport = $this->getTransportBuilder($template, $store, $vars)
                ->addTo($account->getEmail(), $account->getName())
                ->getTransport();
            $transport->sendMessage();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function notifyAdmin(AccountInterface $account, string $template, array $vars): bool
    {
        $template = $this->scopeConfig->getValue($template);
        if (!$template || $template == 'none') {
            return true;
        }

        $customer = $this->accountService->getCustomer($account);
        if ($customer) {
            $store = $this->storeManager->getStore($customer->getStoreId());
        } else {
            $store = $this->storeManager->getDefaultStoreView();
        }

        try {
            $transport = $this->getTransportBuilder($template, $store, $vars)
                ->addTo($this->configProvider->getSenderEmail((int)$store->getId()))
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function getTransportBuilder(string $template, StoreInterface $store, array $vars, string $area = null): TransportBuilder
    {
        if (!$area) {
            $area = Area::AREA_FRONTEND;
        }

        return $this->transportBuilder
            ->setTemplateIdentifier($template)
            ->setTemplateOptions([
                'area'  => $area,
                'store' => $store->getId(),
            ])
            ->setFrom([
                'email' => $this->configProvider->getSenderEmail((int)$store->getId()),
                'name'  => $this->configProvider->getSenderName((int)$store->getId()),
            ])
            ->setTemplateVars($vars);
    }
}
