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
        // Check cookie first
        if (isset($_COOKIE['js-junk'])) {
            return $_COOKIE['js-junk'] === "true";
        }
        
        // Fall back to GET parameter
        if (isset($_GET['js-junk']) && $_GET['js-junk'] === "false") {
            return false;
        }
        if (isset($_GET['js-junk']) && $_GET['js-junk'] === "true") {
            return true;
        }
        
        // Fall back to config
        return boolval($this->config->getValue('react_vue_config/junk/remove'));
    }

    public function removeAdobeCSSJunk()
    {
        // Check cookie first
        if (isset($_COOKIE['css-react'])) {
            return $_COOKIE['css-react'] === "true";
        }
        
        // Fall back to GET parameter
        if (!isset($_GET['css-react'])) {
            return boolval($this->config->getValue('react_vue_config/junk/remove'));
        }

        if (isset($_GET['css-react']) && $_GET['css-react'] === "false") {
            return false;
        }
        if (isset($_GET['css-react']) && $_GET['css-react'] === "true") {
            return true;
        }
        
        // Fall back to config (should not reach here, but safety fallback)
        return boolval($this->config->getValue('react_vue_config/junk/remove'));
    }

    public function deferJS()
    {
        // Check GET parameter first
        if (isset($_GET['defer-js']) && $_GET['defer-js'] === "false") {
            return false;
        }
        if (isset($_GET['defer-js']) && $_GET['defer-js'] === "true") {
            return true;
        }
        
        // Fall back to config (default to true if not set)
        $configValue = $this->config->getValue('react_vue_config/junk/defer_js');
        return $configValue === null || $configValue === '' ? true : boolval($configValue);
    }

    public function getInlineJs($file) {
        $jsContent = file_get_contents(__DIR__ . '/view/frontend/web/js/' . $file);
        return '<script>' . $jsContent . '</script>';
    }


    /**
     * Check if minification is enabled
     *
     * @return bool|null
     */
    public function isMinifyEnabled($flag = false)
    {
        return $this->getData('minify');
    }

}
