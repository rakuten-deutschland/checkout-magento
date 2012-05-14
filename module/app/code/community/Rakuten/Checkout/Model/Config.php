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
 * Rakuten Checkout config model
*/
class Rakuten_Checkout_Model_Config
{
    const MERCHANT_ID                   = '2';

    const METHOD_STANDARD               = '0';
    const METHOD_INLINE                 = '1';

    const ROCKIN_SANDBOX_URL            = 'https://sandbox.rakuten-checkout.de/rockin';
    const ROCKIN_LIVE_URL               = 'https://secure.rakuten-checkout.de/rockin';

    const ROCKIN_SHIPMENT_SANDBOX_URL   = 'https://sandbox.rakuten-checkout.de/rockin/shipment';
    const ROCKIN_SHIPMENT_LIVE_URL      = 'https://secure.rakuten-checkout.de/rockin/shipment';

    const ROCKOUT_START_URL             = 'rakuten/checkout';
    const ROCKBACK_URL                  = 'rakuten/rope/index';

    const RAKUTEN_PIPE_URL              = 'https://images.rakuten-checkout.de/images/files/pipe.html';
    const PIPE_URL                      = 'rakuten/rope/pipe';

    const LOGO_IMG                      = 'images/rakuten/payment_banner_small.png';
    const RAKUTEN_INFO_URL              = 'http://checkout.rakuten.de/';

    const BUTTON_IMG                    = 'images/rakuten/checkout-button_light-bg_175x50.png';

    /**
     * If debugging is allowed
     * 
     * @var bool
     */
    public $debug = false;

    /**
     * Current store id
     *
     * @var int
     */
    protected $_storeId = null;

    /**
     * Currency codes supported by Rakuten Checkout
     *
     * @var array
     */
    protected $_supportedCurrencyCodes = array('EUR');

    /**
     * Merchant countries supported by Rakuten Checkout
     *
     * @var array
     */
    protected $_supportedMerchantCountryCodes = array('AT','DE');

    /**
     * Buyer country supported by Rakuten Checkout
     *
     * @var array
     */
    protected $_supportedBuyerCountryCodes = array('AT', 'DE');

    /**
     * Languages supported by Rakuten Checkout
     *
     * @var array
     */
    protected $_supportedLanguages = array('DE');

    /**
     * Tax class mapping
     *
     * @var array
     */
    public $taxClassMap = array(
        '1' => 0,       // DE 0%
        '2' => 7,       // DE 7%
        '3' => 10.7,    // DE 10.7%
        '4' => 19,      // DE 19%
        //'5' => 0,     // AT 0%
        '6' => 10,      // AT 10%
        '7' => 12,      // AT 12%
        '8' => 20,      // AT 20%
    );

    /**
     * Default tax class
     *
     * @var string
     */
    public $taxClassDefault = '4';

    /**
     * Model constructor
     * Set store id, if specified
     *
     * @param  array $params
     * @return void
     */
    public function __construct($params = array())
    {
        if ($params) {
            $storeId = array_shift($params);
            $this->setStoreId($storeId);
        }
    }

    /**
     * Store ID setter
     *
     * @param  int $storeId
     * @return Rakuten_Checkout_Model_Config
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = (int)$storeId;
        return $this;
    }

    /**
     * Return currency codes supported by Rakuten Checkout
     *
     * @return array
     */
    public function getSupportedCurrencyCodes()
    {
        return $this->_supportedCurrencyCodes;
    }

    /**
     * Get current currency
     *
     * @return string
     */
    public function getCurrentCurrency()
    {
        return Mage::app()->getStore($this->_storeId)->getCurrentCurrencyCode();
    }

    /**
     * Check if specified currency code is supported by Rakuten Checkout
     * Check if current currency is supported if no currency specified
     *
     * @param  string $code
     * @return bool
     */
    public function isCurrencySupported($code = null)
    {
        if (is_null($code)) {
            $code = $this->getCurrentCurrency();
        }
        if (in_array($code, $this->_supportedCurrencyCodes)) {
            return true;
        }
        return false;
    }

