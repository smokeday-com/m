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

namespace Mirasvit\Affiliate\Block\Account;

use DateTime;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Theme\Block\Html\Pager;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Model\ResourceModel\Event\Collection;
use Mirasvit\Affiliate\Repository\EventRepository;
use Mirasvit\Affiliate\Service\ChartService;
use Mirasvit\Affiliate\Service\SessionService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Statistics extends Template
{
    private $configProvider;

    private $eventRepository;

    private $chartService;

    private $sessionService;

    private $pager;

    private $pricingHelper;

    private $localeResolver;

    public function __construct(
        ConfigProvider    $configProvider,
        EventRepository   $eventRepository,
        ChartService      $chartService,
        SessionService    $sessionService,
        PricingHelper     $pricingHelper,
        ResolverInterface $localeResolver,
        Template\Context  $context
    ) {
        $this->configProvider  = $configProvider;
        $this->eventRepository = $eventRepository;
        $this->chartService    = $chartService;
        $this->sessionService  = $sessionService;
        $this->pricingHelper   = $pricingHelper;
        $this->localeResolver  = $localeResolver;

        parent::__construct($context);
    }

    public function getNumberByType(string $type, bool $unique = false): int
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return 0;
        }
        $collection->addFieldToFilter(EventInterface::TYPE, $type);

        if ($type === ProgramInterface::TYPE_PAY_PER_SALE) {
            $collection->addFieldToFilter(EventInterface::COMMISSION, ['gt' => 0]);
        }

        if ($unique) {
            $collection->getSelect()->group('visitor_identifier');
        }

        return $collection->getSize();
    }

    public function getCommissionAmount(): float
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return 0.0;
        }
        $collection->addExpressionFieldToSelect('commissions', (string)(new \Zend_Db_Expr('SUM(commission)')), []);

        return (float)$collection->getFirstItem()->getData('commissions');
    }

    public function getCalendarDateHtml(string $range): string
    {
        $date = ($range == 'from') ? $this->getFilterFrom() : $this->getFilterTo()->modify('-1 day');

        // Convert to long year format because Calendar use long year format on frontend.
        // see lib/web/mage/calendar.js
        $value = $this->getLongYearFormatValue($date);

        $dateFormat        = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $escapedDateFormat = preg_replace('/[^MmDdYy\/\.\-]/', '', $dateFormat);

        $calendar = $this->getLayout()
            ->createBlock(\Magento\Framework\View\Element\Html\Date::class)
            ->setId($range)
            ->setName($range)
            ->setClass('datetime-picker input-text')
            ->setDateFormat($escapedDateFormat)
            ->setValue($value)
            ->setYearsRange(2016 . ':' . date('Y'));

        return (string)$calendar->getHtml();
    }

    public function getFilterFrom(): DateTime
    {
        $from = $this->getRequest()->getParam('from');

        return $from
            ? $this->_localeDate->date($from, null, true, false)
            : $this->_localeDate->date()->modify('-1 month');
    }

    public function getFilterTo(): DateTime
    {
        $to = $this->getRequest()->getParam('to');

        return $to
            ? $this->_localeDate->date($to, null, true, false)->modify('+1 day')
            : $this->_localeDate->date()->modify('+1 day');
    }

    public function getAccount(): ?AccountInterface
    {
        return $this->sessionService->getAccount();
    }

    public function getEventCollection(): ?Collection
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return null;
        }

        $pager = $this->getPagerBlock();

        return $collection
            ->setPageSize((int)$pager->getLimit())
            ->setCurPage((int)$pager->getCurrentPage())
            ->setOrder('created_at', 'desc');
    }

    public function getPagerHtml(): string
    {
        return $this->getChildHtml('pager');
    }

    public function getChartConfig()
    {
        $chartConfig = $this->chartService->getChartConfig(
            $this->getFilterFrom(),
            $this->getFilterTo(),
            $this->getCurrentCurrencyCode(),
            $this->isCurrentSite()
        );

        return [
            'ChartJsConfig' => $this->chartLabelFormatting($chartConfig),
            'currencyCode'  => $this->getCurrentCurrencyCode(),
        ];
    }

    public function isCurrentSite(): bool
    {
        $isCurrentSite = $this->getRequest()->getParam('current-site');
        if (!$isCurrentSite) {
            return true;
        }

        return $this->getRequest()->getParam('current-site') == 'on';
    }

    public function getCurrentCurrencyCode(): string
    {
        return (string)$this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getCurrencyFormatted(float $amount): string
    {
        return (string)$this->pricingHelper->currency($amount, true, false);
    }

    protected function _prepareLayout(): Statistics
    {
        if ($this->getEventCollection()) {
            $pager = $this->getPagerBlock()
                ->setShowPerPage(true)
                ->setCollection($this->getEventCollection());
            $this->setChild('pager', $pager);
            $this->getEventCollection()->load();
        }

        return parent::_prepareLayout();
    }

    /**
     * @return Pager
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getPagerBlock(): BlockInterface
    {
        if (!$this->pager) {
            $this->pager = $this->getLayout()->createBlock(Pager::class, 'affiliate.transaction.pager');
        }

        return $this->pager;
    }

    private function chartLabelFormatting(array $chartConfig): array
    {
        $labels = array_map(function ($label) {
            return $this->_localeDate->formatDate(
                date_create_from_format('Y-m-d', $label),
                \IntlDateFormatter::MEDIUM
            );
        }, $chartConfig['data']['labels']);

        $chartConfig['data']['labels'] = $labels;

        return $chartConfig;
    }

    private function getCollection(): ?Collection
    {
        $account = $this->getAccount();
        if (!$account) {
            return null;
        }

        $collection = $this->eventRepository->getCollection()
            ->addFieldToFilter('account_id', $account->getId())
            ->addFieldToFilter(EventInterface::CREATED_AT, ['gteq' => $this->getFilterFrom()])
            ->addFieldToFilter(EventInterface::CREATED_AT, ['lteq' => $this->getFilterTo()]);

        if ($this->isCurrentSite()) {
            $collection->addStoreFilter();
        }

        if (!$this->configProvider->isShowZeroEvents()) {
            $collection->greaterZeroFilter();
        }

        return $collection;
    }

    private function getLongYearFormatValue(DateTime $date): string
    {
        $formatter = new \IntlDateFormatter(
            $this->localeResolver->getLocale(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE
        );

        $dateFormat     = $formatter->getPattern();
        $longYearFormat = preg_replace('/y+/', 'y', $dateFormat);
        $formatter->setPattern($longYearFormat);

        return $formatter->format($date);
    }
}
