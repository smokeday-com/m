<?php

namespace Mage4\AutoInvoice\Model;

use Goeasyship\Shipping\Model\ShipOrder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;


class ExtendShipOrder extends ShipOrder
{
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;

    public function __construct(
        ResourceConnection         $resourceConnection,
        OrderConfig                $config,
        Order                      $convertOrder,
        OrderRepository            $orderRepository,
        ShipmentRepository         $shipmentRepository,
        ShipmentNotifier           $shipmentNotifier,
        \Magento\Sales\Model\Order $order,
        ShipmentFactory            $shipmentFactory,
        Track                      $track,
        InvoiceService             $invoiceService,
        InvoiceSender              $invoiceSender,
        Transaction                $transaction
    )
    {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        parent::__construct(
            $resourceConnection,
            $config,
            $convertOrder,
            $orderRepository,
            $shipmentRepository,
            $shipmentNotifier,
            $order,
            $shipmentFactory,
            $track
        );
    }

    /**
     * Logic of working with shopping
     *
     * @param int|string $orderId
     * @param array $items
     * @param array $trackData
     * @param string $comment
     * @return bool|string
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(
        $orderId,
        $items = [],
        $trackData = [],
        $comment = ''
    )
    {
        $order = $this->_orderRepository->get($orderId);

        if (!$order->canShip()) {
            return false;
        }
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->addComment('Amount Captured Online: Automatic invoice by EasyShip');
            $invoice->register();
            $invoice->save();

            $transactionSave =
                $this->transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transactionSave->save();
            $this->invoiceSender->send($invoice);

            $order->addCommentToStatusHistory(
                __('Notified customer about invoice creation #%1.', $invoice->getId())
            )->setIsCustomerNotified(true)->save();
        }

        $shipment = $this->_convertOrder->toShipment($order);
        $countItems = count($items);
        foreach ($order->getAllItems() as $orderItem) {
            $this->_addToShip($shipment, $orderItem, $items, $countItems);
        }

        $shipment->getOrder()->setIsInProcess(true);
        $trackData = $this->validateTrackData($trackData);

        if (!empty($trackData)) {
            $this->_track->addData($trackData);
            $shipment->addTrack($this->_track);
        }

        if (!empty($comment)) {
            $shipment->addComment($comment);
        }

        $shipment->setShipmentStatus(Shipment::STATUS_NEW);

        $shipment->register();

        $connection = $this->_resourceConnection->getConnection('sales');
        $connection->beginTransaction();
        try {
            $order->setState(self::ORDER_IN_PROGRESS);
            $order->setStatus($this->_config->getStateDefaultStatus($order->getState()));
            $this->_shipmentRepository->save($shipment);
            $this->_orderRepository->save($order);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new CouldNotSaveException(
                __('Could not save a shipment, see error log for details' . $e->getMessage())
            );
        }

        return $shipment->toJson();
    }

}
