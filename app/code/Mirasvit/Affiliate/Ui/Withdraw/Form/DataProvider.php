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

namespace Mirasvit\Affiliate\Ui\Withdraw\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Repository\WithdrawRepository;

class DataProvider extends AbstractDataProvider
{
    private $withdrawRepository;

    /** @var WithdrawInterface[]|\Mirasvit\Affiliate\Model\ResourceModel\Withdraw\Collection */
    protected $collection;

    private $urlBuilder;

    private $request;

    /**
     * @param WithdrawRepository $transactionRepository
     * @param UrlInterface       $urlBuilder
     * @param RequestInterface   $request
     * @param string             $name
     * @param string             $primaryFieldName
     * @param string             $requestFieldName
     * @param array              $meta
     * @param array              $data
     */
    public function __construct(
        WithdrawRepository $transactionRepository,
        UrlInterface $urlBuilder,
        RequestInterface $request,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->withdrawRepository = $transactionRepository;
        $this->collection         = $this->withdrawRepository->getCollection();
        $this->urlBuilder         = $urlBuilder;
        $this->request            = $request;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        $result = [];
        foreach ($this->collection->joinAccount() as $item) {
            $result[$item->getId()] = [
                WithdrawInterface::ID         => $item->getId(),
                WithdrawInterface::ACCOUNT_ID => $item->getAccountId(),
                WithdrawInterface::STATUS     => $item->getStatus(),
                WithdrawInterface::AMOUNT     => $item->getAmount(),
                WithdrawInterface::FEE        => $item->getFee(),
                'affiliate'                   => [
                    'name'  => $item->getData('name'),
                    'email' => $item->getData('email'),
                    'url'   => $this->urlBuilder->getUrl('affiliate/account/edit', [AccountInterface::ID => $item->getAccountId()]),
                ],
            ];
        }

        return $result;
    }

    public function getMeta(): array
    {
        $meta  = parent::getMeta();
        $isNew = empty($this->request->getParam($this->primaryFieldName));
        if ($isNew) {
            $meta['general']['children']['affiliate']['arguments']['data']['config']['visible'] = false;
        } else {
            $meta['general']['children']['account_id']['arguments']['data']['config']['visible'] = false;
        }

        return $meta;
    }
}