    /**
     * Return merchant country codes supported by Rakuten Checkout
     *
     * @return array
     */
    public function getSupportedMerchantCountryCodes()
    {
        return $this->_supportedMerchantCountryCodes;
    }

    /**
     * Return buyer country codes supported by Rakuten Checkout
     *
     * @return array
     */
    public function getSupportedBuyerCountryCodes()
    {
        return $this->_supportedBuyerCountryCodes;
    }

    /**
     * Return merchant country code, use default country if it isn't specified in General settings
     *
     * @return string
     */
    public function getMerchantCountry()
    {
        /** @var $coreHelper Mage_Core_Helper_Data */
        $coreHelper = Mage::helper('core');
        return $coreHelper->getDefaultCountry($this->_storeId);
    }

    /**
     * Check if buyer country is supported by Rakuten Checkout
     *
     * @param  string $code
     * @return bool
     */
    public function isBuyerCountrySupported($code)
    {
        if (in_array($code, $this->_supportedBuyerCountryCodes)) {
            return true;
        }
        return false;
    }

    /**
     * Integration methods supported by Rakuten Checkout
     *
     * @return array
     */
    public function getIntegrationMethods()
    {
        return array(
            self::METHOD_STANDARD => Mage::helper('rakuten')->__('Standard'),
            self::METHOD_INLINE => Mage::helper('rakuten')->__('Inline'),
        );
    }

    /**
     * Billing address restrictions
     *
     * @return array
     */
    public function getBillingAddrRestrictions()
    {
        return array(
            '1' => Mage::helper('rakuten')->__('All Addresses'),
            '2' => Mage::helper('rakuten')->__('Business Addresses Only'),
            '3' => Mage::helper('rakuten')->__('Private Addresses Only')
        );
    }

    /**
     * Get Rakuten Checkout configuration value by parameter subpath
     *
     * @param  string $subpath
     * @return mixed
     */
    protected function _getConfigValue($subpath)
    {
        return Mage::getStoreConfig('rakuten/'.$subpath);
    }

    /**
     * Get integration method (0 - standard, 1 - inline)
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_getConfigValue('general/method');
    }

    /**
     * Check if Rakuten Checkout enabled in the configuration (0/1)
     *
     * @return string
     */
    public function isEnabled()
    {
        return Mage::getStoreConfig('payment/rakuten/active');
    }

    /**
     * Check if Rakuten Checkout is in sandbox mode (0/1)
     *
     * @return string
     */
    public function isSandbox()
    {
        return $this->_getConfigValue('general/sandbox');
    }

    /**
     * Get merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        return self::MERCHANT_ID;
    }

    /**
     * Get project ID
     *
     * @return string
     */
    public function getProjectId()
    {
        return $this->_getConfigValue('auth/project_id');
    }

    /**
     * Get API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->_getConfigValue('auth/api_key');
    }

    /**
     * Parse given locale to get language code used by Rakuten Checkout
     *
     * @param  string $localeCode
     * @return string
     */
    protected function _getLanguageFromLocale($localeCode)
    {
        $language = explode('_',$localeCode);
        $language = array_shift($language);
        $language = strtoupper($language);
        return $language;
    }

    /**
     * Check whether specified language code is supported
     * Fallback to DE
     *
     * @param  string $languageCode
     * @return string
     */
    protected function _getSupportedLanguage($languageCode = null)
    {
        if (!$languageCode || !in_array($languageCode, $this->_supportedLanguages)) {
            return 'DE';
        }
        return $languageCode;
    }

    /**
     * Get Checkout language based on current locale and supported languages
     *
     * @return string
     */
    public function getLanguage()
    {
        $locale = Mage::getStoreConfig('general/locale/code');
        $language = $this->_getLanguageFromLocale($locale);
        $language = $this->_getSupportedLanguage($language);
        return $language;
    }

