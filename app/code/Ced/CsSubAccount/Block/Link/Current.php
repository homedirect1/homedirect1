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
 * @package     Ced_CsSubAccount
 * @author      CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CedCommerce (https://cedcommerce.com/)
 * @license      https://cedcommerce.com/license-agreement.txt
 */

namespace Ced\CsSubAccount\Block\Link;

use Magento\Customer\Model\Session;

/**
 * Class Current
 * @package Ced\CsSubAccount\Block\Link
 */
class Current extends \Ced\CsMarketplace\Block\Link\Current
{
    /**
     * Default path
     *
     * @var \Magento\Framework\App\DefaultPathInterface
     */
    protected $_defaultPath;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var \Ced\CsSubAccount\Model\CsSubAccountFactory
     */
    protected $csSubAccountFactory;

    /**
     * Current constructor.
     * @param \Ced\CsSubAccount\Model\CsSubAccountFactory $csSubAccountFactory
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Ced\CsSubAccount\Model\CsSubAccountFactory $csSubAccountFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        Session $customerSession,
        array $data = []
    )
    {
        parent::__construct($context, $defaultPath, $data);
        $this->_defaultPath = $defaultPath;
        $this->_session = $customerSession;
        $this->csSubAccountFactory = $csSubAccountFactory;
    }

    /**
     * Get href URL
     *
     * @return string
     */
    public function getHref()
    {

        if (strlen($this->getPath()) > 0 && $this->getPath() != '#') {
            return $this->getUrl($this->getPath());
        }
        return '#';
    }

    /**
     * Get current mca
     *
     * @return string
     */
    private function getMca()
    {
        $routeParts = [
            'module' => $this->_request->getModuleName(),
            'controller' => $this->_request->getControllerName(),
            'action' => $this->_request->getActionName(),
        ];

        $parts = [];
        foreach ($routeParts as $key => $value) {
            if (!empty($value) && $value != $this->_defaultPath->getPart($key)) {
                $parts[] = $value;
            }
        }
        return implode('/', $parts);
    }


    /**
     * Check if link leads to URL equivalent to URL of currently displayed page
     *
     * @return bool
     */
    public function isCurrent()
    {
        $layout = $this->getLayout();
        $containerChilds = $layout->getChildNames($this->getNameInLayout());
        $level = (int)$this->getLevel();
        if (count($containerChilds) > 0) {
            foreach ($containerChilds as $containerChild) {
                foreach ($layout->getChildBlocks($containerChild) as $child) {
                    if ($child->getCurrent() || $child->getUrl($child->getPath()) == $child->getUrl($child->getMca())) {
                        return true;
                    }
                }
            }
        } else {
            return ($level == 1) && ($this->getCurrent() || $this->getUrl($this->getPath()) == $this->getUrl($this->getMca()));
        }

    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $subVendor = $this->_session->getSubVendorData();
        
        if (empty($subVendor) && $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getName())) == 'cssubaccount_profile') {
            return;
        } elseif (!empty($subVendor) && $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getName())) == 'cssubaccount') {
            return;
        } elseif (!empty($subVendor) && $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getName())) == 'vendor_profile') {
            return;
        }


        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        }

        $highlight = '';
        if ($this->isCurrent()) {
            $highlight = ' active';
        }
        if (0) {
            $html = '<li id="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getName())) . '" class="nav item1">';
            $html .= '<i class="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getFontAwesome())) . '"></i>';
            $html .= '<span><strong style="margin-left: 3px;">'
                . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel()))
                . '</strong></span>';

            $childHtml = '';
            $childHtml = $this->getChildHtml();
            if (strlen($childHtml) > 0) {
                $html .= '<span class="fa arrow"></span>';
            }

            if (strlen($childHtml) > 0) {
                $html .= $childHtml;
                $html .= '<div class="largeview-submenu">';
                $html .= $childHtml;
                $html .= '</div>';
            }

            $html .= '</a>';
            $html .= '</li>';
        } else {
            if (!empty($subVendor)) {
                $resources = $this->csSubAccountFactory->create()->load($subVendor['id'])->getRole();
                if ($resources !== 'all') {
                    $stringpath = $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getPath()));
                    if ($stringpath == "#") {
                        $stringpath = $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getPath())) . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getName()));
                    } else {
                        $stringpath = $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getPath()));
                        $stringpath = explode("/", $stringpath);
                        if (!empty($stringpath)) {
                            if (!isset($stringpath[1]) || !$stringpath[1])
                                $stringpath[1] = "index";

                            if (!isset($stringpath[2]) || !$stringpath[2])
                                $stringpath[2] = "index";

                            $stringpath = $stringpath[0] . "/" . $stringpath[1] . "/" . $stringpath[2];
                        }
                    }
                    $role = explode(',', $resources);
                    if (in_array($stringpath, $role) || ($this->getName() === 'vendor_dashboard') || in_array($stringpath.'/', $role)) {
                        $html = '<li id="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getName())) . '" class="nav item"><a class="' . $highlight . '" href="' . $this->escapeHtml($this->getHref()) . '"';
                        $html .= $this->getTitle()
                            ? ' title="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getTitle())) . '"'
                            : '';
                        $html .= '>';
                        $html .= '<i class="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getFontAwesome())) . '"></i>';

                        if ($this->getIsHighlighted() || strlen($highlight) > 0) {
                            $html .= '<span><strong style="margin-left: 3px;">';
                        } else {
                            $html .= '<span style="margin-left: 3px;">';
                        }

                        $html .= $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel()));

                        if ($this->getIsHighlighted() || strlen($highlight) > 0) {
                            $html .= '</strong></span>';
                        } else {
                            $html .= '</span>';
                        }

                        $childHtml = '';
                        $childHtml = $this->getChildHtml();

                        if (strlen($childHtml) > 0) {
                            $html .= '<span class="fa arrow"></span>';
                        }

                        $html .= '</a>';

                        if (strlen($childHtml) > 0) {
                            $html .= $childHtml;
                            $html .= '<div class="largeview-submenu">';
                            $html .= $childHtml;
                            $html .= '</div>';
                        }
                        $html .= '</li>';
                        return $html;
                    } else {
                        return;
                    }
                } else {

                    die("sdv");

                    $html = '
                <li id="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getName())) . '" class="nav item"><a class="' . $highlight . '" href="' . $this->escapeHtml($this->getHref()) . '"';
                    $html .= $this->getTitle()
                        ? ' title="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getTitle())) . '"'
                        : '';
                    $html .= '>';
                    $html .= '
                <i class="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getFontAwesome())) . '"></i>';

                    if ($this->getIsHighlighted() || strlen($highlight) > 0) {
                        $html .= '<span><strong style="margin-left: 3px;">';
                    } else {
                        $html .= '<span style="margin-left: 3px;">';
                    }

                    $html .= $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel()));

                    if ($this->getIsHighlighted() || strlen($highlight) > 0) {
                        $html .= '</strong></span>';
                    } else {
                        $html .= '</span>';
                    }

                    $childHtml = '';
                    $childHtml = $this->getChildHtml();
                    if (strlen($childHtml) > 0) {
                        $html .= '
                    <span class="fa arrow"></span>';
                    }

                    $html .= '</a>';

                    if (strlen($childHtml) > 0) {
                        $html .= $childHtml;
                        $html .= '<div class="largeview-submenu">';
                        $html .= $childHtml;
                        $html .= '</div>';
                    }
                    $html .= '</li>';
                }
            }
            $html = '<li id="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getName())) . '" class="nav item"><a class="' . $highlight . '" href="' . $this->escapeHtml($this->getHref()) . '"';
            $html .= $this->getTitle()
                ? ' title="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getTitle())) . '"'
                : '';
            $html .= '>';
            $html .= '<i class="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getFontAwesome())) . '"></i>';

            if ($this->getIsHighlighted() || strlen($highlight) > 0) {
                $html .= '<span><strong style="margin-left: 3px;">';
            } else {
                $html .= '<span style="margin-left: 3px;">';
            }

            $html .= $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel()));

            if ($this->getIsHighlighted() || strlen($highlight) > 0) {
                $html .= '</strong></span>';
            } else {
                $html .= '</span>';
            }

            $childHtml = '';
            $childHtml = $this->getChildHtml();

            if (strlen($childHtml) > 0) {
                $html .= '<span class="fa arrow"></span>';
            }

            $html .= '</a>';

            if (strlen($childHtml) > 0) {
                $html .= $childHtml;
                $html .= '<div class="largeview-submenu">';
                $html .= $childHtml;
                $html .= '</div>';
            }
            $html .= '</li>';
        }
        return $html;
    }
}
