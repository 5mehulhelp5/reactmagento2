<?php
namespace React\React\Block\Product\Renderer;

use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchConfigurable;

class Configurable extends SwatchConfigurable
{
    const SWATCH_RENDERER_TEMPLATE = 'React_React::product/view/renderer.phtml';

    /**
     * Return renderer template
     *
     * Template for product with swatches is different from product without swatches
     *
     * @return string
     */
    protected function getRendererTemplate()
    {
        return $this->isProductHasSwatchAttribute() ?
        self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;
    }

}
