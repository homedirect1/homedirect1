<?php

/**
 * CedCommerce
  *
  * NOTICE OF LICENSE
  *
  * This source file is subject to the Academic Free License (AFL 3.0)
  * You can check the licence at this URL: http://cedcommerce.com/license-agreement.txt
  * It is also available through the world-wide-web at this URL:
  * http://opensource.org/licenses/afl-3.0.php
  *
  * @category    Ced
  * @package     Ced_CsPerkmshipping
  * @author   CedCommerce Core Team <connect@cedcommerce.com >
  * @copyright   Copyright CEDCOMMERCE (http://cedcommerce.com/)
  * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
  */

namespace Ced\CsPerkmshipping\Model\Carrier;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address\RateRequest;

class Perkmshipping extends \Ced\Perkmshipping\Model\Carrier\Perkmshipping
{
    

    protected $_code = 'perkmshipping';

    protected $_url = 'https://maps.googleapis.com/maps/api/distancematrix/';
    

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Ced\Advancedmatrix\Model\Resource\Carrier\AdvancedrateFactory
     */
    protected $dataHelper;
    protected $configHelper;
    protected $_vendorFactory;
    protected $scopeConfig;
    protected $_objectManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
    	\Ced\CsMarketplace\Model\VendorFactory $vendorFactory,
	\Magento\Framework\App\Request\Http $request,    
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
    	$this->_vendorFactory = $vendorFactory;
    	$this->scopeConfig = $scopeConfig;
	$this->_request = $request;    
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_objectManager = $objectManager;
       // $this->configHelper = $configHelper;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger,$rateResultFactory,$rateMethodFactory,$request,$data);

     }
    
    
 public function collectRates(RateRequest $request)
    {
    	if(!$this->scopeConfig->getValue('carriers/perkmshipping/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
    		return;
        
		if(!$this->_objectManager->get('Ced\CsMultiShipping\Helper\Data')->isEnabled()){
			return parent::collectRates($request);
		}
		if(!$this->_objectManager->get('Ced\CsPerkmshipping\Helper\Data')->isEnabled())
			return parent::collectRates($request);
		
		$vendorId = $request->getVendorId();
		//echo $vendorId;die;
		if(!$vendorId)
			return;
		$vsetting_model = $this->_objectManager->create('Ced\CsMarketplace\Model\Vsettings');
		
   	 try{	
			$PerkmSpecificConfig = array();
			
			if($vendorId!="admin")
				$PerkmSpecificConfig = $request->getVendorShippingSpecifics();
			//Collect Rates For Admin
			if($vendorId!="admin"){
				$storeAdd = $vsetting_model->getCollection()->addFieldToFilter('vendor_id',$vendorId);
				foreach($storeAdd as $key=> $value){
					if($value['key'] == 'shipping/address/country_id'){
						$cId = $value->getValue();
					}
					if($value['key'] == 'shipping/address/postcode'){
						$pCode = $value->getValue();
					}
					if($value['key'] == 'shipping/address/city'){
						$city = $value->getValue();
					}
				}		
					
				$country = $this->_objectManager->create('Magento\Directory\Model\Country')->loadByCode($cId);
				$cName = $country->getName();
				$vAddress = $city.','.$pCode.','.$city.','.$cName;
				
				if($vAddress){
					$address = $vAddress;
			
				}else{
					$address = $this->getConfigData('address');
				}
					
				$destCountryId = $request->getDestCountryId();
				$vendor = $this->_objectManager->create('Ced\CsMarketplace\Model\Vendor')->load($vendorId);
				$allow_country_key = 'shipping/'.$this->_code.'/allowed_country';
				$helper = $this->_objectManager->create('Ced\CsMarketplace\Helper\Data');
				$key_tmp=$helper->getTableKey('key');
				$vendor_id_tmp=$helper->getTableKey('vendor_id');
				$availableCountries = explode(',',$vsetting_model->loadByField(array($key_tmp,$vendor_id_tmp),array($allow_country_key, $vendorId))
						->getValue());
				if(!in_array($destCountryId,$availableCountries))
				{
					return false;
				} 
				$address_calc = $PerkmSpecificConfig['address_calc'];
				$distMetric = $PerkmSpecificConfig['distance_metric'];
									
				if($PerkmSpecificConfig['distance_max']){
					$distMax = $PerkmSpecificConfig['distance_max'];
				}else{
					$distMax = $this->getConfigData('distance_max');
				}
					
				$distRound = $PerkmSpecificConfig['distance_round'];
					
				$priceRound = $PerkmSpecificConfig['price_round'];
				
					//print_r($PerkmSpecificConfig['shipping_zones']);die;
				$shipping_zones1 ='';
				 if($PerkmSpecificConfig['shipping_zones']){
					$data = json_decode($PerkmSpecificConfig['shipping_zones']);
					$shipping_zones1 = $data;
				}else{ 
					$shipping_zones	= @unserialize($this->getConfigData('shipping_zones'));
				}

				$minmax = $PerkmSpecificConfig['minmax'];
				
				$priceMin = $PerkmSpecificConfig['price_min'];
					
				$priceMax = $PerkmSpecificConfig['price_max'];		
					
				$freeShip = $PerkmSpecificConfig['freeshipping'];
					
				$freeTotal = $PerkmSpecificConfig['free_shipping_subtotal'];			
					
				$minimum = $PerkmSpecificConfig['minimum'];
			
				$minimumTotal = $PerkmSpecificConfig['minimum_subtotal'];
				$api_key = $this->getConfigData('apikey');
				
				if($PerkmSpecificConfig['title']){
					$title = $PerkmSpecificConfig['title'];
				}else{
					$title = $this->getConfigData('title');
				}
			
				if($PerkmSpecificConfig['methodtitle']){
					$Methodtitle = $PerkmSpecificConfig['methodtitle'];
				}else{
					$Methodtitle = $this->getConfigData('methodtitle');
				}
					
				$specificerrmsg		= $this->getConfigData('specificerrmsg');

				if($minimum && ($minimumTotal > $request->getBaseSubtotalInclTax())) {
					if($specificerrmsg) {
						$error = $this->_rateErrorFactory->create();
						$error->setCarrier($this->_code);
						$error->setCarrierTitle($title);
						$error->setErrorMessage($specificerrmsg);
					} else {
						return false;
					}
				}
			
				if($distMetric == 'metric') {
					$distance_m = 'km';
				} else {
					$distance_m = 'mi';
				}
					
				if(($request->getDestCity() && $request->getDestCountryId()) || $request->getDestPostcode()) {
						
					if($request->getDestPostcode()) {
						$postcode = $request->getDestPostcode();
					} else {
						$postcode = '';
					}
					$shipfrom = str_replace(' ', '+', $address);
						
					$street ='';
					
					if($address_calc == 'full') {
						if($request->getDestStreet()) {
							$street = $request->getDestStreet();
						} else {
							$street = '';
						}
						if($this->_request->getControllerName()=='cart'){
							$street = '';
						}	
						$shipto = str_replace(' ', '+',($street . ',+' . $postcode . ',+' . $request->getDestCity() . ',+' . $request->getDestCountryId()));
					} else {
						$shipto = str_replace(' ', '+',($postcode . ',+' . $request->getDestCity() . ',+' . $request->getDestCountryId()));
							
					}
					$url = empty($api_key) ? $this->_url.'json?origins=' . $shipfrom . '&destinations=' . $shipto . '&mode=driving&sensor=false&units=' . $distMetric : $this->_url.'json?origins=' . $shipfrom . '&destinations=' . $shipto . '&mode=driving&sensor=false&units=' . $distMetric.'&key='.$api_key;
					
					$result = file_get_contents($url);
					if(!$result) {
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						$data = json_decode(curl_exec($ch), true);
					} else {
						$data = json_decode(utf8_encode($result), true);
					}
					
					$status = $data['status'];
						
					if(isset($data['rows'][0]['elements'][0]['distance']['value'])) {
						$distance = $data['rows'][0]['elements'][0]['distance']['value'];
							
						if($distMetric == 'metric') {
							$distkm = ($distance / 1000);
						} else {
							$distkm = ($distance / 1609);
						}
					} else {
						$distance = '';
						$distkm = '';
					}

					switch ($distRound) {
						case '2':
							$distkm = round($distkm, 2);
							break;
						case '1':
							$distkm = round($distkm, 1);
							break;
						case '0':
							$distkm = round($distkm, 0);
							break;
						case 'C':
							$distkm = ceil($distkm);
							break;
						case 'F':
							$distkm = floor($distkm);
							break;
					}
					
						 //Check $diskm is Null or not
					$distance_text=0;
	    	                        if($distkm!='')
	    	    	                     {
						// Format distance
						if($distMetric == 'metric') {
							$distance_text = number_format($distkm, 2, '.', '') . ' ' . $distance_m;
						} else {
						$distance_text = number_format($distkm, 2, '.', '') . ' ' . $distance_m;
					       }	
				           }
						
				} else {
					$status = 'OK';
					$distkm = '0';
				}
		
					
				// Google down
				if($status != 'OK')
					return false;
						// No Distcance
				if(!isset($data['rows'][0]['elements'][0]['distance']['value'])) {
					if($specificerrmsg) {
						$error = $this->_rateErrorFactory->create();
						$error->setCarrier($this->_code);
						$error->setCarrierTitle($title);
						$error->setErrorMessage($specificerrmsg);
					} else {
						return false;
					}
				}
					
				// Distance > Max distance
				if(($distMax) && ($distkm > $distMax)) {
					if($specificerrmsg) {
						$error = $this->_rateErrorFactory->create();
						$error->setCarrier($this->_code);
						$error->setCarrierTitle($title);
						$error->setErrorMessage($specificerrmsg);
					} else {
						return false;
					}
				}
					
				// PRICE
				$price = '0.00';
					
				// Loop Through Zones
				//$distkm = 25;
			
			
				$distance_left = $distkm;
				//echo $distkm;die;
				if($shipping_zones1){
					foreach($shipping_zones1 as $shipping_zone) {
						if($shipping_zone->from <= $distkm) {
							$zone = ($shipping_zone->to - $shipping_zone->from);
							if($distance_left > $zone) {
								if($shipping_zone->type == 'fixed') {
									$price = 0;
									$price += $shipping_zone->price;
									
								} else {
									$price = 0;
									$price += ($shipping_zone->price * $zone);
								}
							} else {
								if($shipping_zone->type == 'fixed') {
									$price = 0;
									$price += $shipping_zone->price;
									
								} else {
									$price = 0;
									$price += ($shipping_zone->price * $distance_left);
								}
							}
							$distance_left = ($distance_left - $zone);
							if($distance_left < 0) {
								break;
							}
						}
					}
				}/*else{
					foreach($shipping_zones as $shipping_zone) {
						if($shipping_zone['from'] <= $distkm) {
							$zone = ($shipping_zone['to'] - $shipping_zone['from']);
							if($distance_left > $zone) {
								if($shipping_zone['type'] == 'fixed') {
									$price = 0;
									$price += $shipping_zone['price'];

								} else {
									$price = 0;
									$price += ($shipping_zone['price'] * $zone);
								}
							} else {
								if($shipping_zone['type'] == 'fixed') {
									$price = 0;
									$price += $shipping_zone['price'];
								} else {
									$price += ($shipping_zone['price'] * $distance_left);
								}
							}
							$distance_left = ($distance_left - $zone);
							if($distance_left < 0) {
								break;
							}
						}
					}
				}*/
			
					
				// Rounding
				switch($priceRound) {
					case '2':
						$price = round($price, 2);
						break;
					case '1':
						$price = round($price, 1);
						break;
					case '0':
						$price = round($price, 0);
						break;
					case 'C':
						$price = ceil($price);
						break;
					case 'F':
						$price = floor($price);
						break;
				}
					
				// Min & Max
				if($minmax) {
					if($priceMax) {
						if($price > $priceMax) { $price = $priceMax; }
					}
					if($priceMin) {
						if($price < $priceMin) { $price = $priceMin; }
					}
				}
					
				// Freeshipment
				if($freeShip) {
					if($request->getBaseSubtotalInclTax() >= $freeTotal) {
						$price = '0';
					}
				}
					
				if($vendorId)
					$custom_method = $this->_code.\Ced\CsMultiShipping\Model\Shipping::SEPARATOR.$vendorId;
				else
					$custom_method = $this->_code;
					
			
				$result = $this->_rateResultFactory->create();
	    	$method = $this->_rateMethodFactory->create();
					
			    
			
				// save carrier information
                $method->setVendorId($vendorId);
				$method->setCarrier($this->_code);
				$method->setCarrierTitle($title);
				$method->setMethod($custom_method);
				$method->setMethodTitle($Methodtitle . ' (' . $distance_text . ')');
				$method->setCost($price);
				$method->setPrice($price);
		
				
				if(isset($error)) {
					$result->append($error);
				} else {
					$result->append($method);
				}
				return $result;
			}
			 
			else{
				return parent::collectRates($request);
			}
		}catch(Exception $e){
			return $e->getMessage();
		}
   }	
}
