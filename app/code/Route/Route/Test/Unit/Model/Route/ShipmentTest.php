<?php
namespace Route\Route\Test\Unit\Model\Route;

use Magento\Sales\Model\Order\Shipment;
use PHPUnit\Framework\TestCase;
use Route\Route\Model\Route\Order;

class ShipmentTest extends TestCase
{
    /** @var Shipment\Track\ $track*/
    private $track;

    private function createSimpleShipmentMock($invalidOrder = true)
    {
        $shipment = $this->createMock(\Magento\Sales\Model\Order\Shipment::class);

        $shipment->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->createOrderMock($invalidOrder));

        return $shipment;
    }

    private function getMockedRouteShipmentClass()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        return $objectManager->getObject('\Route\Route\Model\Route\Shipment');
    }

    private function createOrderMock($invalidOrder = true)
    {
        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getRouteOrderId']
        );
        $order->expects($this->any())
            ->method('getRouteOrderId')
            ->willReturn($invalidOrder ? Order::DEFAULT_ROUTE_ORDER_ID : '100000001');
        return $order;
    }

    public function testShipmentExistsForExistingShipment()
    {
        $this->track = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment\Track::class,
            ['getRouteShipmentId']
        );
        $this->track->expects($this->any())
            ->method('getRouteShipmentId')
            ->willReturn(true);

        $shipmentClass = $this->getMockedRouteShipmentClass();

        $this->assertEquals(true, $shipmentClass->shipmentExists($this->track));
    }

    public function testShipmentExistsForNotValidOrder()
    {
        $this->track = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment\Track::class,
            ['getRouteShipmentId', 'getShipment', 'setRouteShipmentId', 'setAvoidResend', 'save']
        );
        $this->track->expects($this->any())
            ->method('getRouteShipmentId')
            ->willReturn(false);

        $this->track->expects($this->any())
            ->method('getShipment')
            ->willReturn($this->createSimpleShipmentMock());

        $this->track->expects($this->any())
            ->method('setRouteShipmentId')
            ->with(Order::DEFAULT_ROUTE_ORDER_ID)
            ->willReturn($this->track);

        $this->track->expects($this->any())
            ->method('setAvoidResend')
            ->with(true)
            ->willReturn($this->track);

        $this->track->expects($this->any())
            ->method('save')
            ->willReturn(true);

        $shipmentClass = $this->getMockedRouteShipmentClass();

        $this->assertEquals(true, $shipmentClass->shipmentExists($this->track));
    }

    public function testShipmentExistsHappyPath()
    {
        $this->track = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment\Track::class,
            [   'getRouteShipmentId',
                'getShipment',
                'getNumber',
                'setRouteShipmentId',
                'setAvoidResend',
                'save']
        );
        $this->track->expects($this->any())
            ->method('getRouteShipmentId')
            ->willReturn(false);
        $this->track->expects($this->any())
            ->method('getShipment')
            ->willReturn($this->createSimpleShipmentMock(false));
        $this->track->expects($this->any())
            ->method('getNumber')
            ->willReturn('fedex123');
        $this->track->expects($this->any())
            ->method('setRouteShipmentId')
            ->with('FAKE_ROUTE_ID')
            ->willReturn($this->track);
        $this->track->expects($this->any())
            ->method('setAvoidResend')
            ->with(true)
            ->willReturn($this->track);

        $this->track->expects($this->any())
            ->method('save')
            ->willReturn(true);

        $shipmentClass = $this->createPartialMock(
            \Route\Route\Model\Route\Shipment::class,
            ['getShipment']
        );
        $shipmentResponse = $this->createPartialMock(
            \Route\Route\Service\Model\InlineResponse2003::class,
            ['getId']
        );
        $shipmentResponse->expects($this->any())
            ->method('getId')
            ->willReturn('FAKE_ROUTE_ID');

        $shipmentClass->expects($this->any())
            ->method('getShipment')
            ->with('fedex123')
            ->willReturn($shipmentResponse);

        $this->assertEquals(true, $shipmentClass->shipmentExists($this->track));
    }

    public function testShipmentExistsAPIError()
    {
        $this->track = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment\Track::class,
            [   'getRouteShipmentId',
                'getShipment',
                'getNumber',
               ]
        );
        $this->track->expects($this->any())
            ->method('getRouteShipmentId')
            ->willReturn(false);
        $this->track->expects($this->any())
            ->method('getShipment')
            ->willReturn($this->createSimpleShipmentMock(false));
        $this->track->expects($this->any())
            ->method('getNumber')
            ->willReturn('fedex123');

        $shipmentClass = $this->createPartialMock(
            \Route\Route\Model\Route\Shipment::class,
            ['getShipment']
        );

        $shipmentClass->expects($this->any())
            ->method('getShipment')
            ->with('fedex123')
            ->willReturn(false);

        $this->assertEquals(false, $shipmentClass->shipmentExists($this->track));
    }
}
