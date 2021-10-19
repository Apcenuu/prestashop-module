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
 * @author    DIGITAL RETAIL TECHNOLOGIES SL <mail@simlachat.com>
 * @copyright 2020 DIGITAL RETAIL TECHNOLOGIES SL
 * @license   https://opensource.org/licenses/MIT  The MIT License
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */
require_once dirname(__FILE__) . '/../RetailcrmPrestashopLoader.php';

abstract class RetailcrmAbstractEvent implements RetailcrmEventInterface
{
    private $cliMode;
    private $force;
    private $shopId;

    /**
     * {@inheritDoc}
     */
    abstract public function execute();

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        throw new InvalidArgumentException('Not implemented.');
    }

    /**
     * Sets cli mode to true. CLI mode here stands for any execution outside of JobManager context.
     *
     * @param bool $mode
     */
    public function setCliMode($mode)
    {
        $this->cliMode = (bool) $mode;
    }

    /**
     * @param mixed $force
     */
    public function setForce($force)
    {
        $this->force = (bool) $force;
    }

    /**
     * Sets context shop id.
     *
     * @param string|int|null $shopId
     */
    public function setShopId($shopId = null)
    {
        if (!is_null($shopId)) {
            $this->shopId = intval($shopId);
        }
    }

    /**
     * Returns true if current job is running now
     *
     * @return bool
     */
    protected function isRunning()
    {
        return !$this->force && ('' !== RetailcrmJobManager::getCurrentJob() || '' !== RetailcrmCli::getCurrentJob());
    }

    /**
     * Sets current job as active based on execution context.
     *
     * @return bool
     */
    protected function setRunning()
    {
        if ($this->force) {
            return true;
        }

        if ($this->cliMode) {
            return RetailcrmCli::setCurrentJob($this->getName());
        }

        return RetailcrmJobManager::setCurrentJob($this->getName());
    }

    /**
     * Returns array of active shops or false.
     *
     * @return array|false
     */
    protected function getShops()
    {
        $shops = Shop::getShops();

        if (Shop::isFeatureActive()) {
            if ($this->shopId > 0) {
                if (isset($shops[$this->shopId])) {
                    RetailcrmLogger::writeDebug(
                        __METHOD__,
                        sprintf(
                            'Running job for shop %s (%s).',
                            $shops[$this->shopId]['name'],
                            $this->shopId
                        )
                    );

                    return [$shops[$this->shopId]];
                } else {
                    RetailcrmLogger::writeDebug(
                        __METHOD__,
                        sprintf(
                            'Shop with id=%s not found.',
                            $this->shopId
                        )
                    );

                    return [];
                }
            }

            return $shops;
        } else {
            return [$shops[Shop::getContextShopID()]];
        }
    }
}
