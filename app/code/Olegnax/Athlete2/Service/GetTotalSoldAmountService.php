<?php

namespace Olegnax\Athlete2\Service;

use Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory as SoldCollectionFactory;
use Psr\Log\LoggerInterface;

class GetTotalSoldAmountService
{
    protected $soldCollectionFactory;
    protected $logger;

    public function __construct(
        SoldCollectionFactory $soldCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->soldCollectionFactory = $soldCollectionFactory;
        $this->logger = $logger;
    }

    public function getTotalSoldAmount($productId)
    {
        try {
            $collection = $this->soldCollectionFactory->create()
            ->addOrderedQty()
            ->addAttributeToFilter('product_id', $productId)
            ->addAttributeToFilter('state', 'complete');

            $totalSold = 0;
            if($collection->count()){
                foreach ($collection as $item) {
                    $totalSold += $item->getOrderedQty();
                }
            }

            return (int)$totalSold;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return 0; // Handle exception gracefully
        }
    }

}
