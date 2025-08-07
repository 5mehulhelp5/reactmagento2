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
        $html = '<div class="block-title">';
        $html .= '<strong>';
        $html .= '<span class="text">My Cart</span>';
        $html .= '<span class="qty" title="Items in Cart">' . $data['summary_count'] . '</span>';
        $html .= '</strong>';
        $html .= '</div>';

        $html .= '<div class="block-content">';
        $html .= '<button type="button" id="btn-minicart-close" class="action close" data-action="close" title="Close">';
        $html .= '<span>Close</span>';
        $html .= '</button>';

        if (!empty($data['items'])) {
            // Debug: Show available item data
            $html .= '<!-- Debug: Item data structure -->';
            $html .= '<!-- ' . json_encode($data['items'][0]) . ' -->';
            
            $html .= '<div class="items-total">';
            $html .= '<span class="count">' . $data['summary_count'] . '</span>';
            if ($data['summary_count'] > 1) {
                $html .= '<span>Items in Cart</span>';
            } else {
                $html .= '<span>Item in Cart</span>';
            }
            $html .= '</div>';

            $html .= '<div class="subtotal">';
            $html .= '<span class="label">Cart Subtotal</span>';
            $html .= '<div class="amount price-container">';
            $html .= '<span class="price-wrapper">';
            $html .= '<span class="price">' . $data['subtotal'] . '</span>';
            $html .= '</span>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="actions">';
            $html .= '<div class="primary">';
            $html .= '<button id="top-cart-btn-checkout" type="button" class="action primary checkout" data-action="close" title="Proceed to Checkout">Proceed to Checkout</button>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<strong class="subtitle">Recently added item(s)</strong>';
            $html .= '<div data-action="scroll" class="minicart-items-wrapper">';
            $html .= '<ol id="mini-cart" class="minicart-items">';
            foreach ($data['items'] as $item) {
                $html .= $this->renderCartItem($item);
            }
            $html .= '</ol>';
            $html .= '</div>';

            $html .= '<div class="actions">';
            $html .= '<div class="secondary">';
            $html .= '<a class="action viewcart" href="/checkout/cart/">';
            $html .= '<span>View and Edit Cart</span>';
            $html .= '</a>';
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="empty-cart">';
            $html .= '<p>Your cart is empty</p>';
            $html .= '</div>';
        }

        $html .= '</div>';
        
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
        $html = '<li class="item product product-item" data-role="product-item">';
        $html .= '<div class="product">';
        
        // Product image
        if (isset($item['product_url'])) {
            $html .= '<a href="' . $item['product_url'] . '" class="product-item-photo" title="' . $item['product_name'] . '">';
            
            // Try different possible image sources
            $imageSrc = '';
            if (isset($item['product_image']['src'])) {
                $imageSrc = $item['product_image']['src'];
            } elseif (isset($item['product_image'])) {
                $imageSrc = $item['product_image'];
            } elseif (isset($item['thumbnail'])) {
                $imageSrc = $item['thumbnail'];
            }
            
            if ($imageSrc) {
                $html .= '<span class="cart-product-image-container">';
                $html .= '<span class="cart-product-image-wrapper">';
                $html .= '<img class="cart-product-image-photo" src="' . $imageSrc . '" alt="' . $item['product_name'] . '" style="width: 120px;">';
                $html .= '</span>';
                $html .= '</span>';
            } else {
                // Fallback placeholder
                $html .= '<span class="cart-product-image-container">';
                $html .= '<span class="cart-product-image-wrapper">';
                $html .= '<img class="cart-product-image-photo" src="/media/catalog/product/placeholder/default/image.jpg" alt="' . $item['product_name'] . '" style="width: 120px;">';
                $html .= '</span>';
                $html .= '</span>';
            }
            $html .= '</a>';
        }
        
        $html .= '<div class="product-item-details">';
        
        // Product name
        $html .= '<strong class="product-item-name">';
        if (isset($item['product_url'])) {
            $html .= '<a href="' . $item['product_url'] . '">' . $item['product_name'] . '</a>';
        } else {
            $html .= $item['product_name'];
        }
        $html .= '</strong>';
        
        // Product options
        if (isset($item['options']) && !empty($item['options'])) {
            $html .= '<div class="product options">';
            $html .= '<dl class="product options list">';
            foreach ($item['options'] as $option) {
                $html .= '<dt class="label">' . $option['label'] . '</dt>';
                $html .= '<dd class="values">';
                $html .= '<span>' . $option['value'] . '</span>';
                $html .= '</dd>';
            }
            $html .= '</dl>';
            $html .= '</div>';
        }
        
        // Product pricing
        $html .= '<div class="product-item-pricing">';
        $html .= '<div class="price-container">';
        $html .= '<span class="price-wrapper">';
        $html .= '<span class="price">' . $item['product_price'] . '</span>';
        $html .= '</span>';
        $html .= '</div>';
        
        // Quantity
        $html .= '<div class="details-qty qty">';
        $html .= '<label class="label">Qty</label>';
        $html .= '<input type="number" min="0" size="4" class="item-qty cart-item-qty" value="' . $item['qty'] . '">';
        $html .= '</div>';
        $html .= '</div>';
        
        // Product actions
        $html .= '<div class="product actions">';
        if (isset($item['configure_url'])) {
            $html .= '<div class="primary">';
            $html .= '<a href="/checkout/cart/" class="action edit" title="Edit item">';
            $html .= '<span>Edit</span>';
            $html .= '</a>';
            $html .= '</div>';
        }
        $html .= '<div class="secondary">';
        $html .= '<a href="/checkout/cart/" class="action delete" data-cart-item="' . $item['item_id'] . '" title="Remove item">';
        $html .= '<span>Remove</span>';
        $html .= '</a>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // Close product-item-details
        $html .= '</div>'; // Close product
        $html .= '</li>';
        
        return $html;
    }
}
