<?php

namespace React\React;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Page\Config\Metadata\MsApplicationTileImage;
use Magento\Framework\View\Page\Config\Renderer;
use Magento\Framework\View\Page\Config;
use Magento\Store\Model\StoreManagerInterface;
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
    private $localPathCache = [];
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
        @header('x-built-with: React-Luma', false);
        $startTime = microtime(true);

        $this->objectManager = ObjectManager::getInstance();

        // Get request context
        $requestContext = $this->getRequestContext();

        // Process assets
        $assets = $this->processMerge($group->getAll(), $group);
        $attributes = $this->getGroupAttributes($group);
        $type = $group->getProperties()['content_type'] ?? 'css';

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
                if (!is_array($assets)) {
                    die('<h1>Assets should be an array disable JS merge configuration it is harmful for Magento performance!!!<h1>');
                }
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
        // header("Server-Timing: x-mag-react;dur=" . number_format($time * 1000, 2), false);
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
            'removeAdobeJSJunk' => boolval($this->template->removeAdobeJSJunk()),
            'removeCSSjunk' => boolval($this->template->removeAdobeCSSJunk()),
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
        $requestURL = $_SERVER['REQUEST_URI'] ?? '';

        @header("Action-Name: $actionName");

        $removeProtection = boolval(strpos($requestURL, 'checkout') !== false || strpos($requestURL, 'customer') !== false || $area === 'adminhtml');
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
            'assetHomeOptimized' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedProductCSSFileCriticalPath' => false,
            'optimisedCategoryCSSFileCriticalPath' => false,
            'optimisedHomeCSSFileCriticalPath' => false,
            'optimisedProductCSSFileCriticalUrl' => '',
            'optimisedCategoryCSSFileCriticalUrl' => '',
            'optimisedHomeCSSFileCriticalUrl' => ''
        ];
    }

    /**
     * Determine page types based on action name
     */
    private function determinePageTypes(string $actionName): array
    {
        return [
            'isProduct' => in_array($actionName, ['catalog_product_view']),
            'isCategory' => in_array($actionName, ['catalog_category_view', 'catalogsearch_result_index']),
            'isHome' => in_array($actionName, ['cms_index_index'])
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
     * Get store-specific static path prefix
     * Default store uses root pub/static, other stores use pub/static/{store_code}/
     *
     * @return string Path prefix (empty for default store, or '{store_code}/' for others)
     */
    private function getStoreStaticPathPrefix(): string
    {
        try {
            $storeCode = $this->store->getStore()->getCode();
            // Default store code is usually 'default' or empty
            if (empty($storeCode) || $storeCode === 'default') {
                return '';
            }
            return $storeCode . '/';
        } catch (\Exception $e) {
            // Fallback to default if store is not available
            return '';
        }
    }

    /**
     * Check if store-specific folder exists and return effective path prefix
     * If store folder doesn't exist, fallback to default store (empty prefix)
     * Uses cached directory check via checkFile()
     *
     * @param string $storePathPrefix Store-specific path prefix (e.g., 'fr/' or '')
     * @return string Effective path prefix (empty if folder doesn't exist)
     */
    private function getEffectiveStorePathPrefix(string $storePathPrefix): string
    {
        // If no store prefix, use default
        if (empty($storePathPrefix)) {
            return '';
        }
        
        // Check if store-specific folder exists using cached checkFile()
        $storeFolderPath = BP . '/pub/static/' . rtrim($storePathPrefix, '/');
        if (!$this->checkFile($storeFolderPath)) {
            // Store-specific folder doesn't exist, fallback to default store
            return '';
        }
        
        // Store-specific folder exists, use store prefix
        return $storePathPrefix;
    }

    /**
     * Process mobile CSS optimization
     */
    private function processMobileCSS(array $assets, $key, $asset, array $requestContext, array $pageTypes, string $baseURL): array
    {
        if (in_array($requestContext['actionName'], $this->actionFilter) && strpos($asset->getUrl(), 'styles-m')) {
            $this->assetVariables['assetNotOptimisedMobile'] = $asset->getUrl();

            // Get store-specific path prefix and check if folder exists (fallback to default if not)
            $storePathPrefix = $this->getStoreStaticPathPrefix();
            $effectivePathPrefix = $this->getEffectiveStorePathPrefix($storePathPrefix);
            $staticPathPrefix = BP . '/pub/static/' . $effectivePathPrefix;
            $urlPathPrefix = $effectivePathPrefix;

            // Set up optimized file paths and URLs
            $optimisedCSSFileUrl = $baseURL . $urlPathPrefix . 'styles-m.css';
            $optimisedCSSFilePath = $staticPathPrefix . 'styles-m.css';
            if ($this->isMinifyEnabled()) {
                $minifiedPath = $staticPathPrefix . 'styles-m.min.css';
                if ($this->checkFile($minifiedPath)) {
                    $optimisedCSSFileUrl = $baseURL . $urlPathPrefix . 'styles-m.min.css';
                    $optimisedCSSFilePath = $minifiedPath;
                }
            }

            // Product CSS paths
            $this->assetVariables['optimisedProductCSSFileUrl'] = $baseURL . $urlPathPrefix . 'product-styles-m.css';
            $this->assetVariables['optimisedProductCSSFileCriticalUrl'] = $baseURL . $urlPathPrefix . 'product-critical-m.css';
            $this->assetVariables['optimisedProductCSSFileCriticalPath'] = $staticPathPrefix . 'product-critical-m.css';
            $optimisedProductCSSFilePath = $staticPathPrefix . 'product-styles-m.css';

            // Category CSS paths
            $this->assetVariables['optimisedCategoryCSSFileUrl'] = $baseURL . $urlPathPrefix . 'category-styles-m.css';
            $optimisedCategoryCSSFilePath = $staticPathPrefix . 'category-styles-m.css';
            $this->assetVariables['optimisedCategoryCSSFileCriticalUrl'] = $baseURL . $urlPathPrefix . 'category-critical-m.css';
            $this->assetVariables['optimisedCategoryCSSFileCriticalPath'] = $staticPathPrefix . 'category-critical-m.css';

            // Home CSS paths
            $this->assetVariables['optimisedHomeCSSFileUrl'] = $baseURL . $urlPathPrefix . 'home-styles-m.css';
            $optimisedHomeCSSFilePath = $staticPathPrefix . 'home-styles-m.css';
            $this->assetVariables['optimisedHomeCSSFileCriticalUrl'] = $baseURL . $urlPathPrefix . 'home-critical-m.css';
            $this->assetVariables['optimisedHomeCSSFileCriticalPath'] = $staticPathPrefix . 'home-critical-m.css';

            // If minification is enabled, try minified versions and fallback to regular if not found
            if ($this->isMinifyEnabled()) {
                // Product CSS - check if minified exists, otherwise keep regular
                $minifiedProductPath = $staticPathPrefix . 'product-styles-m.min.css';
                $minifiedProductCriticalPath = $staticPathPrefix . 'product-critical-m.min.css';
                if ($this->checkFile($minifiedProductPath)) {
                    $this->assetVariables['optimisedProductCSSFileUrl'] = $baseURL . $urlPathPrefix . 'product-styles-m.min.css';
                    $optimisedProductCSSFilePath = $minifiedProductPath;
                }
                if ($this->checkFile($minifiedProductCriticalPath)) {
                    $this->assetVariables['optimisedProductCSSFileCriticalUrl'] = $baseURL . $urlPathPrefix . 'product-critical-m.min.css';
                    $this->assetVariables['optimisedProductCSSFileCriticalPath'] = $minifiedProductCriticalPath;
                }

                // Category CSS - check if minified exists, otherwise keep regular
                $minifiedCategoryPath = $staticPathPrefix . 'category-styles-m.min.css';
                $minifiedCategoryCriticalPath = $staticPathPrefix . 'category-critical-m.min.css';
                if ($this->checkFile($minifiedCategoryPath)) {
                    $this->assetVariables['optimisedCategoryCSSFileUrl'] = $baseURL . $urlPathPrefix . 'category-styles-m.min.css';
                    $optimisedCategoryCSSFilePath = $minifiedCategoryPath;
                }
                if ($this->checkFile($minifiedCategoryCriticalPath)) {
                    $this->assetVariables['optimisedCategoryCSSFileCriticalUrl'] = $baseURL . $urlPathPrefix . 'category-critical-m.min.css';
                    $this->assetVariables['optimisedCategoryCSSFileCriticalPath'] = $minifiedCategoryCriticalPath;
                }

                // Home CSS - check if minified exists, otherwise keep regular
                $minifiedHomePath = $staticPathPrefix . 'home-styles-m.min.css';
                $minifiedHomeCriticalPath = $staticPathPrefix . 'home-critical-m.min.css';
                if ($this->checkFile($minifiedHomePath)) {
                    $this->assetVariables['optimisedHomeCSSFileUrl'] = $baseURL . $urlPathPrefix . 'home-styles-m.min.css';
                    $optimisedHomeCSSFilePath = $minifiedHomePath;
                }
                if ($this->checkFile($minifiedHomeCriticalPath)) {
                    $this->assetVariables['optimisedHomeCSSFileCriticalUrl'] = $baseURL . $urlPathPrefix . 'home-critical-m.min.css';
                    $this->assetVariables['optimisedHomeCSSFileCriticalPath'] = $minifiedHomeCriticalPath;
                }
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
            if ($this->checkFile($optimisedHomeCSSFilePath) && $pageTypes['isHome']) {
                $this->assetVariables['assetHomeOptimized'] = $this->assetVariables['optimisedHomeCSSFileUrl'];
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
            $url = $asset->getUrl();
            if (strpos($url, 'js/react')) {
                unset($assets[$key]);
                if ($this->configuration['reactEnabled']) {
                    array_unshift($assets, $asset);
                }
                continue; // Skip vue check if already processed as react
            }
            if (strpos($url, 'vue')) {
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
                    // If removing junk AND action is in filter, don't add it back
                    if (!in_array($requestContext['actionName'], $this->actionFilter)) {
                        array_unshift($assets, $asset);
                    }
                } else {
                    // If not removing junk, ensure it's at the front
                    unset($assets[$key]);
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
        // Reorder RequireJS assets if they still exist (not removed by processRequireJSAssets)
        if ($this->configuration['removeAdobeJSJunk']) {
            foreach ($assets as $key => $asset) {
                $url = $asset->getUrl();
                if (strpos($url, 'require') && in_array($requestContext['actionName'], $this->actionFilter)) {
                    // RequireJS should be removed for filtered actions, skip it
                    continue;
                }
                if (strpos($url, 'require')) {
                    unset($assets[$key]);
                    array_unshift($assets, $asset);
                }
            }

            // Reorder React/Vue assets
            foreach ($assets as $key => $asset) {
                $url = $asset->getUrl();
                if (strpos($url, 'js/react') || strpos($url, 'vue')) {
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
        if ($this->assetVariables['assetOptimized'] && !($this->assetVariables['assetProductOptimized'] || $this->assetVariables['assetCategoryOptimized'] || $this->assetVariables['assetHomeOptimized'])) {
            $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->assetVariables['assetOptimized'] . '" />' . "\n" . $result;
        }

        if ($this->assetVariables['assetOptimizedLarge']) {
            $result = '<link rel="stylesheet" type="text/css" media="screen and (min-width: 768px)" href="' . $this->assetVariables['assetOptimizedLarge'] . '" />' . "\n" . $result;
        }

        // Product CSS
        $result = $this->addProductCSSLinks($result, $pageTypes);

        // Category CSS
        $result = $this->addCategoryCSSLinks($result, $pageTypes);

        // Home CSS
        $result = $this->addHomePageCSSLinks($result, $pageTypes);

        return $result;
    }

    /**
     * Generic method to add page-specific CSS links
     *
     * @param string $result Current HTML result
     * @param array $pageTypes Page type flags
     * @param string $optimizedAssetKey Key for optimized CSS asset (e.g., 'assetProductOptimized')
     * @param string $criticalPathKey Key for critical CSS file path (e.g., 'optimisedProductCSSFileCriticalPath')
     * @param string $criticalUrlKey Key for critical CSS URL (e.g., 'optimisedProductCSSFileCriticalUrl')
     * @param string $pageTypeKey Page type check key (e.g., 'isProduct')
     * @param string|null $fallbackAssetKey Optional fallback asset key (e.g., 'assetNotOptimisedMobile')
     * @param bool $addPreloadHeader Whether to add preload header for critical CSS
     * @return string Updated HTML result
     */
    private function addPageSpecificCSSLinks(
        string $result,
        array $pageTypes,
        string $optimizedAssetKey,
        string $criticalPathKey,
        string $criticalUrlKey,
        string $pageTypeKey,
        ?string $fallbackAssetKey = null,
        bool $addPreloadHeader = false
    ): string {
        $optimizedAsset = $this->assetVariables[$optimizedAssetKey] ?? false;
        $criticalPath = $this->assetVariables[$criticalPathKey] ?? false;
        $criticalUrl = $this->assetVariables[$criticalUrlKey] ?? '';

        // Early return guard clause: if page type doesn't match, return unchanged
        if (empty($pageTypes[$pageTypeKey])) {
            return $result;
        }
        
        // After early return, we know $pageTypes[$pageTypeKey] is true, so we can simplify conditions
        if ($optimizedAsset) {
            // Check if critical CSS file exists
            $criticalCSSExists = $criticalPath && $this->checkFile($criticalPath);
            
            if ($criticalCSSExists) {
                // Critical CSS exists: load critical CSS + optimized CSS with print media trick
                if ($addPreloadHeader && !$this->configuration['criticalCSSHTML']) {
                    @header('Link: <' . $criticalUrl . '>; rel=preload; as=style', false);
                    $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $criticalUrl . '" />' . "\n" . $result;
                } elseif (!$addPreloadHeader) {
                    // For category, always add critical CSS inline
                    $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $criticalUrl . '" />' . "\n" . $result;
                }
                $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';" href="' . $optimizedAsset . '" />' . "\n" . $result;
            } else {
                // Critical CSS doesn't exist: load optimized CSS as regular stylesheet (no print media trick)
                $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $optimizedAsset . '" />' . "\n" . $result;
            }
        } elseif (!$optimizedAsset) {
            // No optimized asset, but check if critical CSS exists
            if ($criticalPath && $this->checkFile($criticalPath)) {
                $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $criticalUrl . '" />' . "\n" . $result;
                if ($fallbackAssetKey && !empty($this->assetVariables[$fallbackAssetKey])) {
                    $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';" href="' . $this->assetVariables[$fallbackAssetKey] . '" />' . "\n" . $result;
                }
            }
        }

        return $result;
    }

    /**
     * Add product CSS links
     */
    private function addProductCSSLinks(string $result, array $pageTypes): string
    {
        return $this->addPageSpecificCSSLinks(
            result: $result,
            pageTypes: $pageTypes,
            optimizedAssetKey: 'assetProductOptimized',
            criticalPathKey: 'optimisedProductCSSFileCriticalPath',
            criticalUrlKey: 'optimisedProductCSSFileCriticalUrl',
            pageTypeKey: 'isProduct',
            fallbackAssetKey: 'assetNotOptimisedMobile',
            addPreloadHeader: true
        );
    }

    /**
     * Add category CSS links
     */
    private function addCategoryCSSLinks(string $result, array $pageTypes): string
    {
        return $this->addPageSpecificCSSLinks(
            result: $result,
            pageTypes: $pageTypes,
            optimizedAssetKey: 'assetCategoryOptimized',
            criticalPathKey: 'optimisedCategoryCSSFileCriticalPath',
            criticalUrlKey: 'optimisedCategoryCSSFileCriticalUrl',
            pageTypeKey: 'isCategory',
            fallbackAssetKey: 'assetNotOptimisedLarge',
            addPreloadHeader: false
        );
    }

    /**
     * Add home page CSS links
     */
    private function addHomePageCSSLinks(string $result, array $pageTypes): string
    {
        return $this->addPageSpecificCSSLinks(
            result: $result,
            pageTypes: $pageTypes,
            optimizedAssetKey: 'assetHomeOptimized',
            criticalPathKey: 'optimisedHomeCSSFileCriticalPath',
            criticalUrlKey: 'optimisedHomeCSSFileCriticalUrl',
            pageTypeKey: 'isHome',
            fallbackAssetKey: 'assetNotOptimisedMobile',
            addPreloadHeader: true
        );
    }

    /**
     * Remove Adobe JS junk
     */
    private function removeAdobeJSJunk(string $result): string
    {
        return preg_replace('/<script[^>]*require[^>]*><\/script>/', '', $result);
    }

    /**
     * Check if file or directory exists with multi-level caching
     * Supports both files and directories
     * Cache hierarchy: Local cache (request) -> APCu cache (shared) -> File system
     *
     * @param string $path File or directory path
     * @return bool True if file or directory exists
     */
    public function checkFile($path)
    {
        // Level 1: Check local cache (fastest, same request only)
        if (isset($this->localPathCache[$path])) {
            return $this->localPathCache[$path];
        }

        // Level 2: Check APCu cache (shared across requests)
        if ($this->apcuEnabled === null) {
            $this->apcuEnabled = extension_loaded('apcu') && ini_get('apc.enabled');
        }
        
        if ($this->apcuEnabled) {
            // Use fastest available hash function
            if (function_exists('hash') && in_array('xxh3', hash_algos())) {
                $cacheKey = 'path_exists_' . hash('xxh3', $path) . '_' . $this->getStaticVersion();
            } elseif (function_exists('hash') && in_array('xxh64', hash_algos())) {
                $cacheKey = 'path_exists_' . hash('xxh64', $path) . '_' . $this->getStaticVersion();
            } elseif (function_exists('hash') && in_array('crc32', hash_algos())) {
                $cacheKey = 'path_exists_' . hash('crc32', $path) . '_' . $this->getStaticVersion();
            } else {
                // Fallback to crc32() function (fastest built-in)
                $cacheKey = 'path_exists_' . crc32($path) . '_' . $this->getStaticVersion();
            }

            $cachedResult = apcu_fetch($cacheKey);

            if ($cachedResult !== false) {
                // Store in local cache for faster subsequent access
                $this->localPathCache[$path] = $cachedResult;
                return $cachedResult;
            }
        }

        // Level 3: Check file system (slowest)
        $exists = file_exists($path) || is_dir($path);
        
        // Store in local cache
        $this->localPathCache[$path] = $exists;
        
        // Store in APCu cache if available
        if ($this->apcuEnabled) {
            // Cache for 1 hour (3600 seconds) - adjust as needed
            apcu_store($cacheKey, $exists, 3600);
        }

        return $exists;
    }

    public function getStaticVersion()
    {
        if ($this->staticVersion == 0) {
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
        return $this->template->isMinifyEnabled() ?? true;
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
