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
 * @package     Ced_CsMarketplace
 * @author      CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CedCommerce (https://cedcommerce.com/)
 * @license      https://cedcommerce.com/license-agreement.txt
 */

declare(strict_types=1);

namespace Ced\CsMarketplace\ViewModel\Product;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Model\Product;

/**
 * Product options data view model
 */
class OptionsData implements ArgumentInterface
{
    /**
     * Returns options data array
     *
     * @param Product $product
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getOptionsData(Product $product)
    {
        return [];
    }
}