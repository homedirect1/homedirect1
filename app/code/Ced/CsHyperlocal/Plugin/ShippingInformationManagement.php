<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://cedcommerce.com/license-agreement.txt
 *
 * @category    Ced
 * @package     Ced_CsHyperlocal
 * @author    CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CedCommerce (https://cedcommerce.com/)
 * @license      https://cedcommerce.com/license-agreement.txt
 */

namespace Ced\CsHyperlocal\Plugin;

use Magento\Framework\Exception\InputException;

/**
 * Class ShippingInformationManagement
 * @package Ced\CsHyperlocal\Plugin
 */
class ShippingInformationManagement
{
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Ced\CsHyperlocal\Helper\Data
     */
    protected $helper;

    /**
     * @var \Ced\CsMarketplace\Helper\Data
     */
    protected $marketplaceHelper;

    /**
     * @var \Ced\CsMarketplace\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * ShippingInformationManagement constructor.
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Ced\CsHyperlocal\Helper\Data $helper
     * @param \Ced\CsMarketplace\Helper\Data $marketplaceHelper
     * @param \Ced\CsMarketplace\Model\VendorFactory $vendorFactory
     */
    public function __construct(
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Ced\CsHyperlocal\Helper\Data $helper,
        \Ced\CsMarketplace\Helper\Data $marketplaceHelper,
        \Ced\CsMarketplace\Model\VendorFactory $vendorFactory
    )
    {
        $this->_countryFactory = $countryFactory;
        $this->cartRepository = $cartRepository;
        $this->helper = $helper;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->vendorFactory = $vendorFactory;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return mixed
     * @throws InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject, callable $proceed, $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation

    )
    {
        if ($this->helper->isModuleEnabled()) {
            $address = $addressInformation->getShippingAddress();
            $quote = $this->cartRepository->getActive($cartId);
            $quoteItems = $quote->getAllItems();
            $savedLocationFromSession = $this->helper->getShippingLocationFromSession();
            $filterType = $this->marketplaceHelper->getStoreConfig(\Ced\CsHyperlocal\Helper\Data::FILTER_TYPE);
            $filterProductsBy = $this->marketplaceHelper->getStoreConfig(\Ced\CsHyperlocal\Helper\Data::FILTER_PRODUCTS_BY);
            $distanceType = $this->marketplaceHelper->getStoreConfig(\Ced\CsHyperlocal\Helper\Data::DISTANCE_TYPE);
            $radiusConfig = $this->marketplaceHelper->getStoreConfig(\Ced\CsHyperlocal\Helper\Data::FILTER_RADIUS);

            if ($filterType == 'zipcode') {
                if ($address->getData('postcode') != $savedLocationFromSession['filterZipcode']) {
                    throw new InputException(__('Pincode doesn\'t match with the selected pincode.'));
                }
            } elseif ($filterType == 'city_state_country') {
                $country = $this->_countryFactory->create()->loadByCode($address->getData('country_id'));

                if ($savedLocationFromSession['city'] != '' && strtolower($savedLocationFromSession['city']) != strtolower($address->getData('city'))) {
                    throw new InputException(__('City doesn\'t match with the selected city.'));

                } elseif ($savedLocationFromSession['state'] != '' && strtolower($savedLocationFromSession['state']) != strtolower($address->getData('region'))) {
                    throw new InputException(__('Region doesn\'t match with the selected region.'));

                } elseif ($savedLocationFromSession['country'] != '' && strtolower($savedLocationFromSession['country']) != strtolower($country->getName())) {
                    throw new InputException(__('Country doesn\'t match with the selected country.'));
                }
            } elseif ($filterType == 'distance') {

                $latitude1 = $address->getData('latitude');
                $longitude1 = $address->getData('longitude');
                
                $destinationAddress = [$address->getData('street'), $address->getData('city'), $address->getData('region'), $address->getData('postcode'), $address->getData('country_id') ];

                $streetAdress = implode(',', $destinationAddress);
                $streetAdress = preg_replace("/[\r\n]*/", "", str_replace(' ', '+', $streetAdress));
                
                /*
                $latitude1 = 0;
                $longitude1 = 0;
                $apiKey = $this->marketplaceHelper->getStoreConfig(\Ced\CsHyperlocal\Helper\Data::API_KEY);
                $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $streetAdress . '&key=' . $apiKey;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                $result = curl_exec($ch);
                curl_close($ch);
                $result_array = json_decode($result, true);
                if ($result_array) {
                    if (isset($result_array['error_message'])) {
                        throw new InputException(__($result_array['error_message']));
                    } elseif (isset($result_array['results']['0']['geometry']['location']['lat']) && isset($result_array['results']['0']['geometry']['location']['lng'])) {

                        $latitude1 = $result_array['results']['0']['geometry']['location']['lat'];
                        $longitude1 = $result_array['results']['0']['geometry']['location']['lng'];
                    } else {
                        throw new InputException(__('There has been an error while calculating distance.'));
                    }
                }
                */

                $tolat = $latitude1;
                $tolong = $longitude1;
                if ($quoteItems) {
                    foreach ($quoteItems as $item) {
                        $vendorId = $item->getVendorId();
                        if ($vendorId) {
                            $vendorData = $this->vendorFactory->create()->load($vendorId);
                            $fromlat = $vendorData->getLatitude();
                            $fromlong = $vendorData->getLongitude();
                        } else {
                            $fromlat = $this->marketplaceHelper->getStoreConfig(\Ced\CsHyperlocal\Helper\Data::XML_LATITUDE);
                            $fromlong = $this->marketplaceHelper->getStoreConfig(\Ced\CsHyperlocal\Helper\Data::XML_LONGITUDE);
                        }
                        $tolat = is_array($tolat) && isset($tolat['value']) ? $tolat['value'] : $tolat;
                        $tolong = is_array($tolong) && isset($tolong['value']) ? $tolong['value'] : $tolong;
                        $distance = $this->helper->calculateDistancebyHaversine($fromlat, $fromlong, $tolat, $tolong);

                        
                        // if ($distance > $radiusConfig) {
                        //     throw new InputException(__('Address is not is range.'));
                        // }
                    }
                }
            }
        }
        return $proceed($cartId, $addressInformation);
    }
}
