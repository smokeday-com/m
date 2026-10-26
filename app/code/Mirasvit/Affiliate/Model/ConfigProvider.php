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

namespace Mirasvit\Affiliate\Model;

use Exception;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Model\Template\FilterProvider as CmsFilterProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Module\Manager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Ui\Config\Form\WithdrawPayment\BackendModel;
use Mirasvit\Core\Service\SerializeService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigProvider
{
    public const MEDIA_FOLDER          = 'affiliate';
    public const ASSIGN_PRIORITY_FIRST = 'first';
    public const ASSIGN_PRIORITY_LAST  = 'last';
    public const FLOAT_EPSILON         = 0.000001;

    public const AUTOWITHDRAW_SCHEDULE_MONTH = 'schedule_month';
    public const AUTOWITHDRAW_SCHEDULE_WEEK  = 'schedule_week';

    public const AUTOWITHDRAW_ROUND_NONE = 'round_none';
    public const AUTOWITHDRAW_ROUND_10   = 'round_10';
    public const AUTOWITHDRAW_ROUND_100  = 'round_100';

    public const ACCOUNT_FIELDS_PATH = 'affiliate/account/account_fields';
    public const ACCOUNT_FIELDS      = 'account_fields';

    public const FIELD_KEY          = 'key';
    public const FIELD_LABEL        = 'label';
    public const FIELD_TYPE         = 'type';
    public const FIELD_IS_ACTIVE    = 'is_active';
    public const FIELD_IS_REQUIRED  = 'is_required';
    public const FIELD_PLACEHOLDER  = 'placeholder';
    public const FIELD_OPTIONS      = 'options';
    public const FIELD_OPTION_KEY   = 'option_key';
    public const FIELD_OPTION_LABEL = 'option_label';

    private $moduleManager;

    private $scopeConfig;

    private $filesystem;

    private $driverFile;

    private $storeManager;

    private $cmsFilterProvider;

    private $getBlockByIdentifier;

    private $configWriter;

    public function __construct(
        Manager                       $moduleManager,
        GetBlockByIdentifierInterface $getBlockByIdentifier,
        DriverFile                    $driverFile,
        Filesystem                    $filesystem,
        ScopeConfigInterface          $scopeConfig,
        StoreManagerInterface         $storeManager,
        CmsFilterProvider             $cmsFilterProvider,
        WriterInterface               $configWriter
    ) {
        $this->moduleManager        = $moduleManager;
        $this->driverFile           = $driverFile;
        $this->filesystem           = $filesystem;
        $this->getBlockByIdentifier = $getBlockByIdentifier;
        $this->scopeConfig          = $scopeConfig;
        $this->storeManager         = $storeManager;
        $this->cmsFilterProvider    = $cmsFilterProvider;
        $this->configWriter         = $configWriter;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'affiliate/general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isShowReferredCustomers(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'affiliate/general/show_referred_customers',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBaseMediaPath(): string
    {
        $path = $this->filesystem
                ->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath() . self::MEDIA_FOLDER;

        if (!$this->driverFile->isExists($path) || $this->driverFile->isDirectory($path)) {
            $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $writer->create(self::MEDIA_FOLDER);
        }

        return $path;
    }

    public function getMediaUrl(string $image = null): ?string
    {
        if (!$image) {
            return null;
        }

        $url = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . self::MEDIA_FOLDER;

        $url .= '/' . $image;

        return $url;
    }

    public function getMediaPath(string $file): string
    {
        return $this->getBaseMediaPath() . '/' . $file;
    }

    public function getAgreementText(): string
    {
        $block = $this->getBlock('affiliate-agreement');
        $text  = $block ? $block->getContent() : '';

        return (string)$this->cmsFilterProvider->getBlockFilter()
            ->setStoreId($this->storeManager->getStore()->getId())
            ->filter($text);
    }

    public function getWelcomeText(): string
    {
        $block = $this->getBlock('affiliate-welcome');
        $text  = $block ? $block->getContent() : '';

        return (string)$this->cmsFilterProvider->getBlockFilter()
            ->setStoreId($this->storeManager->getStore()->getId())
            ->filter($text);
    }

    public function getDashboardText(): string
    {
        $block = $this->getBlock('affiliate-dashboard');
        $text  = $block ? $block->getContent() : '';

        return (string)$this->cmsFilterProvider->getBlockFilter()
            ->setStoreId($this->storeManager->getStore()->getId())
            ->filter($text);
    }

    public function isAutoSignUpEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('affiliate/account/auto_sign_up');
    }

    public function isApprovalRequired(): bool
    {
        if ($this->isAutoSignUpEnabled()) {
            return false;
        }

        return (bool)$this->scopeConfig->getValue('affiliate/account/approval_required');
    }

    public function getReferralParam(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/account/referral_param');
    }

    public function isShowLinkInAccount(): bool
    {
        return (bool)$this->scopeConfig->getValue('affiliate/account/is_show_link');
    }

    public function isShowZeroEvents(): bool
    {
        return (bool)$this->scopeConfig->getValue('affiliate/account/is_show_zero_events');
    }

    public function getBestsellersLastDays(): int
    {
        return (int)$this->scopeConfig->getValue('affiliate/promotion_materials/widget/bestsellers_last_days');
    }

    public function getCommissionAddOrderStatus(): array
    {
        return array_unique([
            $this->scopeConfig->getValue('affiliate/commission/add_order_status'),
            'complete',
        ]);
    }

    public function getCommissionSubtractOrderStatus(): array
    {
        return explode(',', (string)$this->scopeConfig->getValue('affiliate/commission/subtract_order_status'));
    }

    public function getCommissionSubtractOnCreditmemo(): bool
    {
        return (bool)$this->scopeConfig->getValue('affiliate/commission/subtract_on_creditmemo');
    }

    public function shouldIncludeTax(): bool
    {
        return (bool)$this->scopeConfig->getValue('affiliate/commission/include_tax');
    }

    public function getWithdrawMinAmount(): float
    {
        return (float)$this->scopeConfig->getValue('affiliate/withdraw/min_amount');
    }

    public function getWithdrawPaymentMethodsCustom(bool $activeOnly = true): array
    {
        $value = $this->scopeConfig->getValue('affiliate/withdraw/payment_methods_custom');
        $rows  = is_array($value) ? $value : SerializeService::decode($value);
        if (!$rows) {
            return [];
        }

        $rows = ObjectManager::getInstance()
            ->get('\Mirasvit\Affiliate\Service\Config\PaymentMethodFieldService')
            ->prepareFieldRows($rows);

        if ($activeOnly) {
            $rows = array_filter($rows, function ($row) {
                return (bool)($row[BackendModel::IS_ACTIVE] ?? false);
            });
        }

        return $rows;
    }

    public function getWithdrawPaymentMethodsMap(bool $activeOnly = true): array
    {
        $map = [];
        foreach ($this->getWithdrawPaymentMethodsCustom($activeOnly) as $row) {
            $code  = $row[BackendModel::CODE] ?? '';
            $label = $row[BackendModel::LABEL] ?? '';
            if ($code && $label) {
                $map[$code] = __($label);
            }
        }

        return $map;
    }

    public function getCommissionHoldingPeriod(): int
    {
        return (int)$this->scopeConfig->getValue('affiliate/commission/holding_period') * 24 * 60 * 60;
    }

    public function getCookieLifetimeDays(): int
    {
        return (int)$this->scopeConfig->getValue('affiliate/commission/cookie_lifetime');
    }

    public function getCookieLifetimeSeconds(): int
    {
        return $this->getCookieLifetimeDays() * 24 * 60 * 60;
    }

    public function getCommissionsLimit(): int
    {
        return (int)$this->scopeConfig->getValue('affiliate/commission/commission_limit');
    }

    public function getCommissionAssignPriority(): string
    {
        if ($this->scopeConfig->getValue('affiliate/commission/assign_priority') == self::ASSIGN_PRIORITY_LAST) {
            return self::ASSIGN_PRIORITY_LAST;
        }

        return self::ASSIGN_PRIORITY_FIRST;
    }

    public function isCouponEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('affiliate/coupon/enabled');
    }

    public function getCouponPattern(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/coupon/pattern');
    }

    public function isModuleEnabled(string $moduleName): bool
    {
        return $this->moduleManager->isEnabled($moduleName);
    }

    public function isFirstDayMonday(): bool
    {
        return false;
    }

    public function getFirstDayNum(): int
    {
        return (int)$this->scopeConfig->getValue('general/locale/firstday');
    }

    public function isAutoWithdrawEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('affiliate/withdraw/autowithdraw_enabled');
    }

    public function getAutoWithdrawSchedule(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/withdraw/autowithdraw_schedule');
    }

    public function getAutoWithdrawRound(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/withdraw/autowithdraw_round');
    }

    public function getTemplateIdCustomerAccountNew(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/notification/customer/account_new_template');
    }

    public function getTemplateIdCustomerAccountApproved(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/notification/customer/account_approved_template');
    }

    public function getTemplateIdCustomerTransactionNew(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/notification/customer/transaction_new_template');
    }

    public function getTemplateIdCustomerUpdateBalance(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/notification/customer/update_balance_template');
    }

    public function getTemplateIdAdminAccountNew(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/notification/admin/account_new_template');
    }

    public function getTemplateIdAdminWithdrawNew(): string
    {
        return (string)$this->scopeConfig->getValue('affiliate/notification/admin/withdraw_new_template');
    }

    private function getBlock(string $blockIdentifier): ?BlockInterface
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        try {
            $block = $this->getBlockByIdentifier->execute($blockIdentifier, $storeId);
        } catch (Exception $e) {
            $block = null;
        }

        return $block;
    }

    public function getAccountCustomFields(bool $activeOnly = false): array
    {
        $value = $this->scopeConfig->getValue(self::ACCOUNT_FIELDS_PATH);
        $rows  = is_array($value) ? $value : SerializeService::decode($value);
        if (!$rows) {
            return [];
        }

        if ($activeOnly) {
            $rows = array_filter($rows, function ($row) {
                return (bool)($row[self::FIELD_IS_ACTIVE] ?? false);
            });
        }

        return $rows;
    }

    public function setAccountCustomFields(string $accountFields): void
    {
        $this->configWriter->save(self::ACCOUNT_FIELDS_PATH, $accountFields);
    }
}
