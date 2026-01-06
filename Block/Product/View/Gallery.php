<?php

namespace React\React\Block\Product\View;

use Magento\Catalog\Block\Product\View\Gallery as MagentoGallery;
use React\React\Features\ImagePreload;

class Gallery extends MagentoGallery
{
    /**
     * @var ImagePreload
     */
    protected $imagePreload;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param ImagePreload $imagePreload
     * @param array $data
     * @param \Magento\Catalog\Model\Product\Image\UrlBuilder|null $urlBuilder
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        ImagePreload $imagePreload,
        array $data = [],
        \Magento\Catalog\Model\Product\Image\UrlBuilder $urlBuilder = null
    ) {
        $this->imagePreload = $imagePreload;
        parent::__construct($context, $arrayUtils, $jsonEncoder, $data, null, [], $urlBuilder);
    }

    /**
     * Preload mobile hero image using HTTP Link header
     * 
     * @param string $imageUrl The URL of the image to preload
     * @param bool $isBase64 Whether the image is base64 encoded
     * @return void
     */
    public function preloadMobileImage($imageUrl, $isBase64 = false)
    {
        if (!$isBase64 && !empty($imageUrl) && !headers_sent()) {
            // Use PHP header() function directly to avoid Magento code generation issues
            header("Link: <" . $imageUrl . ">; rel=preload; as=image; fetchpriority=high", false);
        }
    }

    /**
     * Check if base64 image encoding is enabled
     * 
     * @return bool
     */
    public function isBase64ImageEnabled()
    {
        return $this->_scopeConfig->isSetFlag(
            'react_vue_config/product/base64_image',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
