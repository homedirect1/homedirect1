<?php
/**
 * Webkul Software
 *
 * @category Webkul
 * @package Webkul_Recurring
 * @author Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 */
namespace Webkul\Recurring\Observer;

use Magento\Store\Model\StoreRepository;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var StoreRepository
     */
    protected $storeRepository;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Webkul\Recurring\Model\SubscriptionTypeFactory
     */
    protected $planTypeFactory;

    /**
     * @var \Webkul\Recurring\Model\TermFactory
     */
    protected $termsFactory;

    /**
     * @var \Webkul\Recurring\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Catalog\Model\Attribute\ScopeOverriddenValue
     */
    protected $scopeOverriddenValue;
    
    /**
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Webkul\Recurring\Model\SubscriptionTypeFactory $planTypeFactory
     * @param \Webkul\Recurring\Model\TermFactory $termsFactory
     * @param \Webkul\Recurring\Helper\Data $helper
     * @param StoreRepository $storeRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Catalog\Model\Attribute\ScopeOverriddenValue $scopeOverriddenValue
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Webkul\Recurring\Model\SubscriptionTypeFactory $planTypeFactory,
        \Webkul\Recurring\Model\TermFactory $termsFactory,
        \Webkul\Recurring\Helper\Data $helper,
        StoreRepository $storeRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Catalog\Model\Attribute\ScopeOverriddenValue $scopeOverriddenValue
    ) {
        $this->storeRepository = $storeRepository;
        $this->termsFactory    = $termsFactory;
        $this->helper          = $helper;
        $this->request         = $request;
        $this->planTypeFactory = $planTypeFactory;
        $this->messageManager  = $messageManager;
        $this->scopeOverriddenValue = $scopeOverriddenValue;
    }

    /**
     * This function returns the array of website id and store id
     *
     * @return array
     */
    private function getStoreList()
    {
        $stores     = $this->storeRepository->getList();
        $storeList  = [];
        foreach ($stores as $store) {
            $websiteId = $store["website_id"];
            $storeId = $store["store_id"];
            $storeList[$websiteId][] = $storeId;
        }
        return $storeList;
    }

    /**
     * This function will return the current website Id
     *
     * @return void
     */
    private function getCurrentWebsiteId($currentStoreId)
    {
        $stores     = $this->storeRepository->getList();
        foreach ($stores as $store) {
            if ($currentStoreId == $store["store_id"]) {
                return $store["website_id"];
            }
        }
        return 0;
    }

    /**
     * Values verification
     *
     * @param array $value
     * @return boolean
     */
    private function verifyRequest($value)
    {
        return $value['name'] && $value['description'] && $value['initial_fee'] != "" && $value['subscription_charge'];
    }

    /**
     * Add quote item handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $planData    = $this->request->getParams();
        $product     = $observer->getProduct();
        $productId   = $product->getId();
        $storeId = 0;

        try {
            if (isset($planData["product"]["current_store_id"])) {
                $storeId     = $planData["product"]["current_store_id"];
            }
            $recurrScope = $this->helper->getConfig('general_settings/price_scope');
            if (isset($planData['plans'])) {
                foreach ($planData['plans'] as $key => $value) {
                    if ($storeId != 0) {
                        $value['initial_fee'] = $value['initial_fee'] ?? ' ';
                        $value['subscription_charge'] = $value['subscription_charge'] ?? ' ';
                    }
                    $requestFlag = $this->verifyRequest($value);
                    if ($requestFlag) {
                        if ($storeId == 0) {
                            $this->savePriceStoreWise($planData, $key, $value, $recurrScope, $productId);
                        } else {
                            $data = [
                                'id'                  => $value['id'],
                                'canUpdate'           => 1,
                                'name'                => strip_tags($value['name']),
                                'type'                => $key,
                                'selected'            => $value['selected'],
                                'product_id'          => $productId,
                                'status'              => ($value['selected']) ? 1 : 0,
                                'description'         => strip_tags($value['description']),
                                'initial_fee'         => $value['initial_fee'],
                                'store_id'            => $storeId,
                                'website_id'          => $this->getCurrentWebsiteId($storeId),
                                'subscription_charge' => $value['subscription_charge'],
                                'engine'              => isset($value['engine']) ? $value['engine'] : ""
                            ];
                            $this->saveValues($productId, $data, $recurrScope);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->helper->logDataInLogger(
                "Observer_ProductSave execute : ".$e->getMessage()
            );
        }
    }

    /**
     * This function is used to set the price values for website store and global
     *
     * @param array $wholeData
     * @param integer $type
     * @param array $data
     * @param integer $scope
     * @return void
     */
    private function savePriceStoreWise($wholeData, $type, $planParam, $scope, $productId)
    {
        $storeList = $this->getStoreList();
        $currentStoreId = $this->request->getParam('store', 0);
        $currentWebsiteId =  $this->getCurrentWebsiteId($currentStoreId);
        sort($storeList);
        
        foreach ($storeList as $websiteId => $stores) {
            if ($scope != 1 || $currentWebsiteId == $websiteId) {
                foreach ($stores as $storeId) {
                    $data = [
                            'id'                  => $planParam['id'],
                            'name'                => strip_tags($planParam['name']),
                            'type'                => $type,
                            'selected'            => $planParam['selected'],
                            'product_id'          => $productId,
                            'status'              => ($planParam['selected']) ? 1 : 0,
                            'description'         => strip_tags($planParam['description']),
                            'initial_fee'         => $planParam['initial_fee'],
                            'store_id'            => $storeId,
                            'website_id'          => $websiteId,
                            'subscription_charge' => $planParam['subscription_charge'],
                            'engine'              => isset($planParam['engine']) ? $planParam['engine'] : ""
                    ];
                    $this->saveValues($productId, $data, $scope);
                }
            }
        }
    }

    /**
     * This function is use to save the subscription types of the product
     *
     * @param integer $productId
     * @param array $data
     * @param integer $scopeFlag
     * @return void
     */
    private function saveValues($productId, $data, $scopeFlag)
    {
        $model = $this->planTypeFactory->create();
        $time = date('Y-m-d H:i:s');
        $returnValue = $this->validatePlans($productId, $data['type'], $data['id']);
        if ($data['initial_fee'] == 0) {
            $data['initial_fee'] = $data['initial_fee']."";
        }
        if ($returnValue["flag"]) {
            $data['id'] = $this->getExistingId($productId, $data);
            
            if ($data['id'] != "") {
                $model = $model->load($data['id']);
                if ($data['store_id'] != 0) {
                    if (($scopeFlag == 0 && isset($data['canUpdate']))
                        || ($scopeFlag == 1 && !isset($data['canUpdate']))
                    ) {
                        unset($data['initial_fee']);
                        unset($data['subscription_charge']);
                    }
                    if (!isset($data['canUpdate'])) {
                        unset($data['name']);
                        unset($data['description']);
                    }
                }
                $data['update_time'] = $time;
                $data['sort_order'] = $returnValue["sort_order"];
                $model->setData($data);
                $model->setId($data['id']);
                $model->save();
            } else {
                $data['update_time'] = $time;
                $data['sort_order'] = $returnValue["sort_order"];
                $data['created_time'] = $time;
                $model->setData($data);
                $model->save();
            }
        }
    }

    /**
     * Existing plan Id
     *
     * @param integer $productId
     * @param array $data
     * @return string
     */
    private function getExistingId($productId, $data)
    {
        $collection = $this->planTypeFactory->create()->getCollection();
        $collection->addFieldToFilter("type", $data["type"]);
        $collection->addFieldToFilter("product_id", $productId);
        $collection->addFieldToFilter("store_id", $data["store_id"]);
        $collection->addFieldToFilter("website_id", $data["website_id"]);
        
        foreach ($collection as $model) {
            return $model->getId();
        }
        return '';
    }

    /**
     * This function is used to validate the plan and return the sort order accordingly
     *
     * @param integer $productId
     * @param integer $type
     * @param integer $id
     * @return array
     */
    private function validatePlans($productId, $type, $id)
    {
        $collection = $this->planTypeFactory->create()->getCollection();
        $collection->addFieldToFilter("product_id", $productId);
        $collection->addFieldToFilter("type", $type);
        if ($id != "") {
            $collection->addFieldToFilter("entity_id", $id);
        }
        $sortOrder = $this->termsFactory->create()->load($type)->getSortOrder();
        if ($collection->getSize()) {
            return [
                "sort_order" => $sortOrder,
                "flag" => true
            ];
        }
         return [
            "sort_order" => $sortOrder,
            "flag" => true
         ];
    }

    /**
     * This function is used to check the mapping product with subscription type
     *
     * @param object $model
     * @param integer $productId
     * @return void
     */
    private function checkMapping($model, $productId)
    {
        $productIds = $model->getProductIds();
        $productIdsData = '';
        $idNValues = explode(',', $productIds);
        $flag = false;
        foreach ($idNValues as $values) {
            if ($values) {
                $exploded = explode('=>', $values);
                if ($exploded[0] == $productId) {
                    $flag = true;
                } else {
                    $productIdsData = $productIdsData.','.$values;
                }
            }
        }
        if ($flag) {
            $model->setProductIds($productIdsData);
            $model->setId($model->getId())->save();
        }
    }
}
