<?php
/**
 * Copyright (c) 2012, Rakuten Deutschland GmbH. All rights reserved.
 *
 *	Redistribution and use in source and binary forms, with or without
 *	modification, are permitted provided that the following conditions are met:
 *
 * 	 * Redistributions of source code must retain the above copyright
 *  	   notice, this list of conditions and the following disclaimer.
 * 	 * Redistributions in binary form must reproduce the above copyright
 *   	   notice, this list of conditions and the following disclaimer in the
 *   	   documentation and/or other materials provided with the distribution.
 * 	 * Neither the name of the Rakuten Deutschland GmbH nor the
 *   	   names of its contributors may be used to endorse or promote products
 *   	   derived from this software without specific prior written permission.
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
 * R[akuten] O[rder] P[roc]E[ssing] model
 */
class Rakuten_Checkout_Model_Rope
{
    /**
     * Default log filename
     *
     * @var string
     */
    const DEFAULT_LOG_FILE = 'payment_rakuten_rope.log';

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    /**
     * @var Rakuten_Checkout_Model_Config
     */
    protected $_config = null;

    /**
     * ROPE request data
     *
     * @var SimpleXMLElement|string
     */
    protected $_request = null;

    /**
     * XML node to access ordered items
     *
     * @var string
     */
    protected $_orderNode = '';

    /**
     * Collected debug information
     *
     * @var array
     */
    protected $_debugData = array();

    /**
     * Instantiate Rakuten Checkout config model
     *
     * @return void
     */
    public function __construct()
    {
        $this->getConfig();
    }

    /**
     * Rakuten Checkout config model instance getter
     *
     * @return Mage_Rakuten_Model_Config
     */
    public function getConfig()
    {
        if (null === $this->_config) {
            $this->_config = Mage::getSingleton('rakuten/config');
        }
        return $this->_config;
    }

    /**
     * Get ROPE data, run corresponding handler
     *
     * @param  string $request - incoming XML request
     * @return string
     * @throws Exception
     */
    public function processRopeRequest($request)
    {
        $this->_request = $request;
        $this->_config->debug = true;

        $this->_debugData['request'] = $request;

        try {
            $this->_request = new SimpleXMLElement(urldecode($request), LIBXML_NOCDATA);

            // Check type of request and call proper handler
            switch ($this->_request->getName()) {
                case 'tradoria_check_order':
                    $this->_orderNode = 'order';
                    // Init Quote
                    if (!$this->_getQuote()) {
                        throw new Mage_Core_Exception('Error loading quote');
                    }
                    $responseTag = 'tradoria_check_order_response';
                    $response = $this->_checkOrder();
                    break;
                case 'tradoria_order_process':
                    $this->_orderNode = 'cart';
                    // Init Quote
                    if (!$this->_getQuote()) {
                        throw new Mage_Core_Exception('Error loading quote');
                    }
                    $responseTag = 'tradoria_order_process_response';
                    $response = $this->_processOrder();
                    break;
                case 'tradoria_order_status':
                    $responseTag = 'tradoria_order_status_response';
                    $response = $this->_statusUpdate();
                    break;
                default:
                    // Error - Unrecognized request
                    $responseTag = 'unknown_error';
                    $response = false;
            }
        } catch (Exception $e) {
            $this->_debugData['exception'] = $e->getMessage();
            Mage::logException($e);
            return $this->prepareResponse(false);
        }

        return $this->prepareResponse($response, $responseTag);
    }

    /**
     * Prepare XML response
     *
     * @param  bool $success - if need to prepare successful or unsuccessful response
     * @param  string $tag - root node tag for the response
     * @return string
     */
    public function prepareResponse($success, $tag = 'general_error')
    {
        if ($success === true) {
            $success = 'true';
        } elseif ($success === false) {
            $success = 'false';
        } else {
            $success = (string)$success;
        }

        $xml = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' ?><{$tag} />");
        $xml->addChild('success', $success);
        $response = $xml->asXML();

        $this->_debugData['response'] = $response;
        $this->_debug();

        return $response;
    }

    /**
     * Validate authentication data passed in the request against configuration values
     *
     * @return bool
     */
    protected function _auth()
    {
        $projectId = $this->_config->getProjectId();
        $apiKey = $this->_config->getApiKey();

        if ($this->_request->merchant_authentication->project_id == $projectId
            && $this->_request->merchant_authentication->api_key == $apiKey) {
            return true;
        }

        $this->_debugData['reason'] = 'Auth failed';
        return false;
    }

