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

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

// Order attribute to mark order as paid via Rakuten to disable all actions in the admin
$installer->addAttribute('order', 'tradoria_order', array('type'=>'int', 'visible' => false, 'default' => 0));

// Upgrade from 1.0.2 version of the module if Tradoria Checkout was previously installed
$installer->getConnection()->changeColumn($installer->getTable('sales_flat_order'), 'tradoria_order', 'rakuten_order', "INT(11) DEFAULT '0' COMMENT 'Rakuten Order'");

$installer->run("
    UPDATE `{$installer->getTable('sales_flat_order_payment')}`
        SET `method`='rakuten' WHERE `method`='tradoria';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='payment/rakuten/active' WHERE `path`='payment/tradoria/active';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='payment/rakuten/debug' WHERE `path`='payment/tradoria/debug';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/general/enabled' WHERE `path`='tradoria/general/enabled';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/general/method' WHERE `path`='tradoria/general/method';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/general/shipping_rates' WHERE `path`='tradoria/general/shipping_rates';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/general/sandbox' WHERE `path`='tradoria/general/sandbox';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/auth/project_id' WHERE `path`='tradoria/auth/project_id';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/auth/api_key' WHERE `path`='tradoria/auth/api_key';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/misc/billing_addr_restrictions' WHERE `path`='tradoria/misc/billing_addr_restrictions';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/misc/allowspecific' WHERE `path`='tradoria/misc/allowspecific';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/misc/specificcountry' WHERE `path`='tradoria/misc/specificcountry';

    UPDATE `{$installer->getTable('core_config_data')}`
        SET `path`='rakuten/misc/turn_off_coupons' WHERE `path`='tradoria/misc/turn_off_coupons';
");

Mage::app()->cleanCache(Mage_Core_Model_Config::CACHE_TAG);