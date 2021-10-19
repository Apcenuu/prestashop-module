<?php
/**
 * MIT License
 *
 * Copyright (c) 2020 DIGITAL RETAIL TECHNOLOGIES SL
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    DIGITAL RETAIL TECHNOLOGIES SL <mail@simlachat.com>
 *  @copyright 2020 DIGITAL RETAIL TECHNOLOGIES SL
 *  @license   https://opensource.org/licenses/MIT  The MIT License
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */
class RetailcrmConsultantModuleFrontController extends ModuleFrontController
{
    /**
     * Universal render function for 1.6 and 1.7.
     *
     * @param string $response
     */
    private function renderData($response)
    {
        if (property_exists($this, 'ajax')) {
            $this->ajax = true;
        }

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo $response;
    }

    /**
     * {@inheritDoc}
     */
    public function initContent()
    {
        $this->renderData(json_encode($this->getData()));
    }

    /**
     * PrestaShop 1.6 compatibility
     */
    public function run()
    {
        $this->initContent();
    }

    /**
     * Responds RCCT with site
     */
    protected function getData()
    {
        $rcctExtractor = new RetailcrmCachedSettingExtractor();
        $rcct = $rcctExtractor
            ->setCachedAndConfigKey(RetailCRM::CONSULTANT_RCCT)
            ->getData();

        if (empty($rcct)) {
            $script = trim(Configuration::get(RetailCRM::CONSULTANT_SCRIPT));

            if (!empty($script)) {
                $rcctBuilder = new RetailcrmConsultantRcctExtractor();
                $rcct = $rcctBuilder->setConsultantScript($script)->build()->getDataString();

                if (!empty($rcct)) {
                    Cache::getInstance()->set(RetailCRM::CONSULTANT_RCCT, $rcct);
                }
            }
        }

        return ['rcct' => empty($rcct) ? '' : $rcct];
    }
}
