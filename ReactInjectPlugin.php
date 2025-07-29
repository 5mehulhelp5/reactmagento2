<?php

namespace React\React;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Config\Metadata\MsApplicationTileImage;
use Magento\Framework\View\Page\Config\Renderer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use React\React\Template;

/**
 * Page config Renderer model Plugin
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReactInjectPlugin extends Renderer
{

    // Allowed optimisations for
    public $actionFilter = [
        'catalog_category_view',
        'cms_index_index',
        'cms_page_view',
        'catalog_product_view',
        'catalogsearch_result_index',
        'cms_noroute_index',
        'customer_account_login',
        'customer_account_create'
    ];

    public $staticVersion = 0;
    private $apcuEnabled = null;
    private $assetVariables = [];
    private $objectManager = null;
    private $configuration = [];

    /**
     * ReactInjectPlugin constructor
     *
     * @param Config $pageConfig
     * @param \Magento\Framework\View\Asset\MergeService $assetMergeService
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Psr\Log\LoggerInterface $logger
     * @param MsApplicationTileImage|null $msApplicationTileImage
     * @param ScopeConfigInterface $config
     * @param State $state
     * @param StoreManagerInterface $store
     * @param Template $template
     */
    public function __construct(
        Config $pageConfig,
        \Magento\Framework\View\Asset\MergeService $assetMergeService,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Psr\Log\LoggerInterface $logger,
        MsApplicationTileImage $msApplicationTileImage = null,
        private ScopeConfigInterface $config,
        private State $state,
        private StoreManagerInterface $store,
        private Template $template
    ) {
        parent::__construct($pageConfig, $assetMergeService, $urlBuilder, $escaper, $string, $logger, $msApplicationTileImage);
        $this->configuration = $this->getConfigurationSettings();
    }

    /**
     * Render HTML tags referencing corresponding URLs
     *
     * @param \Magento\Framework\View\Asset\PropertyGroup $group
     * @return string
     */
    protected function renderAssetHtml(\Magento\Framework\View\Asset\PropertyGroup $group)
    {
        @header("x-built-with: Ract-Luma", false);
        $startTime = microtime(true);

        $this->objectManager = ObjectManager::getInstance();
        
        // Get request context
        $requestContext = $this->getRequestContext();
        
        // Process assets
        $assets = $this->processMerge($group->getAll(), $group);
        $attributes = $this->getGroupAttributes($group);
        $type = $group->getProperties()['content_type'];
        
        // Initialize asset variables
        $this->initializeAssetVariables();
        
        // Determine page types
        $pageTypes = $this->determinePageTypes($requestContext['actionName']);
        
        try {
            // Process CSS optimization
            if ($this->configuration['removeCSSjunk'] && $type === 'css') {
                $assets = $this->processCSSOptimization($assets, $requestContext, $pageTypes);
            }
            
            // Process JavaScript optimization
            if ($type === 'js') {
                $assets = $this->processJavaScriptOptimization($assets, $requestContext);
            }
            
            // Generate HTML result
            $result = $this->generateAssetHtml($assets, $group, $attributes);
            
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            $result = $this->generateErrorHtml($attributes);
        }

        // Add optimized CSS links
        if ($this->configuration['removeCSSjunk']) {
            $result = $this->addOptimizedCSSLinks($result, $pageTypes);
        }

        // Remove Adobe JS junk
        if ($this->configuration['removeAdobeJSJunk'] && $type === 'js' && $requestContext['removeController']) {
            $result = $this->removeAdobeJSJunk($result);
        }
        
        $endTime = microtime(true);
        $time = $endTime - $startTime;
        //header("Server-Timing: x-mag-react;dur=" . number_format($time * 1000, 2), false);
        return $result;
    }

    /**
     * Get configuration settings from config
     */
    private function getConfigurationSettings(): array
    {
        return [
            'reactEnabled' => boolval($this->config->getValue('react_vue_config/react/enable')),
            'vueEnabled' => boolval($this->config->getValue('react_vue_config/vue/enable')),
            'removeAdobeJSJunk' => boolval($this->config->getValue('react_vue_config/junk/remove')),
            'removeCSSjunk' => boolval($this->config->getValue('react_vue_config/css/remove')),
            'criticalCSSHTML' => boolval($this->config->getValue('react_vue_config/css/critical'))
        ];
    }

    /**
     * Get request context information
     */
    private function getRequestContext(): array
    {
        $area = $this->state->getAreaCode();
        $request = $this->objectManager->get(\Magento\Framework\App\Request\Http::class);
        $actionName = $request->getFullActionName();
        $requestURL = $_SERVER['REQUEST_URI'];
        
        @header("Action-Name: $actionName");
        
        $removeProtection = boolval(boolval(strpos($requestURL, 'checkout')) || boolval(strpos($requestURL, 'customer')) || $area === 'adminhtml');
        @header("React-Protection: $removeProtection");
        
        $removeController = in_array($actionName, $this->actionFilter);
        
        return [
            'actionName' => $actionName,
            'removeProtection' => $removeProtection,
            'removeController' => $removeController,
            'area' => $area
        ];
    }

    /**
     * Initialize asset variables
     */
    private function initializeAssetVariables(): void
    {
        $this->assetVariables = [
            'assetOptimized' => false,
            'assetOptimizedLarge' => false,
            'assetProductOptimized' => false,
            'assetCategoryOptimized' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedProductCSSFileCriticalPath' => false,
            'optimisedCategoryCSSFileCriticalPath' => false,
            'optimisedProductCSSFileCriticalUrl' => '',
            'optimisedCategoryCSSFileCriticalUrl' => ''
        ];
    }

    /**
     * Determine page types based on action name
     */
    private function determinePageTypes(string $actionName): array
    {
        return [
            'isProduct' => in_array($actionName, ['catalog_product_view']),
            'isCategory' => in_array($actionName, ['catalog_category_view', 'catalogsearch_result_index'])
        ];
    }

    /**
     * Process CSS optimization
     */
    private function processCSSOptimization(array $assets, array $requestContext, array $pageTypes): array
    {
        $baseURL = $this->store->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);
        
        foreach ($assets as $key => $asset) {
            $assets = $this->processMobileCSS($assets, $key, $asset, $requestContext, $pageTypes, $baseURL);
            $assets = $this->processLargeCSS($assets, $key, $asset, $requestContext, $baseURL);
            $assets = $this->removeUnwantedCSS($assets, $key, $asset, $requestContext);
        }
        
        return $assets;
    }

    /**
     * Process mobile CSS optimization
     */
    private function processMobileCSS(array $assets, $key, $asset, array $requestContext, array $pageTypes, string $baseURL): array
    {
        if (in_array($requestContext['actionName'], $this->actionFilter) && strpos($asset->getUrl(), 'styles-m')) {
            $this->assetVariables['assetNotOptimisedMobile'] = $asset->getUrl();
            
            // Set up optimized file paths and URLs
            $optimisedCSSFileUrl = $baseURL . 'styles-m.css';
            if ($this->isMinifyEnabled()) {
                $optimisedCSSFileUrl = $baseURL . 'styles-m.min.css';
            }
            $optimisedCSSFilePath = BP . '/pub/static/styles-m.css';
            
            $this->assetVariables['optimisedProductCSSFileUrl'] = $baseURL . 'product-styles-m.css';
            $this->assetVariables['optimisedProductCSSFileCriticalUrl'] = $baseURL . 'product-critical-m.css';
            $this->assetVariables['optimisedProductCSSFileCriticalPath'] = BP . '/pub/static/product-critical-m.css';
            $optimisedProductCSSFilePath = BP . '/pub/static/product-styles-m.css';
            
            $this->assetVariables['optimisedCategoryCSSFileUrl'] = $baseURL . 'category-styles-m.css';
            $optimisedCategoryCSSFilePath = BP . '/pub/static/category-styles-m.css';
            $this->assetVariables['optimisedCategoryCSSFileCriticalUrl'] = $baseURL . 'category-critical-m.css';
            $this->assetVariables['optimisedCategoryCSSFileCriticalPath'] = BP . '/pub/static/category-critical-m.css';

            if ($this->isMinifyEnabled()) {
                $this->assetVariables['optimisedProductCSSFileUrl'] = $baseURL . 'product-styles-m.min.css';
                $this->assetVariables['optimisedProductCSSFileCriticalUrl'] = $baseURL . 'product-critical-m.min.css';
                $this->assetVariables['optimisedCategoryCSSFileUrl'] = $baseURL . 'category-styles-m.css';
                $this->assetVariables['optimisedCategoryCSSFileCriticalUrl'] = $baseURL . 'category-critical-m.css'; 
            }
            
            // Check and set optimized assets
            if ($this->checkFile($optimisedCSSFilePath)) {
                $this->assetVariables['assetOptimized'] = $optimisedCSSFileUrl;
                unset($assets[$key]);
            }
            if ($this->checkFile($optimisedProductCSSFilePath) && $pageTypes['isProduct']) {
                $this->assetVariables['assetProductOptimized'] = $this->assetVariables['optimisedProductCSSFileUrl'];
                unset($assets[$key]);
            }
            if ($this->checkFile($optimisedCategoryCSSFilePath) && $pageTypes['isCategory']) {
                $this->assetVariables['assetCategoryOptimized'] = $this->assetVariables['optimisedCategoryCSSFileUrl'];
                unset($assets[$key]);
            }
        }
        
        return $assets;
    }

    /**
     * Process large CSS optimization
     */
    private function processLargeCSS(array $assets, $key, $asset, array $requestContext, string $baseURL): array
    {
        if (in_array($requestContext['actionName'], $this->actionFilter) && strpos($asset->getUrl(), 'styles-l')) {
            $optimisedCSSFileUrlLarge = $baseURL . 'styles-l.css';
            if ($this->isMinifyEnabled()) {
                $optimisedCSSFileUrlLarge = $baseURL . 'styles-l.min.css';
            }
            $optimisedCSSFilePathLarge = BP . '/pub/static/styles-l.css';
            $this->assetVariables['assetNotOptimisedLarge'] = $asset->getUrl();
            
            if ($this->checkFile($optimisedCSSFilePathLarge)) {
                $this->assetVariables['assetOptimizedLarge'] = $optimisedCSSFileUrlLarge;
                unset($assets[$key]);
            } else {
                @header('Optimised-CSS: false');
            }
        }
        
        return $assets;
    }

    /**
     * Remove unwanted CSS files
     */
    private function removeUnwantedCSS(array $assets, $key, $asset, array $requestContext): array
    {
        $unwantedFiles = ['calendar', 'gallery', 'uppy-custom'];
        
        foreach ($unwantedFiles as $unwanted) {
            if (in_array($requestContext['actionName'], $this->actionFilter) && strpos($asset->getUrl(), $unwanted)) {
                unset($assets[$key]);
                break;
            }
        }
        
        return $assets;
    }

    /**
     * Process JavaScript optimization
     */
    private function processJavaScriptOptimization(array $assets, array $requestContext): array
    {
        if ($this->configuration['removeAdobeJSJunk']) {
            $assets = $this->processReactVueAssets($assets, $requestContext);
            $assets = $this->processRequireJSAssets($assets, $requestContext);
        }
        
        // Reorder assets for proper script loading
        $assets = $this->reorderAssets($assets, $requestContext);
        
        return $assets;
    }

    /**
     * Process React and Vue assets
     */
    private function processReactVueAssets(array $assets, array $requestContext): array
    {
        foreach ($assets as $key => $asset) {
            if (strpos($asset->getUrl(), 'js/react')) {
                unset($assets[$key]);
                if ($this->configuration['reactEnabled']) {
                    array_unshift($assets, $asset);
                }
            }
            if (strpos($asset->getUrl(), 'vue')) {
                unset($assets[$key]);
                if ($this->configuration['vueEnabled']) {
                    array_unshift($assets, $asset);
                }
            }
        }
        
        return $assets;
    }

    /**
     * Process RequireJS assets
     */
    private function processRequireJSAssets(array $assets, array $requestContext): array
    {
        foreach ($assets as $key => $asset) {
            if (strpos($asset->getUrl(), 'require')) {
                if ($this->configuration['removeAdobeJSJunk']) {
                    unset($assets[$key]);
                }
                
                if (!$this->configuration['removeAdobeJSJunk'] || !in_array($requestContext['actionName'], $this->actionFilter)) {
                    array_unshift($assets, $asset);
                }
            }
        }
        
        return $assets;
    }

    /**
     * Reorder assets for proper loading
     */
    private function reorderAssets(array $assets, array $requestContext): array
    {
        // Execute one more time to make scripts the same order
        foreach ($assets as $key => $asset) {
            if (strpos($asset->getUrl(), 'require') && $this->configuration['removeAdobeJSJunk']) {
                unset($assets[$key]);
                array_unshift($assets, $asset);
            }
        }
        
        if ($this->configuration['removeAdobeJSJunk']) {
            foreach ($assets as $key => $asset) {
                if (strpos($asset->getUrl(), 'js/react') || strpos($asset->getUrl(), 'vue')) {
                    unset($assets[$key]);
                    array_unshift($assets, $asset);
                }
            }
        }
        
        return $assets;
    }

    /**
     * Generate asset HTML
     */
    private function generateAssetHtml(array $assets, $group, ?string $attributes): string
    {
        $result = '';
        $attributes = $attributes ?? '';
        
        foreach ($assets as $asset) {
            $template = $this->getAssetTemplate(
                $group->getProperty(GroupedCollection::PROPERTY_CONTENT_TYPE),
                $this->addDefaultAttributes($this->getAssetContentType($asset), $attributes)
            );
            $result .= sprintf($template, $asset->getUrl());
        }
        
        return $result;
    }

    /**
     * Generate error HTML
     */
    private function generateErrorHtml(?string $attributes): string
    {
        $attributes = $attributes ?? '';
        $template = $this->getAssetTemplate('js', $attributes);
        return sprintf($template, $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']));
    }

    /**
     * Add optimized CSS links
     */
    private function addOptimizedCSSLinks(string $result, array $pageTypes): string
    {
        // Mobile CSS
        if ($this->assetVariables['assetOptimized'] && !($this->assetVariables['assetProductOptimized'] || $this->assetVariables['assetCategoryOptimized'])) {
            $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->assetVariables['assetOptimized'] . '" />' . "\n" . $result;
        }
        
        if ($this->assetVariables['assetOptimizedLarge']) {
            $result = '<link rel="stylesheet" type="text/css" media="screen and (min-width: 768px)" href="' . $this->assetVariables['assetOptimizedLarge'] . '" />' . "\n" . $result;
        }
        
        // Product CSS
        $result = $this->addProductCSSLinks($result, $pageTypes);
        
        // Category CSS
        $result = $this->addCategoryCSSLinks($result, $pageTypes);
        
        return $result;
    }

    /**
     * Add product CSS links
     */
    private function addProductCSSLinks(string $result, array $pageTypes): string
    {
        if ($this->assetVariables['assetProductOptimized'] && $pageTypes['isProduct']) {
            if ($this->assetVariables['optimisedProductCSSFileCriticalPath'] && $this->checkFile($this->assetVariables['optimisedProductCSSFileCriticalPath'])) {
                if (!$this->configuration['criticalCSSHTML']) {
                    @header("Link: <" . $this->assetVariables['optimisedProductCSSFileCriticalUrl'] . ">; rel=preload; as=style", false);
                    $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->assetVariables['optimisedProductCSSFileCriticalUrl'] . '" />' . "\n" . $result;
                }
                $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';" href="' . $this->assetVariables['assetProductOptimized'] . '" />' . "\n" . $result;
            } else {
                $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->assetVariables['assetProductOptimized'] . '" />' . "\n" . $result;
            }
        } else if (!$this->assetVariables['assetProductOptimized'] && $pageTypes['isProduct']) {
            if ($this->assetVariables['optimisedProductCSSFileCriticalPath'] && $this->checkFile($this->assetVariables['optimisedProductCSSFileCriticalPath'])) {
                $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->assetVariables['optimisedProductCSSFileCriticalUrl'] . '" />' . "\n" . $result;
                $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';" href="' . $this->assetVariables['assetNotOptimisedMobile'] . '" />' . "\n" . $result;
            }
        }
        
        return $result;
    }

    /**
     * Add category CSS links
     */
    private function addCategoryCSSLinks(string $result, array $pageTypes): string
    {
        if ($this->assetVariables['assetCategoryOptimized'] && $pageTypes['isCategory']) {
            if ($this->assetVariables['optimisedCategoryCSSFileCriticalPath'] && $this->checkFile($this->assetVariables['optimisedCategoryCSSFileCriticalPath'])) {
                $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->assetVariables['optimisedCategoryCSSFileCriticalUrl'] . '" />' . "\n" . $result;
                $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';" href="' . $this->assetVariables['assetCategoryOptimized'] . '" />' . "\n" . $result;
            } else {
                $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->assetVariables['assetCategoryOptimized'] . '" />' . "\n" . $result;
            }
        } else if (!$this->assetVariables['assetCategoryOptimized'] && $pageTypes['isCategory']) {
            if ($this->assetVariables['optimisedCategoryCSSFileCriticalPath'] && $this->checkFile($this->assetVariables['optimisedCategoryCSSFileCriticalPath'])) {
                $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->assetVariables['optimisedCategoryCSSFileCriticalUrl'] . '" />' . "\n" . $result;
                $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';" href="' . $this->assetVariables['assetNotOptimisedLarge'] . '" />' . "\n" . $result;
            }
        }
        
        return $result;
    }

    /**
     * Remove Adobe JS junk
     */
    private function removeAdobeJSJunk(string $result): string
    {
        return preg_replace('/<script[^>]*require[^>]*><\/script>/', '', $result);
    }

    public function checkFile($file)
    {
        // APCu cache optimization for file existence checks
        if ($this->apcuEnabled === null) {
            $this->apcuEnabled = extension_loaded('apcu') && ini_get('apc.enabled');
        }
        if ($this->apcuEnabled) {
            // Use fastest available hash function
            if (function_exists('hash') && in_array('xxh3', hash_algos())) {
                $cacheKey = 'file_exists_' . hash('xxh3', $file) . '_' . $this->getStaticVersion();
            } elseif (function_exists('hash') && in_array('xxh64', hash_algos())) {
                $cacheKey = 'file_exists_' . hash('xxh64', $file) . '_' . $this->getStaticVersion();
            } elseif (function_exists('hash') && in_array('crc32', hash_algos())) {
                $cacheKey = 'file_exists_' . hash('crc32', $file) . '_' . $this->getStaticVersion();
            } else {
                // Fallback to crc32() function (fastest built-in)
                $cacheKey = 'file_exists_' . crc32($file) . '_' . $this->getStaticVersion();
            }
            
            $cachedResult = apcu_fetch($cacheKey);
            
            if ($cachedResult !== false) {
                return $cachedResult;
            }
            
            $exists = file_exists($file);
            // Cache for 1 hour (3600 seconds) - adjust as needed
            apcu_store($cacheKey, $exists, 3600);
            
            return $exists;
        }
        
        // Fallback to direct file_exists if APCu is not available
        return file_exists($file);
    }

    public function getStaticVersion(){
        if($this->staticVersion == 0){
            $this->staticVersion = @file_get_contents(BP . '/pub/static/deployed_version.txt');
        }
        return $this->staticVersion;
    }

    /**
     * Check if minification is enabled via template
     * 
     * For now inclides files with .min instead of main css files
     *
     * @return bool
     */
    public function isMinifyEnabled(): bool
    {
        return $this->template->isMinifyEnabled();
    }

/*
 * Alternative
 *  protected function getIncludes()
 *  {
 *      $html = parent::getIncludes();
 *
 *      // Remove all `<script type="text/x-magento-init">` blocks
 *      $html = preg_replace('/<script[^>]+type=["\']text\/x-magento-init["\'][^>]*>.*?<\/script>/s', '', $html);
 *
 *      return $html;
 *  }
 */
}