    /**
     * Get quote based on quote and store IDs from the request
     *
     * @return Mage_Sales_Model_Quote|bool
     */
    protected function _getQuote()
    {
        if (null === $this->_quote) {
            $quoteId = (int)$this->_request->{$this->_orderNode}->custom_1;
            $storeId = (int)$this->_request->{$this->_orderNode}->custom_2;

            if ($quoteId > 0) {
                $this->_quote = Mage::getModel('sales/quote');
                if ($storeId > 0) {
                    $this->_quote->setStoreId($storeId)->load($quoteId);
                } else {
                    $this->_quote->loadByIdWithoutStore($quoteId);
                }

                if ($this->_quote->isVirtual()) {
                    $this->_quote->getBillingAddress()->setPaymentMethod('rakuten');
                } else {
                    $this->_quote->getShippingAddress()->setPaymentMethod('rakuten');
                }
            } else {
                return false;
            }
        }

        return $this->_quote;
    }

    /**
     * Compare Magento quote and shopping cart details from the request
     *
     * @return bool
     */
    protected function _validateQuote()
    {
        $quoteItems = $this->_quote->getAllVisibleItems();

        $quoteItemsArray = array();
        $quoteItemsSku = array();

        /** @var $xmlItems SimpleXMLElement */
        $xmlItems = $this->_request->{$this->_orderNode}->items;

        $xmlItemsArray = array();
        $xmlItemsSku = array();

        foreach ($quoteItems as $item) {
            /** @var $item Mage_Sales_Model_Quote_Item */
            $quoteItemsArray[(string)$item->getSku()] = $item;
            $quoteItemsSku[] = (string)$item->getSku();
        }

        foreach ($xmlItems->children() as $item) {
            /** @var $item SimpleXMLElement */
            $xmlItemsArray[(string)$item->sku] = $item;
            $xmlItemsSku[] = (string)$item->sku;
        }

        $this->_debugData['quoteItemsSku'] = implode(', ', $quoteItemsSku);
        $this->_debugData['xmlItemsSku'] = implode(', ', $xmlItemsSku);

        // Validation of the shopping cart

        if (count($quoteItemsArray) != count($xmlItemsArray)) {
            $this->_debugData['reason'] = 'Quote validation failed: Qty of items';
            return false;
        }

        foreach ($quoteItemsArray as $sku=>$item) {
            if (!isset($xmlItemsArray[$sku])) {
                $this->_debugData['reason'] = 'Quote validation failed: SKU doesn\'t exist';
                return false;
            }
            $xmlItem = $xmlItemsArray[$sku];
            $checkoutHelper = Mage::helper('checkout');
            if ($item->getQty() != (int)$xmlItem->qty
                || $checkoutHelper->getPriceInclTax($item) != Mage::app()->getStore()->roundPrice((float)$xmlItem->price)
            ) {
                $this->_debugData['reason'] = 'Quote validation failed: Items don\'t match';
                return false;
            }
        }

        return true;
    }

    /**
     * Check qty in stock/product availability.
     * Called by Rakuten before order placement
     *
     * @return bool
     */
    protected function _checkOrder()
    {
        if (!$this->_auth()) {
            return false;
        }

        if (!$this->_validateQuote()) {
            return false;
        }

        $items = $this->_quote->getAllItems();

        foreach ($items as $item) {
            /** @var $item Mage_Sales_Model_Quote_Item */
            if (!$item->getProduct()->isAvailable()) {
                $this->_debugData['reason'] = 'Item availability check failed';
                return false;
            }
        }

        return true;
    }

