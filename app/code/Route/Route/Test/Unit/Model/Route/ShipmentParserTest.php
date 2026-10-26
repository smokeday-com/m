<?php
/**
 * Created by PhpStorm.
 * User: eduan
 * Date: 08/03/20
 * Time: 00:53
 */

namespace Route\Route\Test\Unit\Model\Route;

use Magento\Sales\Model\Order\Shipment;
use PHPUnit\Framework\TestCase;
use Route\Route\Model\Route\ShipmentParser;
use Route\Route\Service\Model\V1ShipmentsBody;

class ShipmentParserTest extends TestCase
{
    /** @var Shipment\Track\ $track*/
    private $track;

    /** @var Shipment $shipment*/
    private $shipment;

    /** @var ShipmentParser $shipmentParser */
    private $shipmentParser;

    private function createOrderMock()
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->expects($this->any())->method('getIncrementId')->willReturn('100000007');
        return $order;
    }

    private function getItems()
    {
        $item = $this->createMock(\Magento\Sales\Api\Data\ShipmentItemInterface::class);
        $item->expects($this->any())->method('getProductId')->willReturn('10002');
        $item->expects($this->any())->method('getQty')->willReturn(1);
        return [$item];
    }

    private function getMultipleItems()
    {
        $item = $this->createMock(\Magento\Sales\Api\Data\ShipmentItemInterface::class);
        $item->expects($this->any())->method('getProductId')->willReturn('10003');
        $item->expects($this->any())->method('getQty')->willReturn(2);
        return array_merge($this->getItems(), [$item]);
    }

    private function createSimpleShipmentMock()
    {
        $shipment = $this->createMock(\Magento\Sales\Model\Order\Shipment::class);

        $shipment->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->createOrderMock());

        $shipment->expects($this->any())
            ->method('getAllItems')
            ->willReturn($this->getItems());

        return $shipment;
    }

    private function createMultipleShipmentMock()
    {
        $shipment = $this->createMock(\Magento\Sales\Model\Order\Shipment::class);

        $shipment->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->createOrderMock());

        $shipment->expects($this->any())
            ->method('getAllItems')
            ->willReturn($this->getMultipleItems());

        return $shipment;
    }

    public function setUp(): void
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->shipmentParser = $objectManager->getObject('\Route\Route\Model\Route\ShipmentParser');

        $this->track = $this->createMock(\Magento\Sales\Model\Order\Shipment\Track::class);

        $this->track->expects($this->any())
            ->method('getNumber')
            ->willReturn('TRACK_TEST_1234');
    }

    public function testSimpleShipment()
    {
        $expected = new V1ShipmentsBody([
            'source_order_id' => '100000007',
            'tracking_number' => 'TRACK_TEST_1234',
            'courier_id' => 'usps',
            'source_product_ids' => ["10002"],
            'line_items' => [

            ]
        ]);

        $shipment = $this->createSimpleShipmentMock();

        $this->track->expects($this->any())
            ->method('getCarrierCode')
            ->willReturn('usps');
        $this->track->expects($this->any())
            ->method('getShipment')
            ->willReturn($shipment);

//        $this->shipmentParser->setShipment($shipment);

        $this->assertEquals($expected, $this->shipmentParser->getRouteObject($this->track));
    }

    public function testMultipleItemsShipment()
    {
        $expected = new V1ShipmentsBody([
            'source_order_id' => '100000007',
            'tracking_number' => 'TRACK_TEST_1234',
            'courier_id' => 'usps',
            'source_product_ids' => ["10002", "10003", "10003"],
            'line_items'=>[

            ]
        ]);

        $shipment = $this->createMultipleShipmentMock();

        $this->track->expects($this->any())
            ->method('getCarrierCode')
            ->willReturn('usps');
        $this->track->expects($this->any())
            ->method('getShipment')
            ->willReturn($shipment);

        $this->assertEquals($expected, $this->shipmentParser->getRouteObject($this->track));
    }

    public function testCustomCarrierShipment()
    {
        $expected = new V1ShipmentsBody([
            'source_order_id' => '100000007',
            'tracking_number' => 'TRACK_TEST_1234',
            'courier_id' => 'Title',
            'source_product_ids' => ["10002"],
            'line_items' => []
        ]);
        $shipment = $this->createSimpleShipmentMock();

        $this->track->expects($this->any())
            ->method('getCarrierCode')
            ->willReturn('custom');

        $this->track->expects($this->any())
            ->method('getTitle')
            ->willReturn('Title');

        $this->track->expects($this->any())
            ->method('getShipment')
            ->willReturn($shipment);

        $this->assertEquals($expected, $this->shipmentParser->getRouteObject($this->track));
    }

    public function testInvalidProductId()
    {
        $expected = new V1ShipmentsBody([
            'source_order_id' => '100000007',
            'tracking_number' => 'TRACK_TEST_1234',
            'courier_id' => 'Title',
            'source_product_ids' => ["10002"],
            'line_items' => [
            ]
        ]);

        $shipment = $this->createMock(\Magento\Sales\Model\Order\Shipment::class);

        $shipment->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->createOrderMock());

        $item = $this->createMock(\Magento\Sales\Api\Data\ShipmentItemInterface::class);
        $item->expects($this->any())->method('getProductId')->willReturn('10002');
        $item->expects($this->any())->method('getQty')->willReturn(1);

        $item2 = $this->createMock(\Magento\Sales\Api\Data\ShipmentItemInterface::class);
        $item2->expects($this->any())->method('getProductId')->willReturn(false);
        $item2->expects($this->any())->method('getQty')->willReturn(1);

        $shipment->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$item, $item2]);

        $this->track->expects($this->any())
            ->method('getCarrierCode')
            ->willReturn('custom');

        $this->track->expects($this->any())
            ->method('getTitle')
            ->willReturn('Title');

        $this->track->expects($this->any())
            ->method('getShipment')
            ->willReturn($shipment);

        $this->assertEquals($expected, $this->shipmentParser->getRouteObject($this->track));
    }

    public function testInvalidProductIds()
    {
        $expected = new V1ShipmentsBody([
            'source_order_id' => '100000007',
            'tracking_number' => 'TRACK_TEST_1234',
            'courier_id' => 'Title',
            'source_product_ids' => [],
            'line_items' => [
            ]
        ]);

        $shipment = $this->createMock(\Magento\Sales\Model\Order\Shipment::class);

        $shipment->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->createOrderMock());

        $item = $this->createMock(\Magento\Sales\Api\Data\ShipmentItemInterface::class);
        $item->expects($this->any())->method('getProductId')->willReturn(false);
        $item->expects($this->any())->method('getQty')->willReturn(1);

        $item2 = $this->createMock(\Magento\Sales\Api\Data\ShipmentItemInterface::class);
        $item2->expects($this->any())->method('getProductId')->willReturn(false);
        $item2->expects($this->any())->method('getQty')->willReturn(1);

        $shipment->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$item, $item2]);

        $this->track->expects($this->any())
            ->method('getCarrierCode')
            ->willReturn('custom');

        $this->track->expects($this->any())
            ->method('getTitle')
            ->willReturn('Title');

        $this->track->expects($this->any())
            ->method('getShipment')
            ->willReturn($shipment);

        $this->assertEquals($expected, $this->shipmentParser->getRouteObject($this->track));
    }
}
