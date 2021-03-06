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
 * @category  Ced
 * @package   Ced_CsOrder
 * @author    CedCommerce Core Team <connect@cedcommerce.com >
 * @copyright Copyright CEDCOMMERCE (https://cedcommerce.com/)
 * @license      https://cedcommerce.com/license-agreement.txt
 */

namespace Ced\CsOrder\Controller\Vorders;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlFactory;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Shipments
 * @package Ced\CsOrder\Controller\Vorders
 */
class Shipments extends \Ced\CsMarketplace\Controller\Vendor
{
    /**
     * @var Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Ced\CsMarketplace\Model\Vorders
     */
    protected $vorders;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;


    /**
     * Shipments constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $customerSession
     * @param UrlFactory $urlFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Ced\CsMarketplace\Helper\Data $csmarketplaceHelper
     * @param \Ced\CsMarketplace\Helper\Acl $aclHelper
     * @param \Ced\CsMarketplace\Model\VendorFactory $vendor
     * @param \Ced\CsMarketplace\Model\Vorders $vorders
     */

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $customerSession,
        UrlFactory $urlFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Ced\CsMarketplace\Helper\Data $csmarketplaceHelper,
        \Ced\CsMarketplace\Helper\Acl $aclHelper,
        \Ced\CsMarketplace\Model\VendorFactory $vendor,
        \Ced\CsMarketplace\Model\Vorders $vorders
    )
    {
        $this->registry = $registry;
        $this->vorders = $vorders;
        $this->customerSession = $customerSession;
        parent::__construct($context, $resultPageFactory, $customerSession, $urlFactory, $registry, $jsonFactory, $csmarketplaceHelper, $aclHelper, $vendor);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id', 0);
        $incrementId = 0;
        if ($orderId == 0) {
            $incrementId = (int)$this->getRequest()->getParam('increment_id', 0);
            if (!$incrementId) {
                $this->_forward('noRoute');
                return;
            }
        }
        $register = $this->registry;
        if ($orderId) {
            $vorder = $this->vorders->load($orderId);
        } else if ($incrementId) {
            $vendorId = $this->customerSession->getVendorId();
            $vorder = $this->vorders->loadByField(array('order_id', 'vendor_id'), array($incrementId, $vendorId));
        }
        $order = $vorder->getOrder(false, false);
        $register->register('current_order', $order);
        $register->register('sales_order', $order);
        $register->register('current_vorder', $vorder);
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