    /**
     * Place the order
     *
     * @return bool
     */
    protected function _processOrder()
    {
        if (!$this->_auth()) {
            return false;
        }

        if (!$this->_validateQuote()) {
            return false;
        }

        // Push address to the quote, set totals,
        // Convert quote to the order,
        // Set rakuten_order attribute to "1"

        try {
            // To avoid duplicates look for order with the same Rakuten order no
            $orders = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('ext_order_id', (string)$this->_request->order_no);

            if (count($orders)) {
                $this->_debugData['reason'] = 'The same order already placed';
                return false;
            }

            // Import addresses and other data to quote
            $this->_quote->setIsActive(true)->reserveOrderId();
            $storeId = $this->_quote->getStoreId();

            Mage::app()->setCurrentStore(Mage::app()->getStore($storeId));
            if ($this->_quote->getQuoteCurrencyCode() != $this->_quote->getBaseCurrencyCode()) {
                Mage::app()->getStore()->setCurrentCurrencyCode($this->_quote->getQuoteCurrencyCode());
            }

            $billing = $this->_convertAddress('client');
            $this->_quote->setBillingAddress($billing);

            $shipping = $this->_convertAddress('delivery_address');
            $this->_quote->setShippingAddress($shipping);

            $this->_convertTotals($this->_quote->getShippingAddress());

            $this->_quote->getPayment()->importData(array('method'=>'rakuten'));

            /**
             * Convert quote to order
             *
             * @var $convertQuote Mage_Sales_Model_Convert_Quote
             */
            $convertQuote = Mage::getSingleton('sales/convert_quote');

            /* @var $order Mage_Sales_Model_Order */
            $order = $convertQuote->toOrder($this->_quote);

            if ($this->_quote->isVirtual()) {
                $convertQuote->addressToOrder($this->_quote->getBillingAddress(), $order);
            } else {
                $convertQuote->addressToOrder($this->_quote->getShippingAddress(), $order);
            }

            $order->setExtOrderId((string)$this->_request->order_no);
            $order->setExtCustomerId((string)$this->_request->client->client_id);

            if (!$order->getCustomerEmail()) {
                $order->setCustomerEmail($billing->getEmail())
                        ->setCustomerPrefix($billing->getPrefix())
                        ->setCustomerFirstname($billing->getFirstname())
                        ->setCustomerMiddlename($billing->getMiddlename())
                        ->setCustomerLastname($billing->getLastname())
                        ->setCustomerSuffix($billing->getSuffix())
                        ->setCustomerIsGuest(1);
            }

            $order->setBillingAddress(
                $convertQuote->addressToOrderAddress($this->_quote->getBillingAddress())
            );

            if (!$this->_quote->isVirtual()) {
                $order->setShippingAddress(
                    $convertQuote->addressToOrderAddress($this->_quote->getShippingAddress())
                );
            }

            /** @var $item Mage_Sales_Model_Quote_Item */
            foreach ($this->_quote->getAllItems() as $item) {
                $orderItem = $convertQuote->itemToOrderItem($item);
                if ($item->getParentItem()) {
                    $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
                }
                $order->addItem($orderItem);
            }

            /**
             * Adding transaction for correct transaction information displaying on the order view in the admin.
             * It has no influence on the API interaction logic.
             *
             * @var $payment Mage_Sales_Model_Order_Payment
             */
            $payment = Mage::getModel('sales/order_payment');
            $payment->setMethod('rakuten')
                ->setTransactionId((string)$this->_request->order_no)
                ->setIsTransactionClosed(false);
            $order->setPayment($payment);
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
            $order->setCanShipPartiallyItem(false);

            $message = '';

            if (trim((string)$this->_request->comment_client) != '') {
                $message .= $this->__('Customer\'s Comment: %s', '<strong>' . trim((string)$this->_request->comment_client) . '</strong><br />');
            }

            $message .= $this->__('Rakuten Order No: %s', '<strong>' . (string)$this->_request->order_no . '</strong><br />')
                        . $this->__('Rakuten Client ID: %s', '<strong>' . (string)$this->_request->client->client_id . '</strong><br />');

            $order->addStatusHistoryComment($message);

            $order->setRakutenOrder(1); // Custom attribute for fast filtering of orders placed via Rakuten Checkout
            $order->place();
            $order->save();
//            $order->sendNewOrderEmail();

            $this->_quote->setIsActive(false)->save();

            Mage::dispatchEvent('checkout_submit_all_after', array('order' => $order, 'quote' => $this->_quote));
        } catch (Exception $e) {
            $this->_debugData['exception'] = $e->getMessage();
            Mage::logException($e);
            return false;
        }

        return true;
    }

    /**
     * Update order status (create invoice, shipment, cancel the order)
     *
     * @return bool
     */
    protected function _statusUpdate()
    {
        if (!$this->_auth()) {
            return false;
        }

        try {
            $rakuten_order_no = (string)$this->_request->order_no;

            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order');
            $order->loadByAttribute('ext_order_id', $rakuten_order_no);

            /**
              * Check if order exists
              */
            if (!$order->getId()) {
                $this->_debugData['reason'] = 'No corresponding orders found';
                return false;
            }

            $status = (string)$this->_request->status;

            Mage::dispatchEvent('rakuten_update_status_before',
                                array('order' => $order, 'status' => $status, 'rakuten_order_no'=>$rakuten_order_no));

            switch ($status) {
                case 'editable':
                    // Processing
                    $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, true);
                    if (!$order->canInvoice()) {
                        $this->_debugData['reason'] = 'Invoicing is disabled for this order';
                        return false;
                    }

                    /** @var $invoice Mage_Sales_Model_Order_Invoice */
                    $invoice = $order->prepareInvoice();

                    if (!$invoice) {
                        $this->_debugData['reason'] = 'Cannot create invoice';
                        return false;
                    }

                    $invoice->register();
                    $invoice->getOrder()->setIsInProcess(true);

                    try {
                        $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();
                    } catch (Mage_Core_Exception $e) {
                        $this->_debugData['exception'] = $e->getMessage();
                        Mage::logException($e);
                        return false;
                    }

                    //$invoice->capture()->save();
                    $invoice->save();
                    break;

                case 'shipped':
                    // Shipped
                    $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_SHIP, true);
                    if (!$order->canShip()) {
                        $this->_debugData['reason'] = 'Shipping is disabled for this order';
                        return false;
                    }

                     /** @var $shipment Mage_Sales_Model_Order_Shipment */
                    $shipment = $order->prepareShipment();

                    if (!$shipment) {
                        $this->_debugData['reason'] = 'Cannot create shipment';
                        return false;
                    }

                    $shipment->register();
                    $shipment->getOrder()->setIsInProcess(true);

                    try {
                        $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($shipment)
                            ->addObject($shipment->getOrder())
                            ->save();
                    } catch (Mage_Core_Exception $e) {
                        $this->_debugData['exception'] = $e->getMessage();
                        Mage::logException($e);
                        return false;
                    }
                    break;

                case 'cancelled':
                    // Cancelled
                    $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);
                    if (!$order->canCancel()) {
                        // Force order cancellation (might have negative stock management implications)
                        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
                        Mage::dispatchEvent('order_cancel_after', array('order' => $order));
                        return true;
//                        $this->_debugData['reason'] = 'Cancellation is disabled for this order';
//                        return false;
                    }

