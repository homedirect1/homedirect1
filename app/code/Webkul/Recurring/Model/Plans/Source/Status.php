<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Recurring
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\Recurring\Model\Plans\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Webkul Recurring Plans Source Status Model
 */
class Status implements OptionSourceInterface
{
    const ENABLED  = 1;
    const DISABLED = 0;
    
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
            $options = [
                [
                    'label' => __("Enabled"),
                    'value' => self::ENABLED,
                ],
                [
                    'label' => __("Disabled"),
                    'value' => self::DISABLED,
                ]
            ];
            return $options;
    }
}
