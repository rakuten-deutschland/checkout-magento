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
 * ROPE - R[akuten] O[rder] P[roc]E[ssing] - Controller for all supported Rakuten callback requests
 */
class Rakuten_Checkout_RopeController extends Mage_Core_Controller_Front_Action
{
    /**
     * Init ROPE model and pass ROPE request to it
     *
     * @return void
     */
    public function indexAction()
    {
        /** @var $ropeModel Rakuten_Checkout_Model_Rope */
        $ropeModel = Mage::getModel('rakuten/rope');

        // If request isn't POSTed show 404
        if (!$this->getRequest()->isPost()) {
            echo $ropeModel->prepareResponse(false);
            return;
        }

        $request = $this->getRequest()->getRawBody();

        // If request has no RawBody show 404
        if (!$request) {
            echo $ropeModel->prepareResponse(false);
            return;
        }

        try {
            // Process ROPE request and output response
            echo $ropeModel->processRopeRequest($request);
        } catch (Exception $e) {
            // Log exception and show 404
            Mage::logException($e);
            $this->_forward('404');
        }
    }

    /**
     * Pipe action to read pipe script contents
     * (to avoid cross-domain iframe resize restrictions)
     *
     * @return void
     */
    public function pipeAction()
    {
        $cacheId = 'RAKUTEN_CHECKOUT_PIPE';

        $pipe = Mage::app()->loadCache($cacheId);

        if (!$pipe) {
            $cacheLifetime = 21600; // 6 hrs

            /** @var $config Rakuten_Checkout_Model_Config */
            $config = Mage::getModel('rakuten/config');
            $pipeUrl = $config->getRakutenPipeUrl();

            $pipe = file_get_contents($pipeUrl);

            Mage::app()->saveCache($pipe, $cacheId, array(), $cacheLifetime);
        }

        echo $pipe;
    }
}