                    $order->cancel()->save();
                    break;

                default:
                    // Error - Unrecognized request
                    $this->_debugData['reason'] = 'Unknown status';
                    return false;
            }

            Mage::dispatchEvent('rakuten_update_status_after',
                                array('order' => $order, 'status' => $status, 'rakuten_order_no'=>$rakuten_order_no));

        } catch (Exception $e) {
            $this->_debugData['exception'] = $e->getMessage();
            Mage::logException($e);
            return false;
        }

        return true;
    }

    /**
     * Converts address data from ROPE request to address object
     *
     * @param  string $addressNode
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function _convertAddress($addressNode='client')
    {
        $address = $this->_request->$addressNode;

        /** @var $addressObj Mage_Sales_Model_Quote_Address */
        $addressObj = Mage::getModel('sales/quote_address');
        
        $addressObj->setFirstname((string)$address->first_name)
            ->setLastname((string)$address->last_name)
            ->setCompany((string)$address->company)
            ->setStreet((string)$address->street . " " . (string)$address->street_no . "\n" . (string)$address->address_add)
            ->setPostcode((string)$address->zip_code)
            ->setCity((string)$address->city)
            ->setCountryId((string)$address->country)
            ->setEmail((string)$address->email)
            ->setTelephone((string)$address->phone);

        return $addressObj;
    }

    /**
     * Convert totals from ROPE request to quote address
     *
     * @param  Mage_Sales_Model_Quote_Address $address
     * @return void
     */
    protected function _convertTotals($address)
    {
        $address->setTaxAmount((float)$this->_request->total_tax_amount);
        $address->setBaseTaxAmount((float)$this->_request->total_tax_amount);

        /** @var $taxConfig Mage_Tax_Model_Config */
        $taxConfig = Mage::getSingleton('tax/config');
        $taxConfig->setShippingPriceIncludeTax(false);

        $method = 'rakuten_tablerate';
        $carrierTitle = 'Rakuten Checkout';
        $methodTitle = 'Tablerate';
        $rate = $this->_createShippingRate($method, $carrierTitle, $methodTitle)
                ->setPrice((float)$this->_request->shipping);

        $address->addShippingRate($rate)
                ->setShippingMethod($method)
                ->setShippingAmountForDiscount(0);

        $address->setGrandTotal((float)$this->_request->total);
        $address->setBaseGrandTotal((float)$this->_request->total);
    }

    /**
     * Creates shipping rate by method code
     * Sets shipping rate's accurate description, titles and so on
     *
     * @param  string $code
     * @param  string $carrierTitle
     * @param  string $methodTitle
     * @return Mage_Sales_Model_Quote_Address_Rate
     */
    protected function _createShippingRate($code, $carrierTitle, $methodTitle)
    {
        /** @var $rate Mage_Sales_Model_Quote_Address_Rate */
        $rate = Mage::getModel('sales/quote_address_rate');
        $rate->setCode($code);

        list($carrier, $method) = explode('_', $code, 2);

        $rate->setCarrier($carrier)
            ->setCarrierTitle($carrierTitle)
            ->setMethod($method)
            ->setMethodTitle($methodTitle);

        return $rate;
    }

    /**
     * Log debug data to file
     *
     * @param  mixed $debugData
     * @return void
     */
    protected function _debug()
    {
        if ($this->_config->debug) {
            $file = self::DEFAULT_LOG_FILE;
            /** @var $log Mage_Core_Model_Log_Adapter */
            $log = Mage::getModel('core/log_adapter', $file);
            $log->log($this->_debugData);
        }
    }

    /**
     * Translator function emulator for ROPE model
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), 'Rakuten_Checkout');
        array_unshift($args, $expr);
        return Mage::app()->getTranslator()->translate($args);
    }
}
