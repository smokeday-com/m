<?php

namespace Route\Route\Controller\Adminhtml\Orders;

use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Route\Route\Logger\Sentry;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Order as OrderClient;

class Sync extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Json
     */
    protected $jsonHelper;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var OrderClient
     */
    protected $orderClient;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Json $jsonHelper
     * @param Sentry $logger
     * @param CollectionFactory $orderCollectionFactory
     * @param Data $helper
     * @param OrderClient $orderClient Order api client
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Json $jsonHelper,
        Sentry $logger,
        CollectionFactory $orderCollectionFactory,
        Data $helper,
        OrderClient $orderClient
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->orderClient = $orderClient;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $post = $this->getRequest()->getPostValue();
            if (!isset($post['from']) || !isset($post['to']) || !isset($post['maxLimit'])) {
                return $this->jsonResponse('Error: we are missing the period to evaluate');
            }
            //do the reconcile
            $ordersCollection = $this->orderCollectionFactory->create();
            $ordersCollection->getSelect()->limit(intval($post['maxLimit']));
            $dateFrom = \DateTime::createFromFormat('Y-m-d', $post['from']);
            $dateTo = \DateTime::createFromFormat('Y-m-d', $post['to']);
            $ordersCollection->addFieldToFilter('created_at', [
                'gt' => $dateFrom->format('YmdHis')
            ])->addFieldToFilter('created_at', [
                'lt' => $dateTo->format('YmdHis')
            ]);


            $orderUpsertStatuses = $this->helper->getOrderStatus();
            $orderCancelStatuses = $this->helper->getOrderStatusCanceled();

            $upsertCounter = 0;
            $cancelCounter = 0;
            foreach ($ordersCollection as $order) {
                $orderStatus = $order->getStatus();
                if (in_array($orderStatus, $orderCancelStatuses)) {
                    //cancel order
                    $this->orderClient->postOrderCancel($order);
                    $cancelCounter++;
                } else {
                    if (empty($orderUpsertStatuses) ||
                        in_array($orderStatus, $orderUpsertStatuses) ||
                        is_null($this->helper->getConfigValue(Data::ORDER_STATUS))) {
                        //upsert order
                        $this->orderClient->postOrder(
                            $order,
                            $order->getRouteIsInsured()
                        );
                        $upsertCounter++;
                    }
                }
            }

            $message = 'Process done: ' . $cancelCounter .' orders were canceled and '.$upsertCounter . ' were upsert';

            return $this->jsonResponse($message);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $exception) {
            $this->logger->debug('Exception while trying to reconcile orders: ' . $exception->getMessage(), $exception->getTrace());
            return $this->jsonResponse($exception->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->serialize($response)
        );
    }
}
