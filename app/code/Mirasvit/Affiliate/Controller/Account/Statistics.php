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

namespace Mirasvit\Affiliate\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Locale\ResolverInterface;
use Mirasvit\Affiliate\Controller\AbstractAccount;

class Statistics extends AbstractAccount
{
    private $localeResolver;

    protected $messageManager;

    public function __construct(
        Context           $context,
        ResolverInterface $localeResolver
    ) {
        $this->localeResolver = $localeResolver;
        $this->messageManager = $context->getMessageManager();

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $request = $this->getRequest();
        $from    = $request->getParam('from');
        $to      = $request->getParam('to');

        if (($from && !$this->isDateValid($from)) || ($to && !$this->isDateValid($to))) {
            $this->messageManager->addErrorMessage("Invalid date supplied to one of the date fields.");

            return $this->resultRedirectFactory->create()->setPath('*/*/*');
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->getConfig()->getTitle()->set((string)__('Statistics'));

        return $resultPage;
    }

    private function isDateValid(string $date): bool
    {
        $formatter = new \IntlDateFormatter(
            $this->localeResolver->getLocale(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE
        );

        $dateFormat     = $formatter->getPattern();
        $longYearFormat = preg_replace('/y+/', 'y', $dateFormat);
        $formatter->setPattern($longYearFormat);
        try {
            return (bool)$formatter->parse($date);
        } catch (\Exception $e) {
            return false;
        }
    }
}
