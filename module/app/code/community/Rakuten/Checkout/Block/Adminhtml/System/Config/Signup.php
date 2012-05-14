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
 * Show signup buttons in the Rakuten configuration
 *
 * @method Rakuten_Checkout_Block_Adminhtml_System_Config_Signup setButtonUrl(string $buttonUrl)
 * @method string getButtonUrl()
 * @method Rakuten_Checkout_Block_Adminhtml_System_Config_Signup setButtonLabel(string $buttonLabel)
 * @method string getButtonLabel()
 * @method Rakuten_Checkout_Block_Adminhtml_System_Config_Signup setHtmlId(string $htmlId)
 * @method string getHtmlId()
 * @method Rakuten_Checkout_Block_Adminhtml_System_Config_Signup setSandboxButtonUrl(string $buttonUrl)
 * @method string getSandboxButtonUrl()
 * @method Rakuten_Checkout_Block_Adminhtml_System_Config_Signup setSandboxButtonLabel(string $buttonLabel)
 * @method string getSandboxButtonLabel()
 * @method Rakuten_Checkout_Block_Adminhtml_System_Config_Signup setSandboxHtmlId(string $htmlId)
 * @method string getSandboxHtmlId()
 */
class Rakuten_Checkout_Block_Adminhtml_System_Config_Signup extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('rakuten/system/config/signup.phtml');
        }
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get buttons
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => Mage::helper('rakuten')->__($originalData['button_label']),
            'button_url'   => $originalData['button_url'],
            'html_id' => $element->getHtmlId(),
            'sandbox_button_label' => Mage::helper('rakuten')->__($originalData['sandbox_button_label']),
            'sandbox_button_url'   => $originalData['sandbox_button_url'],
            'sandbox_html_id' => 'sandbox_' . $element->getHtmlId(),
        ));
        return $this->_toHtml();
    }
}