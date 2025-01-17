<?php
/**
 * MIT License
 *
 * Copyright (c) 2021 DIGITAL RETAIL TECHNOLOGIES SL
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
 *  @copyright 2021 DIGITAL RETAIL TECHNOLOGIES SL
 *  @license   https://opensource.org/licenses/MIT  The MIT License
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
    date_default_timezone_set(@date_default_timezone_get());
}

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/bootstrap.php';

class RetailCRM extends Module
{
    const API_URL = 'RETAILCRM_ADDRESS';
    const API_KEY = 'RETAILCRM_API_TOKEN';
    const DELIVERY = 'RETAILCRM_API_DELIVERY';
    const STATUS = 'RETAILCRM_API_STATUS';
    const OUT_OF_STOCK_STATUS = 'RETAILCRM_API_OUT_OF_STOCK_STATUS';
    const PAYMENT = 'RETAILCRM_API_PAYMENT';
    const DELIVERY_DEFAULT = 'RETAILCRM_API_DELIVERY_DEFAULT';
    const PAYMENT_DEFAULT = 'RETAILCRM_API_PAYMENT_DEFAULT';
    const STATUS_EXPORT = 'RETAILCRM_STATUS_EXPORT';
    const CLIENT_ID = 'RETAILCRM_CLIENT_ID';
    const COLLECTOR_ACTIVE = 'RETAILCRM_DAEMON_COLLECTOR_ACTIVE';
    const COLLECTOR_KEY = 'RETAILCRM_DAEMON_COLLECTOR_KEY';
    const SYNC_CARTS_ACTIVE = 'RETAILCRM_API_SYNCHRONIZE_CARTS';
    const SYNC_CARTS_STATUS = 'RETAILCRM_API_SYNCHRONIZED_CART_STATUS';
    const SYNC_CARTS_DELAY = 'RETAILCRM_API_SYNCHRONIZED_CART_DELAY';
    const UPLOAD_ORDERS = 'RETAILCRM_UPLOAD_ORDERS_ID';
    const RUN_JOB = 'RETAILCRM_RUN_JOB';
    const EXPORT_ORDERS = 'RETAILCRM_EXPORT_ORDERS_STEP';
    const EXPORT_CUSTOMERS = 'RETAILCRM_EXPORT_CUSTOMERS_STEP';
    const UPDATE_SINCE_ID = 'RETAILCRM_UPDATE_SINCE_ID';
    const DOWNLOAD_LOGS_NAME = 'RETAILCRM_DOWNLOAD_LOGS_NAME';
    const DOWNLOAD_LOGS = 'RETAILCRM_DOWNLOAD_LOGS';
    const MODULE_LIST_CACHE_CHECKSUM = 'RETAILCRM_MODULE_LIST_CACHE_CHECKSUM';
    const ENABLE_CORPORATE_CLIENTS = 'RETAILCRM_ENABLE_CORPORATE_CLIENTS';
    const ENABLE_HISTORY_UPLOADS = 'RETAILCRM_ENABLE_HISTORY_UPLOADS';
    const ENABLE_BALANCES_RECEIVING = 'RETAILCRM_ENABLE_BALANCES_RECEIVING';
    const ENABLE_ORDER_NUMBER_SENDING = 'RETAILCRM_ENABLE_ORDER_NUMBER_SENDING';
    const ENABLE_ORDER_NUMBER_RECEIVING = 'RETAILCRM_ENABLE_ORDER_NUMBER_RECEIVING';
    const ENABLE_DEBUG_MODE = 'RETAILCRM_ENABLE_DEBUG_MODE';

    const LATEST_API_VERSION = '5';
    const CONSULTANT_SCRIPT = 'RETAILCRM_CONSULTANT_SCRIPT';
    const CONSULTANT_RCCT = 'RETAILCRM_CONSULTANT_RCCT';
    const ENABLE_WEB_JOBS = 'RETAILCRM_ENABLE_WEB_JOBS';
    const RESET_JOBS = 'RETAILCRM_RESET_JOBS';
    const JOBS_NAMES = [
        'RetailcrmAbandonedCartsEvent' => 'Abandoned Carts',
        'RetailcrmIcmlEvent' => 'Icml generation',
        'RetailcrmIcmlUpdateUrlEvent' => 'Icml update URL',
        'RetailcrmSyncEvent' => 'History synchronization',
        'RetailcrmInventoriesEvent' => 'Inventories uploads',
        'RetailcrmClearLogsEvent' => 'Clearing logs',
    ];

    const TABS_TO_VALIDATE = [
        'delivery' => self::DELIVERY,
        'statuses' => self::STATUS,
        'payment' => self::PAYMENT,
        'deliveryDefault' => self::DELIVERY_DEFAULT,
        'paymentDefault' => self::PAYMENT_DEFAULT,
    ];

    // todo dynamically define controller classes
    const ADMIN_CONTROLLERS = [
        RetailcrmSettingsController::class,
        RetailcrmOrdersController::class,
        RetailcrmOrdersUploadController::class,
    ];

    /**
     * @var array
     */
    private $templateErrors;

    /**
     * @var array
     */
    private $templateWarnings;

    /**
     * @var array
     */
    private $templateConfirms;

    /**
     * @var array
     */
    private $templateInfos;

    /** @var bool|\RetailcrmApiClientV5 */
    public $api = false;
    public $default_lang;
    public $default_currency;
    public $default_country;
    public $apiUrl;
    public $apiKey;
    public $psVersion;
    public $log;
    public $confirmUninstall;

    /**
     * @var \RetailcrmReferences
     */
    public $reference;
    public $assetsBase;
    private static $moduleListCache;

    private $use_new_hooks = true;

    public function __construct()
    {
        $this->name = 'retailcrm';
        $this->tab = 'export';
        $this->version = '3.3.5';
        $this->author = 'DIGITAL RETAIL TECHNOLOGIES SL';
        $this->displayName = $this->l('Simla.com');
        $this->description = $this->l('Integration module for Simla.com');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->default_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $this->default_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        $this->apiUrl = Configuration::get(static::API_URL);
        $this->apiKey = Configuration::get(static::API_KEY);
        $this->ps_versions_compliancy = ['min' => '1.6.1.0', 'max' => _PS_VERSION_];
        $this->psVersion = Tools::substr(_PS_VERSION_, 0, 3);
        $this->log = RetailcrmLogger::getLogFile();
        $this->module_key = 'dff3095326546f5fe8995d9e86288491';
        $this->assetsBase =
            Tools::getShopDomainSsl(true, true) .
            __PS_BASE_URI__ .
            'modules/' .
            $this->name .
            '/views';

        if ('1.6' == $this->psVersion) {
            $this->bootstrap = true;
            $this->use_new_hooks = false;
        }

        if ($this->apiUrl && $this->apiKey) {
            $this->api = new RetailcrmProxy($this->apiUrl, $this->apiKey, $this->log);
            $this->reference = new RetailcrmReferences($this->api);
        }

        parent::__construct();
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return
            parent::install()
            && $this->registerHook('newOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('actionPaymentConfirmation')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('actionOrderEdited')
            && $this->registerHook('actionCarrierUpdate')
            && $this->registerHook('header')
            && ($this->use_new_hooks ? $this->registerHook('actionCustomerAccountUpdate') : true)
            && ($this->use_new_hooks ? $this->registerHook('actionValidateCustomerAddressForm') : true)
            && $this->installDB()
            && $this->installTab()
            ;
    }

    /**
     * Installs the tab for the admin controller
     *
     * @return bool
     */
    public function installTab()
    {
        /** @var RetailcrmAdminAbstractController $controller */
        foreach (self::ADMIN_CONTROLLERS as $controller) {
            $tab = new Tab();
            $tab->id = $controller::getId();
            $tab->id_parent = $controller::getParentId();
            $tab->class_name = $controller::getClassName();
            $tab->name = $controller::getName();
            $tab->icon = $controller::getIcon();
            $tab->position = $controller::getPosition();
            $tab->active = 1;
            $tab->module = $this->name;

            if (!$tab->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallTab()
    {
        /** @var RetailcrmAdminAbstractController $controller */
        foreach (self::ADMIN_CONTROLLERS as $controller) {
            $tabId = $controller::getId();
            if (!$tabId) {
                continue;
            }

            $tab = new Tab($tabId);

            if (!$tab->delete()) {
                return false;
            }
        }

        return true;
    }

    public function hookHeader()
    {
        if (!empty($this->context) && !empty($this->context->controller)) {
            $this->context->controller->addJS($this->assetsBase . '/js/retailcrm-compat.min.js');
            $this->context->controller->addJS($this->assetsBase . '/js/retailcrm-jobs.min.js');
            $this->context->controller->addJS($this->assetsBase . '/js/retailcrm-collector.min.js');
            $this->context->controller->addJS($this->assetsBase . '/js/retailcrm-consultant.min.js');
        }
    }

    public function uninstall()
    {
        $apiUrl = Configuration::get(static::API_URL);
        $apiKey = Configuration::get(static::API_KEY);

        if (!empty($apiUrl) && !empty($apiKey)) {
            $api = new RetailcrmProxy(
                $apiUrl,
                $apiKey,
                RetailcrmLogger::getLogFile()
            );

            $clientId = Configuration::get(static::CLIENT_ID);
            $this->integrationModule($api, $clientId, false);
        }

        return parent::uninstall()
            && Configuration::deleteByName(static::API_URL)
            && Configuration::deleteByName(static::API_KEY)
            && Configuration::deleteByName(static::DELIVERY)
            && Configuration::deleteByName(static::STATUS)
            && Configuration::deleteByName(static::OUT_OF_STOCK_STATUS)
            && Configuration::deleteByName(static::PAYMENT)
            && Configuration::deleteByName(static::DELIVERY_DEFAULT)
            && Configuration::deleteByName(static::PAYMENT_DEFAULT)
            && Configuration::deleteByName(static::STATUS_EXPORT)
            && Configuration::deleteByName(static::CLIENT_ID)
            && Configuration::deleteByName(static::COLLECTOR_ACTIVE)
            && Configuration::deleteByName(static::COLLECTOR_KEY)
            && Configuration::deleteByName(static::SYNC_CARTS_ACTIVE)
            && Configuration::deleteByName(static::SYNC_CARTS_STATUS)
            && Configuration::deleteByName(static::SYNC_CARTS_DELAY)
            && Configuration::deleteByName(static::UPLOAD_ORDERS)
            && Configuration::deleteByName(static::MODULE_LIST_CACHE_CHECKSUM)
            && Configuration::deleteByName(static::ENABLE_CORPORATE_CLIENTS)
            && Configuration::deleteByName(static::ENABLE_HISTORY_UPLOADS)
            && Configuration::deleteByName(static::ENABLE_BALANCES_RECEIVING)
            && Configuration::deleteByName(static::ENABLE_ORDER_NUMBER_SENDING)
            && Configuration::deleteByName(static::ENABLE_ORDER_NUMBER_RECEIVING)
            && Configuration::deleteByName(static::ENABLE_DEBUG_MODE)
            && Configuration::deleteByName(static::ENABLE_WEB_JOBS)
            && Configuration::deleteByName('RETAILCRM_LAST_SYNC')
            && Configuration::deleteByName('RETAILCRM_LAST_ORDERS_SYNC')
            && Configuration::deleteByName('RETAILCRM_LAST_CUSTOMERS_SYNC')
            && Configuration::deleteByName(RetailcrmJobManager::LAST_RUN_NAME)
            && Configuration::deleteByName(RetailcrmJobManager::LAST_RUN_DETAIL_NAME)
            && Configuration::deleteByName(RetailcrmCatalogHelper::ICML_INFO_NAME)
            && Configuration::deleteByName(RetailcrmJobManager::IN_PROGRESS_NAME)
            && Configuration::deleteByName(RetailcrmJobManager::CURRENT_TASK)
            && Configuration::deleteByName(RetailcrmCli::CURRENT_TASK_CLI)
            && $this->uninstallDB()
            && $this->uninstallTab()
            ;
    }

    public function enable($force_all = false)
    {
        return parent::enable($force_all)
            && $this->installTab()
            ;
    }

    public function disable($force_all = false)
    {
        return parent::disable($force_all)
            && $this->uninstallTab()
            ;
    }

    public function installDB()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'retailcrm_abandonedcarts` (
                    `id_cart` INT UNSIGNED UNIQUE NOT NULL,
                    `last_uploaded` DATETIME,
                    FOREIGN KEY (id_cart) REFERENCES ' . _DB_PREFIX_ . 'cart (id_cart)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) DEFAULT CHARSET=utf8;
                CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'retailcrm_exported_orders` (
                    `id_order` INT UNSIGNED UNIQUE NULL,
                    `id_order_crm` INT UNSIGNED UNIQUE NULL,
                    `errors` TEXT NULL,
                    `last_uploaded` DATETIME,
                    FOREIGN KEY (id_order) REFERENCES ' . _DB_PREFIX_ . 'orders (id_order)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) DEFAULT CHARSET=utf8;'
        );
    }

    public function uninstallDB()
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'retailcrm_abandonedcarts`;
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'retailcrm_exported_orders`;'
        );
    }

    /**
     * Remove files that was deleted\moved\renamed in a newer version and currently are outdated
     *
     * @param array $files File paths relative to the `modules/` directory
     *
     * @return bool
     */
    public function removeOldFiles($files)
    {
        foreach ($files as $file) {
            try {
                if (0 !== strpos($file, 'retailcrm/')) {
                    continue;
                }

                $relativePath = str_replace('retailcrm/', '', $file);
                $fullPath = sprintf(
                    '%s/%s', __DIR__, $relativePath
                );

                if (!file_exists($fullPath)) {
                    continue;
                }

                RetailcrmLogger::writeCaller(
                    __METHOD__, sprintf('Remove `%s`', $file)
                );

                unlink($fullPath); // todo maybe check and remove empty directories
            } catch (Exception $e) {
                RetailcrmLogger::writeCaller(
                    __METHOD__,
                    sprintf('Error removing `%s`: %s', $file, $e->getMessage())
                );
            } catch (Error $e) {
                RetailcrmLogger::writeCaller(
                    __METHOD__,
                    sprintf('Error removing `%s`: %s', $file, $e->getMessage())
                );
            }
        }

        return true;
    }

    public function getContent()
    {
        $output = null;
        $address = Configuration::get(static::API_URL);
        $token = Configuration::get(static::API_KEY);

        if (Tools::isSubmit('submit' . $this->name)) {
            // todo all those vars & ifs to one $command var and check in switch
            $jobName = (string) (Tools::getValue(static::RUN_JOB));
            $ordersIds = (string) (Tools::getValue(static::UPLOAD_ORDERS));
            $exportOrders = (int) (Tools::getValue(static::EXPORT_ORDERS));
            $exportCustomers = (int) (Tools::getValue(static::EXPORT_CUSTOMERS));
            $updateSinceId = (bool) (Tools::getValue(static::UPDATE_SINCE_ID));
            $downloadLogs = (bool) (Tools::getValue(static::DOWNLOAD_LOGS));
            $resetJobs = (bool) (Tools::getValue(static::RESET_JOBS));

            if (!empty($ordersIds)) {
                $output .= $this->uploadOrders(RetailcrmTools::partitionId($ordersIds));
            } elseif (!empty($jobName)) {
                $this->runJobMultistore($jobName);
            } elseif (!empty($exportOrders)) {
                return $this->export($exportOrders);
            } elseif (!empty($exportCustomers)) {
                return $this->export($exportCustomers, 'customer');
            } elseif ($updateSinceId) {
                return $this->updateSinceId();
            } elseif ($downloadLogs) {
                return $this->downloadLogs();
            } elseif ($resetJobs) {
                return $this->resetJobs();
            } else {
                $output .= $this->saveSettings();
            }
        }

        if ($address && $token) {
            $this->api = new RetailcrmProxy($address, $token, $this->log);
            $this->reference = new RetailcrmReferences($this->api);
        }

        $templateFactory = new RetailcrmTemplateFactory($this->context->smarty, $this->assetsBase);

        return $templateFactory
            ->createTemplate($this)
            ->setContext($this->context)
            ->setErrors($this->getErrorMessages())
            ->setWarnings($this->getWarningMessage())
            ->setInformations($this->getInformationMessages())
            ->setConfirmations($this->getConfirmationMessages())
            ->render(__FILE__)
        ;
    }

    public function uploadOrders($orderIds)
    {
        if (10 < count($orderIds)) {
            return $this->displayError($this->l("Can't upload more than 10 orders per request"));
        }

        if (1 > count($orderIds)) {
            return $this->displayError($this->l('At least one order ID should be specified'));
        }

        if (!($this->api instanceof RetailcrmProxy)) {
            $this->api = RetailcrmTools::getApiClient();

            if (!($this->api instanceof RetailcrmProxy)) {
                return $this->displayError($this->l("Can't upload orders - set API key and API URL first!"));
            }
        }

        $result = '';
        $isSuccessful = true;
        $skippedOrders = [];
        RetailcrmExport::$api = $this->api;

        foreach ($orderIds as $orderId) {
            $response = false;

            try {
                $response = RetailcrmExport::exportOrder($orderId);
            } catch (PrestaShopObjectNotFoundExceptionCore $e) {
                $skippedOrders[] = $orderId;
            } catch (Exception $e) {
                $this->displayError($e->getMessage());
                RetailcrmLogger::writeCaller(__METHOD__, $e->getTraceAsString());
            } catch (Error $e) {
                $this->displayError($e->getMessage());
                RetailcrmLogger::writeCaller(__METHOD__, $e->getTraceAsString());
            }

            $isSuccessful = $isSuccessful ? $response : false;
            time_nanosleep(0, 50000000);
        }

        if ($isSuccessful && empty($skippedOrders)) {
            return $this->displayConfirmation($this->l('All orders were uploaded successfully'));
        } else {
            $result .= $this->displayWarning($this->l('Not all orders were uploaded successfully'));

            if ($errors = RetailcrmApiErrors::getErrors()) {
                foreach ($errors as $error) {
                    $result .= $this->displayError($error);
                }
            }

            if (!empty($skippedOrders)) {
                $result .= $this->displayWarning(sprintf(
                    $this->l('Orders skipped due to non-existence: %s', 'retailcrm'),
                    implode(', ', $skippedOrders)
                ));
            }

            return $result;
        }
    }

    /**
     * @param string $jobName
     *
     * @return string
     */
    public function runJob($jobName)
    {
        $jobNameFront = (empty(static::JOBS_NAMES[$jobName]) ? $jobName : static::JOBS_NAMES[$jobName]);

        try {
            if (RetailcrmJobManager::execManualJob($jobName)) {
                return $this->displayConfirmation(sprintf(
                    '%s %s',
                    $this->l($jobNameFront),
                    $this->l('was completed successfully')
                ));
            } else {
                return $this->displayError(sprintf(
                    '%s %s',
                    $this->l($jobNameFront),
                    $this->l('was not executed')
                ));
            }
        } catch (Exception $e) {
            return $this->displayError(sprintf(
                '%s %s: %s',
                $this->l($jobNameFront),
                $this->l('was completed with errors'),
                $e->getMessage()
            ));
        } catch (Error $e) {
            return $this->displayError(sprintf(
                '%s %s: %s',
                $this->l($jobNameFront),
                $this->l('was completed with errors'),
                $e->getMessage()
            ));
        }
    }

    public function runJobMultistore($jobName)
    {
        RetailcrmContextSwitcher::runInContext([$this, 'runJob'], [$jobName]);
    }

    /**
     * @param int $step
     * @param string $entity
     *
     * @return bool
     */
    public function export($step, $entity = 'order')
    {
        if (!Tools::getValue('ajax')) {
            return RetailcrmJsonResponse::invalidResponse('This method allow only in ajax mode');
        }

        --$step;
        if (0 > $step) {
            return RetailcrmJsonResponse::invalidResponse('Invalid request data');
        }

        $api = RetailcrmTools::getApiClient();

        if (empty($api)) {
            return RetailcrmJsonResponse::invalidResponse('Set API key & URL first');
        }

        RetailcrmExport::init();
        RetailcrmExport::$api = $api;

        if ('order' === $entity) {
            $stepSize = RetailcrmExport::RETAILCRM_EXPORT_ORDERS_STEP_SIZE_WEB;

            RetailcrmExport::$ordersOffset = $stepSize;
            RetailcrmExport::exportOrders($step * $stepSize, $stepSize);
        // todo maybe save current step to database
        } elseif ('customer' === $entity) {
            $stepSize = RetailcrmExport::RETAILCRM_EXPORT_CUSTOMERS_STEP_SIZE_WEB;

            RetailcrmExport::$customersOffset = $stepSize;
            RetailcrmExport::exportCustomers($step * $stepSize, $stepSize);
            // todo maybe save current step to database
        }

        return RetailcrmJsonResponse::successfullResponse();
    }

    public function updateSinceId()
    {
        if (!Tools::getValue('ajax')) {
            return RetailcrmJsonResponse::invalidResponse('This method allow only in ajax mode');
        }

        $api = RetailcrmTools::getApiClient();

        if (empty($api)) {
            return RetailcrmJsonResponse::invalidResponse('Set API key & URL first');
        }

        RetailcrmHistory::$api = $api;
        RetailcrmHistory::updateSinceId('customers');
        RetailcrmHistory::updateSinceId('orders');

        return RetailcrmJsonResponse::successfullResponse();
    }

    public function downloadLogs()
    {
        if (!Tools::getValue('ajax')) {
            return false;
        }

        $name = (string) (Tools::getValue(static::DOWNLOAD_LOGS_NAME));
        if (!empty($name)) {
            if (false === ($filePath = RetailcrmLogger::checkFileName($name))) {
                return false;
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
        } else {
            $zipname = _PS_DOWNLOAD_DIR_ . '/retailcrm_logs_' . date('Y-m-d H-i-s') . '.zip';

            $zipFile = new ZipArchive();
            $zipFile->open($zipname, ZIPARCHIVE::CREATE);

            foreach (RetailcrmLogger::getLogFilesInfo() as $logFile) {
                $zipFile->addFile($logFile['path'], $logFile['name']);
            }

            $zipFile->close();

            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename=' . basename($zipname));
            header('Content-Length: ' . filesize($zipname));
            readfile($zipname);
            unlink($zipname);
        }

        return true;
    }

    /**
     * Resets JobManager and cli internal lock
     */
    public function resetJobs()
    {
        $errors = [];
        try {
            if (!RetailcrmJobManager::reset()) {
                $errors[] = 'Job manager internal state was NOT cleared.';
            }
            if (!RetailcrmCli::clearCurrentJob(null)) {
                $errors[] = 'CLI job was NOT cleared';
            }

            if (!empty($errors)) {
                return RetailcrmJsonResponse::invalidResponse(implode(' ', $errors));
            }

            return RetailcrmJsonResponse::successfullResponse();
        } catch (Exception $exception) {
            return RetailcrmJsonResponse::invalidResponse($exception->getMessage());
        } catch (Error $exception) {
            return RetailcrmJsonResponse::invalidResponse($exception->getMessage());
        }
    }

    public function hookActionCustomerAccountAdd($params)
    {
        if ($this->api) {
            $customer = $params['newCustomer'];
            $customerSend = RetailcrmOrderBuilder::buildCrmCustomer($customer);

            $this->api->customersCreate($customerSend);

            return true;
        }

        return false;
    }

    // this hook added in 1.7
    public function hookActionCustomerAccountUpdate($params)
    {
        if ($this->api) {
            /** @var Customer|CustomerCore|null $customer */
            $customer = isset($params['customer']) ? $params['customer'] : null;

            if (empty($customer)) {
                return false;
            }

            /** @var Cart|CartCore|null $cart */
            $cart = isset($params['cart']) ? $params['cart'] : null;

            /** @var array $customerSend */
            $customerSend = RetailcrmOrderBuilder::buildCrmCustomer($customer);

            /** @var \RetailcrmAddressBuilder $addressBuilder */
            $addressBuilder = new RetailcrmAddressBuilder();

            /** @var Address|\AddressCore|array $address */
            $address = [];

            if (isset($customerSend['externalId'])) {
                $customerData = $this->api->customersGet($customerSend['externalId']);

                // Necessary part if we don't want to overwrite other phone numbers.
                if ($customerData instanceof RetailcrmApiResponse
                    && $customerData->isSuccessful()
                    && $customerData->offsetExists('customer')
                ) {
                    $customerSend['phones'] = $customerData['customer']['phones'];
                }

                // Workaround: PrestaShop will return OLD address data, before editing.
                // In order to circumvent this we are using post data to fill new address object.
                if (Tools::getIsset('submitAddress')
                    && Tools::getIsset('id_customer')
                    && Tools::getIsset('id_address')
                ) {
                    $address = new Address(Tools::getValue('id_address'));

                    foreach (array_keys(Address::$definition['fields']) as $field) {
                        if (property_exists($address, $field) && Tools::getIsset($field)) {
                            $address->$field = Tools::getValue($field);
                        }
                    }
                } else {
                    $addresses = $customer->getAddresses($this->default_lang);
                    $address = array_shift($addresses);
                }

                if (!empty($address)) {
                    $addressBuilder->setMode(RetailcrmAddressBuilder::MODE_CUSTOMER);

                    if (is_object($address)) {
                        $addressBuilder->setAddress($address);
                    } else {
                        $addressBuilder->setAddressId($address['id_address']);
                    }

                    $addressBuilder->build();
                } elseif (!empty($cart)) {
                    $addressBuilder
                        ->setMode(RetailcrmAddressBuilder::MODE_ORDER_DELIVERY)
                        ->setAddressId($cart->id_address_invoice)
                        ->build()
                    ;
                }

                $customerSend = RetailcrmTools::mergeCustomerAddress($customerSend, $addressBuilder->getDataArray());

                $this->api->customersEdit($customerSend);

                return true;
            }
        }

        return false;
    }

    // this hook added in 1.7
    public function hookActionValidateCustomerAddressForm($params)
    {
        $customer = new Customer($params['cart']->id_customer);
        $customerAddress = ['customer' => $customer, 'cart' => $params['cart']];

        return $this->hookActionCustomerAccountUpdate($customerAddress);
    }

    public function hookNewOrder($params)
    {
        return $this->hookActionOrderStatusPostUpdate($params);
    }

    public function hookActionPaymentConfirmation($params)
    {
        return $this->hookActionOrderStatusPostUpdate($params);
    }

    /**
     * This will ensure that our delivery mapping will not lose associations with edited deliveries.
     * PrestaShop doesn't actually edit delivery - it will hide it via `delete` flag in DB and create new one.
     * That's why we need to intercept this here and update delivery ID in mapping if necessary.
     *
     * @param array $params
     */
    public function hookActionCarrierUpdate($params)
    {
        if (!array_key_exists('id_carrier', $params) || !array_key_exists('carrier', $params)) {
            return;
        }

        /** @var Carrier|\CarrierCore $newCarrier */
        $newCarrier = $params['carrier'];
        $oldCarrierId = $params['id_carrier'];

        if (!($newCarrier instanceof Carrier) && !($newCarrier instanceof CarrierCore)) {
            return;
        }

        $delivery = json_decode(Configuration::get(RetailCRM::DELIVERY), true);
        $deliveryDefault = json_decode(Configuration::get(static::DELIVERY_DEFAULT), true);

        if ($oldCarrierId == $deliveryDefault) {
            Configuration::updateValue(static::DELIVERY_DEFAULT, json_encode($newCarrier->id));
        }

        if (is_array($delivery) && array_key_exists($oldCarrierId, $delivery)) {
            $delivery[$newCarrier->id] = $delivery[$oldCarrierId];
            unset($delivery[$oldCarrierId]);
            Configuration::updateValue(static::DELIVERY, json_encode($delivery));
        }
    }

    public function hookActionOrderEdited($params)
    {
        if (!$this->api) {
            return false;
        }

        try {
            RetailcrmExport::$api = $this->api;

            return RetailcrmExport::exportOrder($params['order']->id);
        } catch (Exception $e) {
            RetailcrmLogger::writeCaller(__METHOD__, $e->getMessage());
            RetailcrmLogger::writeNoCaller($e->getTraceAsString());
        } catch (Error $e) {
            RetailcrmLogger::writeCaller(__METHOD__, $e->getMessage());
            RetailcrmLogger::writeNoCaller($e->getTraceAsString());
        }

        return false;
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if (!$this->api) {
            return false;
        }

        $status = json_decode(Configuration::get(static::STATUS), true);

        if (isset($params['orderStatus'])) {
            try {
                RetailcrmExport::$api = $this->api;

                return RetailcrmExport::exportOrder($params['order']->id);
            } catch (Exception $e) {
                RetailcrmLogger::writeCaller(__METHOD__, $e->getMessage());
                RetailcrmLogger::writeNoCaller($e->getTraceAsString());
            } catch (Error $e) {
                RetailcrmLogger::writeCaller(__METHOD__, $e->getMessage());
                RetailcrmLogger::writeNoCaller($e->getTraceAsString());
            }

            return false;
        } elseif (isset($params['newOrderStatus'])) {
            $order = [
                'externalId' => $params['id_order'],
            ];

            $statusCode = $params['newOrderStatus']->id;

            if (array_key_exists($statusCode, $status) && !empty($status[$statusCode])) {
                $order['status'] = $status[$statusCode];
            }

            $order = RetailcrmTools::filter('RetailcrmFilterOrderStatusUpdate', $order, $params);

            if (isset($order['externalId']) && 1 < count($order)) {
                $this->api->ordersEdit($order);

                return true;
            }
        }

        return false;
    }

    public function hookActionPaymentCCAdd($params)
    {
        $payments = array_filter(json_decode(Configuration::get(static::PAYMENT), true));
        $paymentType = false;
        $externalId = false;

        foreach ($this->reference->getSystemPaymentModules() as $paymentCMS) {
            if ($paymentCMS['name'] === $params['paymentCC']->payment_method
                && array_key_exists($paymentCMS['code'], $payments)
                && !empty($payments[$paymentCMS['code']])
            ) {
                $paymentType = $payments[$paymentCMS['code']];
                break;
            }
        }

        if (!$paymentType || empty($params['cart']) || empty((int) $params['cart']->id)) {
            return false;
        }

        $response = $this->api->ordersGet(RetailcrmTools::getCartOrderExternalId($params['cart']));

        if (false !== $response && isset($response['order'])) {
            $externalId = RetailcrmTools::getCartOrderExternalId($params['cart']);
        } else {
            if (version_compare(_PS_VERSION_, '1.7.1.0', '>=')) {
                $id_order = (int) Order::getIdByCartId((int) $params['cart']->id);
            } else {
                $id_order = (int) Order::getOrderByCartId((int) $params['cart']->id);
            }

            if (0 < $id_order) {
                // do not update payment if the order in Cart and OrderPayment aren't the same
                if ($params['paymentCC']->order_reference) {
                    $order = Order::getByReference($params['paymentCC']->order_reference)->getFirst();
                    if (!$order || $order->id !== $id_order) {
                        return false;
                    }
                }

                $response = $this->api->ordersGet($id_order);
                if (false !== $response && isset($response['order'])) {
                    $externalId = $id_order;
                }
            }
        }

        if (false === $externalId) {
            return false;
        }

        $status = (0 < round($params['paymentCC']->amount, 2) ? 'paid' : null);
        $orderCRM = $response['order'];

        if ($orderCRM && $orderCRM['payments']) {
            foreach ($orderCRM['payments'] as $orderPayment) {
                if ($orderPayment['type'] === $paymentType) {
                    $updatePayment = $orderPayment;
                    $updatePayment['amount'] = $params['paymentCC']->amount;
                    $updatePayment['paidAt'] = $params['paymentCC']->date_add;
                    $updatePayment['status'] = $status;
                }
            }
        }

        if (isset($updatePayment)) {
            $this->api->ordersPaymentEdit($updatePayment, 'id');
        } else {
            $createPayment = [
                'externalId' => $params['paymentCC']->id,
                'amount' => $params['paymentCC']->amount,
                'paidAt' => $params['paymentCC']->date_add,
                'type' => $paymentType,
                'status' => $status,
                'order' => [
                    'externalId' => $externalId,
                ],
            ];

            $this->api->ordersPaymentCreate($createPayment);
        }

        return true;
    }

    /**
     * Save settings handler
     *
     * @return string
     */
    private function saveSettings()
    {
        $output = '';
        $url = (string) Tools::getValue(static::API_URL);
        $apiKey = (string) Tools::getValue(static::API_KEY);
        $consultantCode = (string) Tools::getValue(static::CONSULTANT_SCRIPT);

        if (!empty($url) && !empty($apiKey)) {
            $settings = [
                'url' => rtrim($url, '/'),
                'apiKey' => $apiKey,
                'address' => (string) (Tools::getValue(static::API_URL)),
                'delivery' => json_encode(Tools::getValue(static::DELIVERY)),
                'status' => json_encode(Tools::getValue(static::STATUS)),
                'outOfStockStatus' => json_encode(Tools::getValue(static::OUT_OF_STOCK_STATUS)),
                'payment' => json_encode(Tools::getValue(static::PAYMENT)),
                'deliveryDefault' => json_encode(Tools::getValue(static::DELIVERY_DEFAULT)),
                'paymentDefault' => json_encode(Tools::getValue(static::PAYMENT_DEFAULT)),
                'statusExport' => (string) (Tools::getValue(static::STATUS_EXPORT)),
                'enableCorporate' => (false !== Tools::getValue(static::ENABLE_CORPORATE_CLIENTS)),
                'enableHistoryUploads' => (false !== Tools::getValue(static::ENABLE_HISTORY_UPLOADS)),
                'enableBalancesReceiving' => (false !== Tools::getValue(static::ENABLE_BALANCES_RECEIVING)),
                'enableOrderNumberSending' => (false !== Tools::getValue(static::ENABLE_ORDER_NUMBER_SENDING)),
                'enableOrderNumberReceiving' => (false !== Tools::getValue(static::ENABLE_ORDER_NUMBER_RECEIVING)),
                'debugMode' => (false !== Tools::getValue(static::ENABLE_DEBUG_MODE)),
                'webJobs' => (false !== Tools::getValue(static::ENABLE_WEB_JOBS) ? '1' : '0'),
                'collectorActive' => (false !== Tools::getValue(static::COLLECTOR_ACTIVE)),
                'collectorKey' => (string) (Tools::getValue(static::COLLECTOR_KEY)),
                'clientId' => Configuration::get(static::CLIENT_ID),
                'synchronizeCartsActive' => (false !== Tools::getValue(static::SYNC_CARTS_ACTIVE)),
                'synchronizedCartStatus' => (string) (Tools::getValue(static::SYNC_CARTS_STATUS)),
                'synchronizedCartDelay' => (string) (Tools::getValue(static::SYNC_CARTS_DELAY)),
            ];

            $output .= $this->validateForm($settings, $output);

            if ('' === $output) {
                Configuration::updateValue(static::API_URL, $settings['url']);
                Configuration::updateValue(static::API_KEY, $settings['apiKey']);
                Configuration::updateValue(static::DELIVERY, $settings['delivery']);
                Configuration::updateValue(static::STATUS, $settings['status']);
                Configuration::updateValue(static::OUT_OF_STOCK_STATUS, $settings['outOfStockStatus']);
                Configuration::updateValue(static::PAYMENT, $settings['payment']);
                Configuration::updateValue(static::DELIVERY_DEFAULT, $settings['deliveryDefault']);
                Configuration::updateValue(static::PAYMENT_DEFAULT, $settings['paymentDefault']);
                Configuration::updateValue(static::STATUS_EXPORT, $settings['statusExport']);
                Configuration::updateValue(static::ENABLE_CORPORATE_CLIENTS, $settings['enableCorporate']);
                Configuration::updateValue(static::ENABLE_HISTORY_UPLOADS, $settings['enableHistoryUploads']);
                Configuration::updateValue(static::ENABLE_BALANCES_RECEIVING, $settings['enableBalancesReceiving']);
                Configuration::updateValue(static::ENABLE_ORDER_NUMBER_SENDING, $settings['enableOrderNumberSending']);
                Configuration::updateValue(
                    static::ENABLE_ORDER_NUMBER_RECEIVING,
                    $settings['enableOrderNumberReceiving']
                );
                Configuration::updateValue(static::COLLECTOR_ACTIVE, $settings['collectorActive']);
                Configuration::updateValue(static::COLLECTOR_KEY, $settings['collectorKey']);
                Configuration::updateValue(static::SYNC_CARTS_ACTIVE, $settings['synchronizeCartsActive']);
                Configuration::updateValue(static::SYNC_CARTS_STATUS, $settings['synchronizedCartStatus']);
                Configuration::updateValue(static::SYNC_CARTS_DELAY, $settings['synchronizedCartDelay']);
                Configuration::updateValue(static::ENABLE_DEBUG_MODE, $settings['debugMode']);
                Configuration::updateValue(static::ENABLE_WEB_JOBS, $settings['webJobs']);

                $this->apiUrl = $settings['url'];
                $this->apiKey = $settings['apiKey'];
                $this->api = new RetailcrmProxy($this->apiUrl, $this->apiKey, $this->log);
                $this->reference = new RetailcrmReferences($this->api);

                if (0 == $this->isRegisteredInHook('actionPaymentCCAdd')) {
                    $this->registerHook('actionPaymentCCAdd');
                }
            }
        }

        if (!empty($consultantCode)) {
            $extractor = new RetailcrmConsultantRcctExtractor();
            $rcct = $extractor->setConsultantScript($consultantCode)->build()->getDataString();

            if (!empty($rcct)) {
                Configuration::updateValue(static::CONSULTANT_SCRIPT, $consultantCode, true);
                Configuration::updateValue(static::CONSULTANT_RCCT, $rcct);
                Cache::getInstance()->set(static::CONSULTANT_RCCT, $rcct);
            } else {
                Configuration::deleteByName(static::CONSULTANT_SCRIPT);
                Configuration::deleteByName(static::CONSULTANT_RCCT);
                Cache::getInstance()->delete(static::CONSULTANT_RCCT);
            }
        }

        return $output;
    }

    /**
     * Activate/deactivate module in marketplace retailCRM
     *
     * @param \RetailcrmProxy $apiClient
     * @param string $clientId
     * @param bool $active
     *
     * @return bool
     */
    private function integrationModule($apiClient, $clientId, $active = true)
    {
        $scheme = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $logo = 'https://s3.eu-central-1.amazonaws.com/retailcrm-billing/images/5b845ce986911-prestashop2.svg';
        $integrationCode = 'prestashop';
        $name = 'PrestaShop';
        $accountUrl = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $configuration = [
            'clientId' => $clientId,
            'code' => $integrationCode . '-' . $clientId,
            'integrationCode' => $integrationCode,
            'active' => $active,
            'name' => $name,
            'logo' => $logo,
            'accountUrl' => $accountUrl,
        ];
        $response = $apiClient->integrationModulesEdit($configuration);

        if (!$response) {
            return false;
        }

        if ($response->isSuccessful()) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if provided connection supports API v5
     *
     * @param $settings
     *
     * @return bool
     */
    private function validateApiVersion($settings)
    {
        /** @var \RetailcrmProxy|\RetailcrmApiClientV5 $api */
        $api = new RetailcrmProxy(
            $settings['url'],
            $settings['apiKey'],
            $this->log
        );

        $response = $api->apiVersions();

        if (false !== $response && isset($response['versions']) && !empty($response['versions'])) {
            foreach ($response['versions'] as $version) {
                if ($version == static::LATEST_API_VERSION
                    || Tools::substr($version, 0, 1) == static::LATEST_API_VERSION
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Workaround to pass translate method into another classes
     *
     * @param $text
     *
     * @return mixed
     */
    public function translate($text)
    {
        return $this->l($text);
    }

    /**
     * Cart status must be present and must be unique to cartsIds only
     *
     * @param string $statuses
     * @param string $statusExport
     * @param string $cartStatus
     *
     * @return bool
     */
    private function validateCartStatus($statuses, $statusExport, $cartStatus)
    {
        if ('' != $cartStatus && ($cartStatus == $statusExport || stripos($statuses, $cartStatus))) {
            return false;
        }

        return true;
    }

    /**
     * Returns false if mapping is not valid in one-to-one relation
     *
     * @param string $statuses
     *
     * @return bool
     */
    private function validateMappingOneToOne($statuses)
    {
        $data = json_decode($statuses, true);

        if (JSON_ERROR_NONE != json_last_error() || !is_array($data)) {
            return true;
        }

        $statusesList = array_filter(array_values($data));

        if (count($statusesList) != count(array_unique($statusesList))) {
            return false;
        }

        return true;
    }

    public function validateStoredSettings()
    {
        $output = [];
        $checkApiMethods = [
            'delivery' => 'getApiDeliveryTypes',
            'statuses' => 'getApiStatuses',
            'payment' => 'getApiPaymentTypes',
        ];

        foreach (self::TABS_TO_VALIDATE as $tabName => $settingName) {
            $storedValues = Tools::getIsset($settingName)
                ? Tools::getValue($settingName)
                : json_decode(Configuration::get($settingName), true);

            if (false !== $storedValues && null !== $storedValues) {
                if (!$this->validateMappingSelected($storedValues)) {
                    $output[] = $tabName;
                } else {
                    if (array_key_exists($tabName, $checkApiMethods)) {
                        $crmValues = call_user_func([$this->reference, $checkApiMethods[$tabName]]);
                        $crmCodes = array_column($crmValues, 'id_option');

                        if (!empty(array_diff($storedValues, $crmCodes))) {
                            $output[] = $tabName;
                        }
                    }
                }
            }
        }

        if (!$this->validateCatalogMultistore()) {
            $output[] = 'catalog';
        }

        return $output;
    }

    private function validateMappingSelected($values)
    {
        if (is_array($values)) {
            foreach ($values as $item) {
                if (empty($item)) {
                    return false;
                }
            }
        } else {
            if (empty($values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Catalog info validator
     *
     * @return bool
     */
    public function validateCatalog()
    {
        $icmlInfo = RetailcrmCatalogHelper::getIcmlFileInfo();

        if (!$icmlInfo || !isset($icmlInfo['lastGenerated'])) {
            $urlConfiguredAt = RetailcrmTools::getConfigurationCreatedAtByName(self::API_KEY);

            if ($urlConfiguredAt instanceof DateTimeImmutable) {
                $now = new DateTimeImmutable();
                /** @var DateInterval $diff */
                $diff = $urlConfiguredAt->diff($now);

                if (($diff->days * 24 + $diff->h) > 4) {
                    return false;
                }
            }
        } elseif ($icmlInfo['isOutdated'] || !$icmlInfo['isUrlActual']) {
            return false;
        }

        return true;
    }

    /**
     * Catalog info validator for multistore
     *
     * @return bool
     */
    private function validateCatalogMultistore()
    {
        $results = RetailcrmContextSwitcher::runInContext([$this, 'validateCatalog']);
        $results = array_filter($results, function ($item) {
            return !$item;
        });

        return empty($results);
    }

    /**
     * Settings form validator
     *
     * @param $settings
     * @param $output
     *
     * @return string
     */
    private function validateForm($settings, $output)
    {
        if (!RetailcrmTools::validateCrmAddress($settings['url']) || !Validate::isGenericName($settings['url'])) {
            $output .= $this->displayError($this->l('Invalid or empty crm address'));
        } elseif (!$settings['apiKey'] || '' == $settings['apiKey']) {
            $output .= $this->displayError($this->l('Invalid or empty crm api token'));
        } elseif (!$this->validateApiVersion($settings)) {
            $output .= $this->displayError($this->l('The selected version of the API is unavailable'));
        } elseif (!$this->validateCartStatus(
            $settings['status'],
            $settings['statusExport'],
            $settings['synchronizedCartStatus']
        )) {
            $output .= $this->displayError(
                $this->l('Order status for abandoned carts should not be used in other settings')
            );
        } elseif (!$this->validateMappingOneToOne($settings['status'])) {
            $output .= $this->displayError(
                $this->l('Order statuses should not repeat in statuses matrix')
            );
        } elseif (!$this->validateMappingOneToOne($settings['delivery'])) {
            $output .= $this->displayError(
                $this->l('Delivery types should not repeat in delivery matrix')
            );
        } elseif (!$this->validateMappingOneToOne($settings['payment'])) {
            $output .= $this->displayError(
                $this->l('Payment types should not repeat in payment matrix')
            );
        }

        $errorTabs = $this->validateStoredSettings();

        if (in_array('delivery', $errorTabs)) {
            $this->displayWarning($this->l('Select values for all delivery types'));
        }
        if (in_array('statuses', $errorTabs)) {
            $this->displayWarning($this->l('Select values for all order statuses'));
        }
        if (in_array('payment', $errorTabs)) {
            $this->displayWarning($this->l('Select values for all payment types'));
        }
        if (in_array('deliveryDefault', $errorTabs) || in_array('paymentDefault', $errorTabs)) {
            $this->displayWarning($this->l('Select values for all default parameters'));
        }

        return $output;
    }

    /**
     * Loads data from modules list cache
     *
     * @return array|mixed
     */
    private static function requireModulesCache()
    {
        if (file_exists(static::getModulesCache())) {
            return require_once static::getModulesCache();
        }

        return false;
    }

    /**
     * Returns path to modules list cache
     *
     * @return string
     */
    private static function getModulesCache()
    {
        if (defined('_PS_CACHE_DIR_')) {
            return _PS_CACHE_DIR_ . '/retailcrm_modules_cache.php';
        }

        if (!defined('_PS_ROOT_DIR_')) {
            return '';
        }

        $cacheDir = _PS_ROOT_DIR_ . '/cache';

        if (false !== realpath($cacheDir) && is_dir($cacheDir)) {
            return $cacheDir . '/retailcrm_modules_cache.php';
        }

        return _PS_ROOT_DIR_ . '/retailcrm_modules_cache.php';
    }

    /**
     * Returns all module settings
     *
     * @return array
     */
    public static function getSettings()
    {
        $syncCartsDelay = (string) (Configuration::get(static::SYNC_CARTS_DELAY));

        // Use 15 minutes as default interval but don't change immediate interval to it if user already made decision
        if (empty($syncCartsDelay) && '0' !== $syncCartsDelay) {
            $syncCartsDelay = '900';
        }

        return [
            'url' => (string) (Configuration::get(static::API_URL)),
            'apiKey' => (string) (Configuration::get(static::API_KEY)),
            'delivery' => json_decode(Configuration::get(static::DELIVERY), true),
            'status' => json_decode(Configuration::get(static::STATUS), true),
            'outOfStockStatus' => json_decode(Configuration::get(static::OUT_OF_STOCK_STATUS), true),
            'payment' => json_decode(Configuration::get(static::PAYMENT), true),
            'deliveryDefault' => json_decode(Configuration::get(static::DELIVERY_DEFAULT), true),
            'paymentDefault' => json_decode(Configuration::get(static::PAYMENT_DEFAULT), true),
            'statusExport' => (string) (Configuration::get(static::STATUS_EXPORT)),
            'collectorActive' => (Configuration::get(static::COLLECTOR_ACTIVE)),
            'collectorKey' => (string) (Configuration::get(static::COLLECTOR_KEY)),
            'clientId' => Configuration::get(static::CLIENT_ID),
            'synchronizeCartsActive' => (Configuration::get(static::SYNC_CARTS_ACTIVE)),
            'synchronizedCartStatus' => (string) (Configuration::get(static::SYNC_CARTS_STATUS)),
            'synchronizedCartDelay' => $syncCartsDelay,
            'consultantScript' => (string) (Configuration::get(static::CONSULTANT_SCRIPT)),
            'enableCorporate' => (bool) (Configuration::get(static::ENABLE_CORPORATE_CLIENTS)),
            'enableHistoryUploads' => (bool) (Configuration::get(static::ENABLE_HISTORY_UPLOADS)),
            'enableBalancesReceiving' => (bool) (Configuration::get(static::ENABLE_BALANCES_RECEIVING)),
            'enableOrderNumberSending' => (bool) (Configuration::get(static::ENABLE_ORDER_NUMBER_SENDING)),
            'enableOrderNumberReceiving' => (bool) (Configuration::get(static::ENABLE_ORDER_NUMBER_RECEIVING)),
            'debugMode' => RetailcrmTools::isDebug(),
            'webJobs' => RetailcrmTools::isWebJobsEnabled(),
        ];
    }

    /**
     * Returns all settings names in DB
     *
     * @return array
     */
    public static function getSettingsNames()
    {
        return [
            'urlName' => static::API_URL,
            'apiKeyName' => static::API_KEY,
            'deliveryName' => static::DELIVERY,
            'statusName' => static::STATUS,
            'outOfStockStatusName' => static::OUT_OF_STOCK_STATUS,
            'paymentName' => static::PAYMENT,
            'deliveryDefaultName' => static::DELIVERY_DEFAULT,
            'paymentDefaultName' => static::PAYMENT_DEFAULT,
            'statusExportName' => static::STATUS_EXPORT,
            'collectorActiveName' => static::COLLECTOR_ACTIVE,
            'collectorKeyName' => static::COLLECTOR_KEY,
            'clientIdName' => static::CLIENT_ID,
            'synchronizeCartsActiveName' => static::SYNC_CARTS_ACTIVE,
            'synchronizedCartStatusName' => static::SYNC_CARTS_STATUS,
            'synchronizedCartDelayName' => static::SYNC_CARTS_DELAY,
            'uploadOrders' => static::UPLOAD_ORDERS,
            'runJobName' => static::RUN_JOB,
            'consultantScriptName' => static::CONSULTANT_SCRIPT,
            'enableCorporateName' => static::ENABLE_CORPORATE_CLIENTS,
            'enableHistoryUploadsName' => static::ENABLE_HISTORY_UPLOADS,
            'enableBalancesReceivingName' => static::ENABLE_BALANCES_RECEIVING,
            'enableOrderNumberSendingName' => static::ENABLE_ORDER_NUMBER_SENDING,
            'enableOrderNumberReceivingName' => static::ENABLE_ORDER_NUMBER_RECEIVING,
            'debugModeName' => static::ENABLE_DEBUG_MODE,
            'webJobsName' => static::ENABLE_WEB_JOBS,
            'jobsNames' => static::JOBS_NAMES,
        ];
    }

    /**
     * Returns modules list, caches result. Recreates cache when needed.
     * Activity indicator in cache will be rewrited by current state.
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function getCachedCmsModulesList()
    {
        $storedHash = (string) Configuration::get(static::MODULE_LIST_CACHE_CHECKSUM);
        $calculatedHash = md5(implode('#', Module::getModulesDirOnDisk(true)));

        if ($storedHash != $calculatedHash) {
            $serializedModules = [];
            static::$moduleListCache = Module::getModulesOnDisk(true);

            foreach (static::$moduleListCache as $module) {
                $serializedModules[] = json_encode($module);
            }

            Configuration::updateValue(static::MODULE_LIST_CACHE_CHECKSUM, $calculatedHash);
            static::writeModulesCache($serializedModules);

            return static::$moduleListCache;
        }

        try {
            if (is_array(static::$moduleListCache)) {
                return static::$moduleListCache;
            }

            $modulesList = static::requireModulesCache();

            if (false === $modulesList) {
                Configuration::updateValue(static::MODULE_LIST_CACHE_CHECKSUM, 'not exist');

                return static::getCachedCmsModulesList();
            }

            static::$moduleListCache = [];

            foreach ($modulesList as $serializedModule) {
                $deserialized = json_decode($serializedModule);

                if ($deserialized instanceof stdClass
                        && property_exists($deserialized, 'name')
                        && property_exists($deserialized, 'active')
                    ) {
                    $deserialized->active = Module::isEnabled($deserialized->name);
                    static::$moduleListCache[] = $deserialized;
                }
            }

            static::$moduleListCache = array_filter(static::$moduleListCache);
            unset($modulesList);

            return static::$moduleListCache;
        } catch (Exception $exception) {
            RetailcrmLogger::writeCaller(__METHOD__, $exception->getMessage());
            RetailcrmLogger::writeNoCaller($exception->getTraceAsString());
        } catch (Error $exception) {
            RetailcrmLogger::writeCaller(__METHOD__, $exception->getMessage());
            RetailcrmLogger::writeNoCaller($exception->getTraceAsString());
        }

        Configuration::updateValue(static::MODULE_LIST_CACHE_CHECKSUM, 'exception');

        return static::getCachedCmsModulesList();
    }

    /**
     * Writes module list to cache file.
     *
     * @param $data
     */
    private static function writeModulesCache($data)
    {
        $file = fopen(static::getModulesCache(), 'w+');

        if (false !== $file) {
            fwrite($file, '<?php' . PHP_EOL);
            fwrite($file, '// Autogenerated module list cache for retailCRM' . PHP_EOL);
            fwrite($file, '// Delete this file if you cannot see some payment types in module' . PHP_EOL);
            fwrite($file, 'return ' . var_export($data, true) . ';' . PHP_EOL);
            fflush($file);
            fclose($file);
        }
    }

    /**
     * Synchronized cartsIds time choice
     *
     * @return array
     */
    public function getSynchronizedCartsTimeSelect()
    {
        return [
            [
                'id_option' => '900',
                'name' => $this->l('After 15 minutes'),
            ],
            [
                'id_option' => '1800',
                'name' => $this->l('After 30 minutes'),
            ],
            [
                'id_option' => '2700',
                'name' => $this->l('After 45 minute'),
            ],
            [
                'id_option' => '3600',
                'name' => $this->l('After 1 hour'),
            ],
        ];
    }

    /**
     * Initializes arrays of messages
     */
    private function initializeTemplateMessages()
    {
        if (null === $this->templateErrors) {
            $this->templateErrors = [];
        }

        if (null === $this->templateWarnings) {
            $this->templateWarnings = [];
        }

        if (null === $this->templateConfirms) {
            $this->templateConfirms = [];
        }

        if (null === $this->templateErrors) {
            $this->templateInfos = [];
        }
    }

    /**
     * Returns error messages
     *
     * @return array
     */
    protected function getErrorMessages()
    {
        if (empty($this->templateErrors)) {
            return [];
        }

        return $this->templateErrors;
    }

    /**
     * Returns warning messages
     *
     * @return array
     */
    protected function getWarningMessage()
    {
        if (empty($this->templateWarnings)) {
            return [];
        }

        return $this->templateWarnings;
    }

    /**
     * Returns information messages
     *
     * @return array
     */
    protected function getInformationMessages()
    {
        if (empty($this->templateInfos)) {
            return [];
        }

        return $this->templateInfos;
    }

    /**
     * Returns confirmation messages
     *
     * @return array
     */
    protected function getConfirmationMessages()
    {
        if (empty($this->templateConfirms)) {
            return [];
        }

        return $this->templateConfirms;
    }

    /**
     * Replacement for default error message helper
     *
     * @param string|array $message
     *
     * @return string
     */
    public function displayError($message)
    {
        $this->initializeTemplateMessages();
        $this->templateErrors[] = $message;

        return ' ';
    }

    /**
     * Replacement for default warning message helper
     *
     * @param string|array $message
     *
     * @return string
     */
    public function displayWarning($message)
    {
        $this->initializeTemplateMessages();
        $this->templateWarnings[] = $message;

        return ' ';
    }

    /**
     * Replacement for default warning message helper
     *
     * @param string|array $message
     *
     * @return string
     */
    public function displayConfirmation($message)
    {
        $this->initializeTemplateMessages();
        $this->templateConfirms[] = $message;

        return ' ';
    }

    /**
     * Replacement for default warning message helper
     *
     * @param string|array $message
     *
     * @return string
     */
    public function displayInformation($message)
    {
        $this->initializeTemplateMessages();
        $this->templateInfos[] = $message;

        return ' ';
    }
}
