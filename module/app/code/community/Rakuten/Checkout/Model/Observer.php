<?php
/**
 * Copyright (c) 2012, Rakuten Deutschland GmbH. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Rakuten Deutschland GmbH nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL RAKUTEN DEUTSCHLAND GMBH BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
/**
 * Rakuten module observer
 */
class Rakuten_Checkout_Model_Observer
{
    /**
     * Disable order cancel, invoice and reorder actions for orders placed via Rakuten Checkout
     * Observes "sales_order_load_after" event
     *
     * @param  Varien_Event_Observer $observer
     * @return void
     */
    public function disableOrderActions($observer)
    {
        try {
            /** @var $order Mage_Sales_Model_Order */
			$order = $observer->getOrder();
            if ($order->getRakutenOrder()) {
                /**
                 * Full list of Order Action flags:
                 * - ACTION_FLAG_CANCEL
                 * - ACTION_FLAG_HOLD
                 * - ACTION_FLAG_UNHOLD
                 * - ACTION_FLAG_EDIT
                 * - ACTION_FLAG_CREDITMEMO
                 * - ACTION_FLAG_INVOICE
                 * - ACTION_FLAG_REORDER
                 * - ACTION_FLAG_SHIP
                 * - ACTION_FLAG_COMMENT
                 */
                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL,   false);
                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_EDIT,     false);
                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CREDITMEMO, false);
                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE,  false);
                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_REORDER,  false);
                //$order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_SHIP,     false);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Sends create order shipment request to Rakuten Checkout
     * Observes "sales_order_shipment_save_before" event
     *
     * @param  Varien_Event_Observer $observer
     * @return void
     */
    public function sendShipment($observer)
    {
        try {
            /** @var $shipment Mage_Sales_Model_Order_Shipment */
			$shipment = $observer->getShipment();
            if ($shipment->getOrder()->getRakutenOrder()) {
                /** @var $rockout Rakuten_Checkout_Model_Checkout */
                $rockout = Mage::getModel('rakuten/checkout');
                // Send shipment to Rakuten
                $rockout->sendShipment($shipment);
            }
        } catch (Exception $e) {
            Mage::throwException(
                Mage::helper('rakuten')->__('Cannot send shipment to Rakuten Checkout.') .'<br />'
                . Mage::helper('rakuten')->__('Error #%s: %s', $e->getCode(), $e->getMessage())
            );
        }
    }
}
