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
 * Main Rakuten Checkout model
 */
class Rakuten_Checkout_Model_Checkout extends Mage_Payment_Model_Method_Abstract
{
    const ACTION_AUTHORIZE = 0;
    const ACTION_AUTHORIZE_CAPTURE = 1;

    /**
     * Rakuten Checkout payment code
     *
     * @var string
     */
    protected $_code  = 'rakuten';

    /**
     * Rakuten config model instance
     * 
     * @var Rakuten_Checkout_Model_Config
     */
    protected $_config = null;

    /**
     * Merchant ID
     *
     * @var string
     */
    protected $_merchantId = '';

    /**
     * Project Id
     *
     * @var string
     */
    protected $_projectId = '';

    /**
     * API key
     *
     * @var string
     */
    protected $_apiKey = '';

    /**
     * Current language
     *
     * @var string
     */
    protected $_language = '';

    /**
     * Current currency
     *
     * @var string
     */
    protected $_currency = '';

    /**
     * Customer type (1 - all, 2 - business, 3 - private)
     *
     * @var string
     */
    protected $_customerType = '';

    /**
     * Countries for billing address restrictions
     *
     * @var string
     */
    protected $_countries = '';

    /**
     * Collected debug information
     *
     * @var array
     */
    protected $_debugData = array();

    /**
     * Available options
     */
    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = false;
    protected $_canUseForMultishipping  = false;

    /**
     * Model constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->getConfig();

        $this->_projectId       = $this->_config->getProjectId();
        $this->_apiKey          = $this->_config->getApiKey();
        $this->_language        = $this->_config->getLanguage();
        $this->_currency        = $this->_config->getCurrentCurrency();
        $this->_merchantId      = $this->_config->getMerchantId();
        $this->_customerType    = $this->_config->getCustomerType();
        $this->_countries       = $this->_config->getCountries();

        return parent::__construct();
    }

    /**
     * Check if editing the order is allowed
     *
     * @return bool
     */
    public function canEdit()
    {
        return false;
    }

    /**
     * Check if voiding the order is allowed
     *
     * @param   Varien_Object $payment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment)
    {
        return false;
    }

    /**
     * Convert encoding of the string to UTF-8
     * and escape ampersands in the string for XML
     * (required by addChild() simpleXML function)
     *
     * @param  string $string
     * @return string
     */
    protected function _escapeStr($string)
    {
        $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        $string = str_replace('&', '&amp;', $string);
        return $string;
    }