    /**
     * Parse CSV string, return array of values
     *
     * @param  string $input
     * @param  string $delimiter
     * @param  string $enclosure
     * @param  null $escape
     * @param  null $eol
     * @return array
     */
    protected function _strGetCsv($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null)
    {
        $temp=fopen("php://memory", "rw");
        fwrite($temp, $input);
        fseek($temp, 0);
        $r = array();
        while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure)) !== false) {
            $r[] = $data;
        }
        fclose($temp);
        return $r;
    }

    /**
     * Get configured shipping rates
     *
     * @return array
     */
    public function getShippingRates()
    {
        $shippingRates = $this->_getConfigValue('general/shipping_rates');
        $shippingRates = $this->_strGetCsv($shippingRates);

        $processedShippingRates = array();
        foreach ($shippingRates as $shippingRate) {
            if (isset($shippingRate[0]) && isset($shippingRate[1]) && is_numeric($shippingRate[1])
                    && $this->isBuyerCountrySupported($shippingRate[0])) {
                $processedShippingRates[] = array(
                    'country' => (string)$shippingRate[0],
                    'price' => (float)$shippingRate[1],
                    'days_in_transit' => (isset($shippingRate[2]) ? (int)$shippingRate[2] : null)
                );
            }
        }

        return $processedShippingRates;
    }

    /**
     * Get current billing address restriction rules:
     * 1 - All Addresses
     * 2 - Business Addresses Only
     * 3 - Private Addresses Only
     *
     * @return string
     */
    public function getCustomerType()
    {
        return $this->_getConfigValue('misc/billing_addr_restrictions');
    }

    /**
     * Get list of configured supported countries
     *
     * @return bool|mixed
     */
    public function getCountries()
    {
        if ($this->_getConfigValue('misc/allowspecific')==1) {
            return $this->_getConfigValue('misc/specificcountry');
        } else {
            return false;
        }
    }

    /**
     * Get URL for skin files
     *
     * @param  string $file - path to file in skin
     * @param  array $params
     * @return string
     */
    public function getSkinUrl($file = null, array $params = array())
    {
        return Mage::getDesign()->getSkinUrl($file, $params);
    }

    /**
     * Get Rakuten Checkout button image
     *
     * @param  string|Mage_Core_Model_Locale $localeCode
     * @return string
     */
    public function getButtonImageUrl($localeCode)
    {
        return $this->getSkinUrl(self::BUTTON_IMG);
    }

    /**
     * Get Rakuten Checkout logo image
     *
     * @param  string|Mage_Core_Model_Locale $localeCode
     * @return string
     */
    public function getLogoImageUrl($localeCode)
    {
        return $this->getSkinUrl(self::LOGO_IMG);
    }

    /**
     * Get "About Rakuten Checkout" info URL
     *
     * @param  string|Mage_Core_Model_Locale $localeCode
     * @return string
     */
    public function getRakutenInfoUrl($localeCode)
    {
        return self::RAKUTEN_INFO_URL;
    }

    /**
     * Get Rakuten Checkout start URL to redirect user to from the shopping cart
     *
     * @return string
     */
    public function getCheckoutStartUrl()
    {
        return Mage::getUrl(self::ROCKOUT_START_URL);
    }

    /**
     * Get API callback URL on Magento side
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return Mage::getUrl(self::ROCKBACK_URL, array('_secure' => true));
    }

    /**
     * Get Pipe Source URL for Inline integration method
     * (to avoid cross-domain iframe resize restrictions)
     *
     * @return string
     */
    public function getRakutenPipeUrl()
    {
        return self::RAKUTEN_PIPE_URL;
    }

    /**
     * Get Pipe URL for Inline integration method
     * (to avoid cross-domain iframe resize restrictions)
     *
     * @return string
     */
    public function getPipeUrl()
    {
        return Mage::getUrl(self::PIPE_URL);
    }

    /**
     * Get API request URL on Rakuten Checkout side
     * Get either Live or Sandbox Rockin URL based on current configuration settings
     *
     * @return string
     */
    public function getRockinUrl($urlType = 'default')
    {
        if ($this->isSandbox()) {
            switch ($urlType) {
                case 'shipment':
                    return self::ROCKIN_SHIPMENT_SANDBOX_URL;
                default:
                    return self::ROCKIN_SANDBOX_URL;
            }
        } else {
            switch ($urlType) {
                case 'shipment':
                    return self::ROCKIN_SHIPMENT_LIVE_URL;
                default:
                    return self::ROCKIN_LIVE_URL;
            }
        }
    }
}
