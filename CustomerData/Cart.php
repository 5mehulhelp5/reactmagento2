<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace React\React\CustomerData;

use Magento\Checkout\CustomerData\Cart as MagentoCart;
use Magento\Customer\CustomerData\SectionSourceInterface;

/**
 * Cart source override for React Luma
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Cart extends MagentoCart implements SectionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function getSectionData()
    {
        // Get parent data first
        $data = parent::getSectionData();
        
        // Add HTML cart rendering if cart is not empty
        if (!empty($data['items'])) {
            $data['html_cart'] = $this->renderCartHtml($data);
        } else {
            $data['html_cart'] = null; // Render empty cart
        }
        
        return $data;
    }

    /**
     * Render cart HTML with enhanced React Luma features
     *
     * @param array $data Cart data
     * @return string
     */
    public function renderCartHtml($data)
    {
        // Build items HTML
        $itemsHtml = '';
        if (!empty($data['items'])) {
            // Debug: Show available item data
            $debugComment = '<!-- Debug: Item data structure -->' . PHP_EOL . '<!-- ' . json_encode($data['items'][0]) . ' -->';
            
            $itemsHtml = $debugComment . '
            <div class="items-total">
                <span class="count">' . $data['summary_count'] . '</span>';
            if ($data['summary_count'] > 1) {
                $itemsHtml .= '<span>Items in Cart</span>';
            } else {
                $itemsHtml .= '<span>Item in Cart</span>';
            }
            $itemsHtml .= '</div>

            <div class="subtotal">
                <span class="label">Cart Subtotal</span>
                <div class="amount price-container">
                    <span class="price-wrapper">
                        <span class="price">' . $data['subtotal'] . '</span>
                    </span>
                </div>
            </div>

            <div class="actions">
                <div class="primary">
                    <button id="top-cart-btn-checkout" type="button" class="action primary checkout" data-action="close" onclick="window.location.href=\'/checkout/\'" title="Proceed to Checkout">Proceed to Checkout</button>
                </div>
            </div>

            <strong class="subtitle">Recently added item(s)</strong>
            <div data-action="scroll" class="minicart-items-wrapper">
                <ol id="mini-cart" class="minicart-items">';
            
            foreach ($data['items'] as $item) {
                $itemsHtml .= $this->renderCartItem($item);
            }
            
            $itemsHtml .= '</ol>
            </div>

            <div class="actions">
                <div class="secondary">
                    <a class="action viewcart" href="/checkout/cart/">
                        <span>View and Edit Cart</span>
                    </a>
                </div>
            </div>';
        } else {
            $itemsHtml = '<div class="empty-cart">
                <p>Your cart is empty</p>
            </div>';
        }

        // Build complete cart HTML using heredoc
        $html = <<<HTML
        <div class="block-title">
            <strong>
                <span class="text">My Cart</span>
                <span class="qty" title="Items in Cart">{$data['summary_count']}</span>
            </strong>
        </div>
        <div class="block-content">
            <button type="button" id="btn-minicart-close" class="action close" data-action="close" title="Close">
                <span>Close</span>
            </button>
            {$itemsHtml}
        </div>
HTML;
        
        return $html;
    }

    /**
     * Render individual cart item HTML
     *
     * @param array $item Cart item data
     * @return string
     */
    protected function renderCartItem($item)
    {
        // Get image source with fallbacks
        $imageSrc = '';
        if (isset($item['product_image']['src'])) {
            $imageSrc = $item['product_image']['src'];
        } elseif (isset($item['product_image'])) {
            $imageSrc = $item['product_image'];
        } elseif (isset($item['thumbnail'])) {
            $imageSrc = $item['thumbnail'];
        }
        
        // Build product image HTML
        $imageHtml = '';
        if (isset($item['product_url'])) {
            $imageUrl = $imageSrc ?: '/media/catalog/product/placeholder/default/image.jpg';
            $imageHtml = '<a href="' . $item['product_url'] . '" class="product-item-photo" title="' . $item['product_name'] . '">
                <span class="cart-product-image-container">
                    <span class="cart-product-image-wrapper">
                        <img class="cart-product-image-photo" src="' . $imageUrl . '" alt="' . $item['product_name'] . '" style="width: 120px;">
                    </span>
                </span>
            </a>';
        }
        
        // Build product name HTML
        $nameHtml = '<strong class="product-item-name">';
        if (isset($item['product_url'])) {
            $nameHtml .= '<a href="' . $item['product_url'] . '">' . $item['product_name'] . '</a>';
        } else {
            $nameHtml .= $item['product_name'];
        }
        $nameHtml .= '</strong>';
        
        // Build product options HTML
        $optionsHtml = '';
        if (isset($item['options']) && !empty($item['options'])) {
            $optionsHtml = '<div class="product options">
                <dl class="product options list">';
            foreach ($item['options'] as $option) {
                $optionsHtml .= '<dt class="label">' . $option['label'] . '</dt>
                    <dd class="values">
                        <span>' . $option['value'] . '</span>
                    </dd>';
            }
            $optionsHtml .= '</dl>
            </div>';
        }
        
        // Build actions HTML
        $actionsHtml = '<div class="product actions">';
        if (isset($item['configure_url'])) {
            $actionsHtml .= '<div class="primary">
                <a href="/checkout/cart/" class="action edit" title="Edit item">
                    <span>Edit</span>
                </a>
            </div>';
        }
        $actionsHtml .= '<div class="secondary">
            <a href="/checkout/cart/" class="action delete" data-cart-item="' . $item['item_id'] . '" title="Remove item">
                <span>Remove</span>
            </a>
        </div>
        </div>';
        
        // Build complete cart item HTML using heredoc
        $html = <<<HTML
        <li class="item product product-item" data-role="product-item">
            <div class="product">
                {$imageHtml}
                <div class="product-item-details">
                    {$nameHtml}
                    {$optionsHtml}
                    <div class="product-item-pricing">
                        <div class="price-container">
                            <span class="price-wrapper">
                                <span class="price">{$item['product_price']}</span>
                            </span>
                        </div>
                        <div class="details-qty qty">
                            <label class="label">Qty</label>
                            <input type="number" min="0" size="4" class="item-qty cart-item-qty" value="{$item['qty']}">
                        </div>
                    </div>
                    {$actionsHtml}
                </div>
            </div>
        </li>
HTML;
        
        return $html;
    }
}
