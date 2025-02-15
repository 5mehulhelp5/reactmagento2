<?php
// React-Luma extended Template for Block
namespace React\React;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template as MTemplate;
use Magento\Framework\View\Element\Template\Context;

class Template extends MTemplate
{
    public $om;
    public $registry;
    public $config;

    public function __construct(
        Context $context,
        ObjectManager $om,
        Registry $registry,
        ScopeConfigInterface $config,
        array $data = []
    ) {
        $this->om = $om;
        $this->registry = $registry;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    // Function to encode an image as Base64
    public function imageToBase64($imagePath)
    {
        if (file_exists($imagePath)) {
            $imageData = file_get_contents($imagePath);
            $base64 = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath); // Get MIME type
            return "data:$mimeType;base64,$base64";
        }
        return "";
    }

    public function removeAdobeJSJunk()
    {
        return boolval($this->config->getValue('react_vue_config/junk/remove'));
    }

    public function removeAdobeCSSJunk()
    {
        if (!isset($_GET['css-react'])) {
            return $removeCSSjunk = boolval($this->config->getValue('react_vue_config/junk/remove'));
        }

        if (isset($_GET['css-react']) && $_GET['css-react'] === "false") {
            $removeCSSjunk = false;
        }
        if (isset($_GET['css-react']) && $_GET['css-react'] === "true") {
            $removeCSSjunk = true;
        }
        return $removeCSSjunk;
    }

}
