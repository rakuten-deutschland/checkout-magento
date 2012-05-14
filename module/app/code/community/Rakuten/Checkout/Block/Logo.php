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
 * Rakuten Logo block
 *
 * @method Rakuten_Checkout_Block_Logo setLogoImageUrl(string $logoUrl)
 * @method string getLogoImageUrl()
 */
class Rakuten_Checkout_Block_Logo extends Mage_Core_Block_Template
{
    /**
     * Rakuten config model instance
     *
     * @var Rakuten_Checkout_Model_Config
     */
    protected $_config = null;

    /**
     * Current locale model instance
     *
     * @var Mage_Core_Model_Locale
     */
    protected $_locale = null;

    /**
     * Block constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_config = Mage::getSingleton('rakuten/config');
        $this->_locale = Mage::app()->getLocale();
    }

    /**
     * Return URL for Rakuten Info page
     *
     * @return string
     */
    public function getAboutRakutenPageUrl()
    {
        return $this->_config->getRakutenInfoUrl($this->_locale);
    }

    /**
     * Disable block output if logo is turned off
     *
     * @return string
     */
    protected function _toHtml()
    {
        $logoUrl = $this->_config->getLogoImageUrl($this->_locale);
        if (!$logoUrl) {
            return '';
        }
        $this->setLogoImageUrl($logoUrl);
        return parent::_toHtml();
    }
}