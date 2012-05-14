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
 * Rakuten checkout button
 *
 * @method Rakuten_Checkout_Block_Button setShortcutHtmlId(string $shortcutHtmlId)
 * @method string getShortcutHtmlId()
 * @method Rakuten_Checkout_Block_Button setCheckoutUrl(string $checkoutUrl)
 * @method string getCheckoutUrl()
 * @method Rakuten_Checkout_Block_Button setImageUrl(string $imageUrl)
 * @method string getImageUrl()
 * @method boolean getIsInCatalogProduct()
 */
class Rakuten_Checkout_Block_Button extends Mage_Core_Block_Template
{
    /**
     * Whether the block should be eventually rendered
     *
     * @var bool
     */
    protected $_shouldRender = true;

    /**
     * Check if rendering is required and pre-render the block
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _beforeToHtml()
    {
        $result = parent::_beforeToHtml();

        /** @var $config Rakuten_Checkout_Model_Config */
        $config = Mage::getSingleton('rakuten/config');

        if (!$config->isEnabled()) {
            $this->_shouldRender = false;
            return $result;
        }

        $isInCatalog = $this->getIsInCatalogProduct();
        /** @var $checkoutSession Mage_Checkout_Model_Session */
        $checkoutSession = Mage::getSingleton('checkout/session');
        /** @var $quote Mage_Sales_Model_Quote|null */
        $quote = ($isInCatalog || '' == $this->getIsQuoteAllowed())
            ? null : $checkoutSession->getQuote();

        if ($isInCatalog) {
            // Show Rakuten button on a product view page if product has price > 0
            /** @var $currentProduct Mage_Catalog_Model_Product */
            $currentProduct = Mage::registry('current_product');
            if (!is_null($currentProduct)) {
                $productPrice = (float)$currentProduct->getFinalPrice();
                if (empty($productPrice)) {
                    $this->_shouldRender = false;
                    return $result;
                }
            }
        }

        // Validate minimum quote amount and validate quote for zero grand total
        if (null !== $quote && (!$quote->validateMinimumAmount()
            || (!$quote->getGrandTotal() && !$quote->hasNominalItems()))) {
            $this->_shouldRender = false;
            return $result;
        }

        // Set misc data
        /** @var $coreHelper Mage_Core_Helper_Data */
        $coreHelper = $this->helper('core');
        $this->setShortcutHtmlId($coreHelper->uniqHash('rakuten_'))
            ->setCheckoutUrl($config->getCheckoutStartUrl());

        // Set Rakuten button image
        $this->setImageUrl(
                $config->getButtonImageUrl(Mage::app()->getLocale()->getLocaleCode())
            );

        return $result;
    }

    /**
     * Render the block if needed
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_shouldRender) {
            return '';
        }
        return parent::_toHtml();
    }
}