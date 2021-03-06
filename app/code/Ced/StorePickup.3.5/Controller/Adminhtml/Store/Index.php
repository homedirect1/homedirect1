<?php
namespace Ced\StorePickup\Controller\Adminhtml\Store;
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @category  Ced
 * @package   Ced_StorePickup
 * @author    CedCommerce Core Team <connect@cedcommerce.com >
 * @copyright Copyright CEDCOMMERCE (http://cedcommerce.com/)
 * @license      http://cedcommerce.com/license-agreement.txt
 */
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    
    protected $resultPageFactory;
    
    public function __construct(Context $context,PageFactory $resultPageFactory) 
    {
        
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        
    }
    
    public function execute() 
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Ced_StorePickup::view_gird');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Store Pickup'));
        return $resultPage;
    }
}