    /**
     * Add CDATA to simpleXML node
     *
     * @param  SimpleXMLElement $node
     * @param  string $value
     * @return void
     */
    protected function _addCDATA($node, $value)
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        $domNode = dom_import_simplexml($node);
        $domDoc = $domNode->ownerDocument;
        $domNode->appendChild($domDoc->createCDATASection($value));
    }
    
    /**
     * Get redirect URL or inline iFrame code
     *
     * @param  bool $inline
     * @return bool
     * @throws Exception|Mage_Core_Exception
     */
    public function getRedirectUrl($inline = false)
    {
        // Is current currency supported?
        if (!$this->canUseForCurrency()) {
            return false;
        }

        // Create Rakuten Checkout Insert Cart XML request
        $xml = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' ?><tradoria_insert_cart />");

        $merchantAuth = $xml->addChild('merchant_authentication');
        $merchantAuth->addChild('project_id', $this->_projectId);
        $merchantAuth->addChild('api_key', $this->_apiKey);

        $xml->addChild('language', $this->_language);
        $xml->addChild('currency', $this->_currency);

        $merchantCart = $xml->addChild('merchant_carts')->addChild('merchant_cart');
        // $merchantCart->addAttribute('merchant_id', $this->_merchantId);

        $quoteId = $this->getQuoteId();
        $storeId = $this->getStoreId();

        $merchantCart->addChild('custom_1', $quoteId);
        $merchantCart->addChild('custom_2', $storeId);
        $merchantCart->addChild('custom_3');
        $merchantCart->addChild('custom_4');

        $merchantCartItems = $merchantCart->addChild('items');

        // $items = $this->getQuote()->getAllItems();
        $items = $this->getQuote()->getAllVisibleItems();

        /** @var $item Mage_Sales_Model_Quote_Item */
        foreach ($items as $item) {
            $merchantCartItemsItem = $merchantCartItems->addChild('item');

            $merchantCartItemsItemName = $merchantCartItemsItem->addChild('name');
            $this->_addCDATA($merchantCartItemsItemName, $item->getName());

            $merchantCartItemsItem->addChild('sku', $this->_escapeStr($item->getSku())); // THIS ONE IS SHOWN
            $merchantCartItemsItem->addChild('external_product_id'); // this one is not shown (optional)
            $merchantCartItemsItem->addChild('qty', $item->getQty()); // positive integers
            /** @var $checkoutHelper Mage_Checkout_Helper_Data */
            $checkoutHelper = Mage::helper('checkout');
            $merchantCartItemsItem->addChild('unit_price', $checkoutHelper->getPriceInclTax($item));
            $merchantCartItemsItem->addChild('tax_class', $this->getRakutenTaxClass($item->getTaxPercent()));
            $merchantCartItemsItem->addChild('image_url', $this->_escapeStr($item->getProduct()->getThumbnailUrl()));
                                                                             // depracated method used for better backwards compatibility
            $merchantCartItemsItem->addChild('product_url', $this->_escapeStr($item->getProduct()->getProductUrl()));

            /** @var $helper Mage_Catalog_Helper_Product_Configuration */
            // It'll work starting Magento CE 1.5.x only
            //$helper = Mage::helper('catalog/product_configuration');
            //$options = $helper->getOptions($item);

            // So for backwards compatibility duplicating methods from the helper above
            $options = $this->_getOptions($item);

            if (!empty($options)) {
                $custom = serialize($options);
                $comment = array();
                foreach ($options as $option) {
                    $comment[] = "{$option['label']}: {$option['value']}";
                }
                $comment = implode('; ', $comment);
            } else {
                $custom = '';
                $comment = '';
            }

            $merchantCartItemsItemComment = $merchantCartItemsItem->addChild('comment');
            $this->_addCDATA($merchantCartItemsItemComment, $comment);

            $merchantCartItemsItemCustom = $merchantCartItemsItem->addChild('custom');
            $this->_addCDATA($merchantCartItemsItemCustom, $custom);
        }

        $merchantCartShippingRates = $merchantCart->addChild('shipping_rates');

        $shippingRates = $this->_config->getShippingRates();

        foreach ($shippingRates as $shippingRate) {
            $merchantCartShippingRate = $merchantCartShippingRates->addChild('shipping_rate');
            $merchantCartShippingRate->addChild('country', $shippingRate['country']);
            $merchantCartShippingRate->addChild('price', $shippingRate['price']);
        }

        $billingAddressRestrictions = $xml->addChild('billing_address_restrictions');
                                            // restrict invoice address to require private / commercial and by country
        $billingAddressRestrictions->addChild('customer_type')->addAttribute('allow', $this->_customerType);
                                                                                        // 1=all 2=business 3=private

        if ($this->_countries) {
            $billingAddressRestrictions->addChild('countries')->addAttribute('allow', $this->_countries);
        }

        $callbackUrl = $this->_config->getCallbackUrl();
        $pipeUrl = $this->_config->getPipeUrl();
        $xml->addChild('callback_url', $this->_escapeStr($callbackUrl));
        $xml->addChild('pipe_url', $this->_escapeStr($pipeUrl));

        $request = $xml->asXML();

        $response = $this->sendRequest($request);

        $redirectUrl = false;
        $inlineUrl = false;
        $inlineCode = false;

        try {
            $response = new SimpleXMLElement($response);

            if ($response->success != 'true') {
                throw new Mage_Core_Exception(Mage::helper('rakuten')->__('Error #%s: %s', $response->code, $response->message));
            } else {
                $redirectUrl = $response->redirect_url;
                $inlineUrl = $response->inline_url;
                $inlineCode = $response->inline_code;
            }
        } catch (Mage_Core_Exception $e) {
            $this->getCheckout()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->getCheckout()->addError(Mage::helper('rakuten')->__('Unable to redirect to Rakuten Checkout.'));
            Mage::logException($e);
        }

        if ($inline) {
            return $inlineCode;
        } else {
            return $redirectUrl;
        }
    }

    /**
     * Send order shipment to Rakuten Checkout
     *
     * @param  Mage_Sales_Model_Order_Shipment $shipment
     * @return bool
     * @throws Exception|Mage_Core_Exception
     */
    public function sendShipment($shipment)
    {
        // Create Rakuten Checkout Send Order Shipment XML request
        $xml = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' ?><tradoria_order_shipment />");

        $merchantAuth = $xml->addChild('merchant_authentication');
        $merchantAuth->addChild('project_id', $this->_projectId);
        $merchantAuth->addChild('api_key', $this->_apiKey);

        /** @var $order Mage_Sales_Model_Order */
        $order = $shipment->getOrder();

        $xml->addChild('order_no', $order->getExtOrderId());

        $carrierTrackingId = $xml->addChild('carrier_tracking_id');
        $carrierTrackingUrl = $xml->addChild('carrier_tracking_url');
        $carrierTrackingCode = $xml->addChild('carrier_tracking_code');

        if ($tracks = $shipment->getAllTracks()) {
            /** @var $track Mage_Sales_Model_Order_Shipment_Track */
            $track = array_shift($tracks); // Multiple tracking codes for one shipment are supported by Magento

            $this->_addCDATA($carrierTrackingId, $track->getCarrierCode());
            $this->_addCDATA($carrierTrackingCode, $track->getNumber());
        }
        
        $request = $xml->asXML();

        $response = $this->sendRequest($request, 'shipment');

        try {
            $response = new SimpleXMLElement($response);

            if ($response->success != 'true') {
                throw new Mage_Core_Exception((string)$response->message, (int)$response->code);
            } else {
                if ((string)$response->invoice_number != '') {
                    $comment = Mage::helper('rakuten')->__('Rakuten Invoice No: %s', '<strong>' . (string)$response->invoice_number . '</strong><br />');
                    $shipment->addComment($comment);
                    $order->addStatusHistoryComment($comment);
                }
            }
        } catch (Mage_Core_Exception $e) {
            throw $e;
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }

        return true;
    }

    /**
     * Send request to Rakuten Checkout
     *
     * @param  string $xml
     * @return array|bool|string
     * @throws Exception
     */
    public function sendRequest($xml, $type = 'default')
    {
        try {
            $this->_debugData['request_url'] = $this->_config->getRockinUrl($type);
            $this->_debugData['request'] = $xml;

            $httpAdapter = new Varien_Http_Adapter_Curl();
            $httpAdapter->write(Zend_Http_Client::POST, $this->_config->getRockinUrl($type), '1.1', array(), $xml);

            $response = $httpAdapter->read();
        } catch (Exception $e) {
            $this->_debugData['http_error'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
            $this->_debug($this->_debugData);
            throw $e;
        }

        $this->_debugData['response'] = $response;

        $response = preg_split('/^\r?$/m', $response, 2);
        if (isset($response[1])) {
            $response = trim($response[1]);
        } else {
            $response = false;
        }

        $this->_debugData['response_processed'] = $response;
        $this->_debug($this->_debugData);

        return $response;
    }

    /**
     * Check if current currency is supported by Rakuten Checkout
     *
     * @param  string|bool $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode = null)
    {
        if (is_null($currencyCode)) {
            $currencyCode = $this->_config->getCurrentCurrency();
        }
        if (!$this->_config->isCurrencySupported($currencyCode)) {
            $supportedCurrencies = $this->_config->getSupportedCurrencyCodes();
            if (count($supportedCurrencies) == 1) {
                $supportedCurrencies = array_shift($supportedCurrencies);
                $supportedCurrencies = Mage::helper('rakuten')->__('Supported currency is %s.', $supportedCurrencies);
            } else {
                $supportedCurrencies = implode(', ', $supportedCurrencies);
                $supportedCurrencies = Mage::helper('rakuten')->__('Supported currencies are %s.', $supportedCurrencies);
            }

            $this->getCheckout()->addError(
                Mage::helper('rakuten')->
                        __('Current currency %s isn\'t supported by Rakuten Checkout.', $currencyCode)
                . $supportedCurrencies
            );

            return false;
        }

        return true;
    }

    /**
     * Check if current currency is supported by Rakuten Checkout
     *
     * @param  float $percent
     * @return string
     */
    public function getRakutenTaxClass($percent)
    {
        if ($taxClass = array_search($percent, $this->_config->taxClassMap)) {
            return $taxClass;
        } else {
            return $this->_config->taxClassDefault;
        }
    }

    /**
     * Get checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Get current quote ID
     *
     * @return int
     */
    public function getQuoteId()
    {
        return $this->getCheckout()->getQuoteId();
    }

    /**
     * Get current store ID
     *
     * @return int
     */
    public function getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    /**
     * Get Rakuten Checkout config model instance
     *
     * @return Mage_Rakuten_Model_Config
     */
    public function getConfig()
    {
        if (null === $this->_config) {
            $params = array($this->_code);
            if ($store = $this->getStore()) {
                $params[] = is_object($store) ? $store->getId() : $store;
            }
            $this->_config = Mage::getSingleton('rakuten/config', $params);
        }
        return $this->_config;
    }

    // Duplication of Mage_Catalog_Helper_Product_Configuration methods from Magento CE 1.6.x
    // for backwards compatibility with Magento CE 1.4.x

    /**
     * Retrieves product configuration options
     *
     * @param  Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    protected function _getCustomOptions($item)
    {
        $product = $item->getProduct();
        $options = array();
        $optionIds = $item->getOptionByCode('option_ids');
        if ($optionIds) {
            $options = array();
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                $option = $product->getOptionById($optionId);
                if ($option) {
                    $itemOption = $item->getOptionByCode('option_' . $option->getId());
                    $group = $option->groupFactory($option->getType())
                        ->setOption($option)
                        ->setConfigurationItem($item)
                        ->setConfigurationItemOption($itemOption);

                    if ('file' == $option->getType()) {
                        $downloadParams = $item->getFileDownloadParams();
                        if ($downloadParams) {
                            $url = $downloadParams->getUrl();
                            if ($url) {
                                $group->setCustomOptionDownloadUrl($url);
                            }
                            $urlParams = $downloadParams->getUrlParams();
                            if ($urlParams) {
                                $group->setCustomOptionUrlParams($urlParams);
                            }
                        }
                    }

                    $options[] = array(
                        'label' => $option->getTitle(),
                        'value' => $group->getFormattedOptionValue($itemOption->getValue()),
                        'print_value' => $group->getPrintableOptionValue($itemOption->getValue()),
                        'option_id' => $option->getId(),
                        'option_type' => $option->getType(),
                        'custom_view' => $group->isCustomizedView()
                    );
                }
            }
        }

        $addOptions = $item->getOptionByCode('additional_options');
        if ($addOptions) {
            $options = array_merge($options, unserialize($addOptions->getValue()));
        }

        return $options;
    }

    /**
     * Retrieves configuration options for configurable product
     *
     * @param  Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    protected function _getConfigurableOptions($item)
    {
        $product = $item->getProduct();
        /** @var $typeInstance Mage_Catalog_Model_Product_Type_Configurable */
        $typeInstance = $product->getTypeInstance(true);
        $attributes = $typeInstance->getSelectedAttributesInfo($product);
        return array_merge($attributes, $this->_getCustomOptions($item));
    }

    /**
     * Retrieves configuration options for grouped product
     *
     * @param  Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    protected function _getGroupedOptions($item)
    {
        $product = $item->getProduct();

        $options = array();
        /** @var $typeInstance Mage_Catalog_Model_Product_Type_Grouped */
        $typeInstance = $product->getTypeInstance(true);
        $associatedProducts = $typeInstance->getAssociatedProducts($product);

        if ($associatedProducts) {
            foreach ($associatedProducts as $associatedProduct) {
                /** @var $associatedProduct Mage_Catalog_Model_Product */
                $qty = $item->getOptionByCode('associated_product_' . $associatedProduct->getId());
                $option = array(
                    'label' => $associatedProduct->getName(),
                    'value' => ($qty && $qty->getValue()) ? $qty->getValue() : 0
                );

                $options[] = $option;
            }
        }

        return array_merge($options, $this->_getCustomOptions($item));
    }

    /**
     * Retrieves product options list
     *
     * @param  Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    protected function _getOptions($item)
    {
        $typeId = $item->getProduct()->getTypeId();
        switch ($typeId) {
            case Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE:
                return $this->_getConfigurableOptions($item);
                break;
            case Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE:
                return $this->_getGroupedOptions($item);
                break;
        }
        return $this->_getCustomOptions($item);
    }

}
