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
 * Main Rakuten checkout controller
 */
class Rakuten_Checkout_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get redirect URL and redirect customer to Rakuten Checkout
     * or open Rakuten Checkout in the iFrame
     * 
     * @return void
     */
    public function indexAction()
    {
        /** @var $rockout Rakuten_Checkout_Model_Checkout */
        $rockout = Mage::getModel('rakuten/checkout');

        /** @var $config Rakuten_Checkout_Model_Config */
        $config = $rockout->getConfig();

        // Check which method will be used: Standard or Inline
        if ($config->getMethod() == Rakuten_Checkout_Model_Config::METHOD_STANDARD) {
            // Redirect to Rakuten Checkout
            if ($redirectUrl = $rockout->getRedirectUrl()) {
                $this->_redirectUrl($redirectUrl);
            } else {
                $this->_redirect('checkout/cart');
            }
        } elseif ($config->getMethod() == Rakuten_Checkout_Model_Config::METHOD_INLINE) {
            // Open Rakuten Checkout in the iFrame
            if ($inlineCode = $rockout->getRedirectUrl(true)) {
                $this->loadLayout();
                /** @var $block Rakuten_Checkout_Block_Iframe */
                $block = $this->getLayout()->getBlock('rakuten.iframe');
                $block->setInlineCode($inlineCode);
                $this->renderLayout();
            } else {
                $this->_redirect('checkout/cart');
            }
        } else {
            $rockout->getCheckout()->addError(Mage::helper('rakuten')->__('Unknown payment method.'));
            $this->_redirect('checkout/cart');
        }

        return;
    }
}