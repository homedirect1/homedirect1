<?php
/**
 * Adminhtml block for fieldset of configurable product
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ced\CsProduct\Block\ConfigurableProduct\Product\Steps;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\View\Element\Template\Context;

class Bulk extends \Magento\ConfigurableProduct\Block\Adminhtml\Product\Steps\Bulk
{
    /** @var Image */
    protected $image;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var Config
     */
    private $catalogProductMediaConfig;

    /**
     * @param Context $context
     * @param Image $image
     * @param Config $catalogProductMediaConfig
     * @param ProductFactory $productFactory
     */
    public function __construct(
        Context $context,
        Image $image,
        Config $catalogProductMediaConfig,
        ProductFactory $productFactory
    ) {
        parent::__construct($context, $image, $catalogProductMediaConfig, $productFactory);
        $this->image = $image;
        $this->productFactory = $productFactory;
        $this->catalogProductMediaConfig = $catalogProductMediaConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getCaption()
    {
        return __('Bulk Images &amp; Price');
    }

    /**
     * @return string
     */
    public function getNoImageUrl()
    {
        return $this->image->getDefaultPlaceholderUrl('thumbnail');
    }

    /**
     * Get image types data
     *
     * @return array
     */
    public function getImageTypes()
    {
        $imageTypes = [];
        foreach ($this->catalogProductMediaConfig->getMediaAttributeCodes() as $attributeCode) {
            /* @var $attribute Attribute */
            $imageTypes[$attributeCode] = [
                'code' => $attributeCode,
                'value' => '',
                'label' => $attributeCode,
                'scope' => '',
                'name' => $attributeCode,
            ];
        }
        return $imageTypes;
    }

    /**
     * @return array
     */
    public function getMediaAttributes()
    {
        static $simple;
        if (empty($simple)) {
            $simple = $this->productFactory->create()->setTypeId(Type::TYPE_SIMPLE)->getMediaAttributes();
        }
        return $simple;
    }
}
