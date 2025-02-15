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
    ];

    /**
     * @param Config $pageConfig
     * @param \Magento\Framework\View\Asset\MergeService $assetMergeService
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Psr\Log\LoggerInterface $logger
     * @param MsApplicationTileImage|null $msApplicationTileImage
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
        private StoreManagerInterface $store
    ) {
        parent::__construct($pageConfig, $assetMergeService, $urlBuilder, $escaper, $string, $logger, $msApplicationTileImage);
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

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $reactEnabled = boolval($this->config->getValue('react_vue_config/react/enable'));
        $vueEnabled = boolval($this->config->getValue('react_vue_config/vue/enable'));
        /* remove default Magento Junky JS */
        $removeAdobeJSJunk = boolval($this->config->getValue('react_vue_config/junk/remove'));
        $removeCSSjunk = boolval($this->config->getValue('react_vue_config/css/remove'));
        $criticalCSSHTML = boolval($this->config->getValue('react_vue_config/css/critical'));

        if (isset($_GET['css-react']) && $_GET['css-react'] === "false") {
            $removeCSSjunk = false;
        }
        if (isset($_GET['css-react']) && $_GET['css-react'] === "true") {
            $removeCSSjunk = true;
        }

        $area = $this->state->getAreaCode();
        $pageFilter = ['checkout', 'customer'];

        $request = $objectManager->get(\Magento\Framework\App\Request\Http::class);
        $actionName = $request->getFullActionName();
        @header("Action-Name: $actionName");
        $requestURL = $_SERVER['REQUEST_URI'];
        $removeProtection = boolval(boolval(strpos($requestURL, 'checkout')) || boolval(strpos($requestURL, 'customer')) || $area === 'adminhtml');
        @header("React-Protection: $removeProtection");
        $block = $objectManager->get(\Magento\Framework\View\Element\Template::class);
        $assets = $this->processMerge($group->getAll(), $group);
        $attributes = $this->getGroupAttributes($group);
        $type = $group->getProperties()['content_type'];
        $result = '';
        $template = '';
        $assetOptimized = false;
        $assetOptimizedLarge = false;
        $assetProductOptimized = false;
        $assetCategoryOptimized = false;
        $assetNotOptimisedMobile = false;
        $optimisedProductCSSFileCriticalPath = false;
        $optimisedCategoryCSSFileCriticalPath = false;

        $removeController = in_array($actionName, $this->actionFilter);
        $isProduct = in_array($actionName, ['catalog_product_view']);
        $isCategory = in_array($actionName, ['catalog_category_view', 'catalogsearch_result_index']);

        try {
            /** @var $asset \Magento\Framework\View\Asset\AssetInterface */
            // Changes Start
            $baseURL = $this->store->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);
            if ($removeCSSjunk && $type === 'css') {
                foreach ($assets as $key => $asset) {
                    if (in_array($actionName, $this->actionFilter) && strpos($asset->getUrl(), 'styles-m')) {
                        // http://**/static/version1642788857/frontend/Magento/luma/en_US/css/styles-m.css
                        $assetNotOptimisedMobile = $asset->getUrl();
                        $optimisedCSSFileUrl = $baseURL . 'styles-m.css';
                        $optimisedCSSFilePath = BP . '/pub/static/styles-m.css';

                        $optimisedProductCSSFileUrl = $baseURL . 'product-styles-m.css';
                        $optimisedProductCSSFileCriticalUrl = $baseURL . 'product-critical-m.css';
                        $optimisedProductCSSFileCriticalPath = BP . '/pub/static/product-critical-m.css';
                        $optimisedProductCSSFilePath = BP . '/pub/static/product-styles-m.css';

                        $optimisedCategoryCSSFileUrl = $baseURL . 'category-styles-m.css';
                        $optimisedCategoryCSSFilePath = BP . '/pub/static/category-styles-m.css';
                        $optimisedCategoryCSSFileCriticalUrl = $baseURL . 'category-critical-m.css';
                        $optimisedCategoryCSSFileCriticalPath = BP . '/pub/static/category-critical-m.css';

                        if (file_exists($optimisedCSSFilePath)) {
                            // echo $optimisedCSSFileUrl;
                            $assetOptimized = $optimisedCSSFileUrl;
                            unset($assets[$key]);
                        }
                        if (file_exists($optimisedProductCSSFilePath) && $isProduct) {
                            // echo $optimisedCSSFileUrl;
                            $assetProductOptimized = $optimisedProductCSSFileUrl;
                            unset($assets[$key]);
                        }
                        if (file_exists($optimisedCategoryCSSFilePath) && $isCategory) {
                            // echo $optimisedCSSFileUrl;
                            $assetCategoryOptimized = $optimisedCategoryCSSFileUrl;
                            unset($assets[$key]);
                        }

                    }
                    if (in_array($actionName, $this->actionFilter) && strpos($asset->getUrl(), 'styles-l')) {
                        // http://**/static/version1642788857/frontend/Magento/luma/en_US/css/styles-l.css
                        $optimisedCSSFileUrlLarge = $baseURL . 'styles-l.css';
                        $optimisedCSSFilePathLarge = BP . '/pub/static/styles-l.css';
                        $assetNotOptimisedLarge = $asset->getUrl();
                        if (file_exists($optimisedCSSFilePathLarge)) {
                            // echo $optimisedCSSFileUrl;
                            $assetOptimizedLarge = $optimisedCSSFileUrlLarge;
                            unset($assets[$key]);
                        } else {
                            @header('Optimised-CSS: false');
                        }
                    }
                    if (in_array($actionName, $this->actionFilter) && strpos($asset->getUrl(), 'calendar')) {
                        unset($assets[$key]);
                    }
                    if (in_array($actionName, $this->actionFilter) && strpos($asset->getUrl(), 'gallery')) {
                        unset($assets[$key]);
                    }
                    if (in_array($actionName, $this->actionFilter) && strpos($asset->getUrl(), 'uppy-custom')) {
                        unset($assets[$key]);
                    }
                }
            }
            // dump($assets);
            foreach ($assets as $key => $asset) {
                if ($type === 'js' && $removeAdobeJSJunk) {
                    if (strpos($asset->getUrl(), 'js/react')) {
                        unset($assets[$key]);
                        if ($reactEnabled) {
                            array_unshift($assets, $asset);
                        }
                    }
                    if (strpos($asset->getUrl(), 'vue')) {
                        unset($assets[$key]);
                        if ($vueEnabled) {
                            array_unshift($assets, $asset);
                        }
                    }
                    if (strpos($asset->getUrl(), 'require')) {
                        if ($removeAdobeJSJunk)
                        //dd($removeAdobeJSJunk);
                        {
                            unset($assets[$key]);
                        }

                        // junk True ; protection False
                        // echo "require " . (string) $removeProtection;
                        if (!$removeAdobeJSJunk || !in_array($actionName, $this->actionFilter));
                        array_unshift($assets, $asset);
                    }
                    if (strpos($asset->getUrl(), 'require')) {
                        if ($removeAdobeJSJunk)
                        //dd($removeAdobeJSJunk);
                        {
                            unset($assets[$key]);
                        }

                        // junk True ; protection False
                        // echo "require " . (string) $removeProtection;
                        if (!$removeAdobeJSJunk || !in_array($actionName, $this->actionFilter));
                        array_unshift($assets, $asset);
                    }
                }
                if ($type === 'css') {
                    if (strpos($asset->getUrl(), 'styles-')) {
                        unset($assets[$key]);
                        if (!$removeCSSjunk || !in_array($actionName, $this->actionFilter)) {
                            array_unshift($assets, $asset);
                        }
                    }
                }
            }
            // we need execute it one more time to make scripts the same order
            foreach ($assets as $key => $asset) {
                if (strpos($asset->getUrl(), 'require') && $removeAdobeJSJunk) {
                    unset($assets[$key]);
                    array_unshift($assets, $asset);
                    // dd($assets);
                }
            }

            if ($type === 'js' && $removeAdobeJSJunk) {
                foreach ($assets as $key => $asset) {
                    if (strpos($asset->getUrl(), 'js/react') || strpos($asset->getUrl(), 'vue')) {
                        unset($assets[$key]);
                        array_unshift($assets, $asset);
                    }
                }
            }

            // Changes Ends

            foreach ($assets as $asset) {
                $template = $this->getAssetTemplate(
                    $group->getProperty(GroupedCollection::PROPERTY_CONTENT_TYPE),
                    $this->addDefaultAttributes($this->getAssetContentType($asset), $attributes)
                );
                $result .= sprintf($template, $asset->getUrl());
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            $result .= sprintf($template, $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']));
        }

        if ($removeCSSjunk) {
            // mobile CSS
            if ($assetOptimized && !($assetProductOptimized || $assetCategoryOptimized)) {
                $result = '<link  rel="stylesheet" type="text/css"  media="all" href="' . $assetOptimized . '" />' . "\n" . $result;
            }
            if ($assetOptimizedLarge) {
                $result = '<link  rel="stylesheet" type="text/css"  media="screen and (min-width: 768px)" href="' . $assetOptimizedLarge . '" />' . "\n" . $result;
            }
            if ($assetProductOptimized && $isProduct) {
                if ($optimisedProductCSSFileCriticalPath && file_exists($optimisedProductCSSFileCriticalPath)) {
                    if (!$criticalCSSHTML) {
                        // ToDo: check if push works
                        @header("Link: <" . $optimisedProductCSSFileCriticalUrl . ">; rel=preload; as=style", false);
                        $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $optimisedProductCSSFileCriticalUrl . '" />' . "\n" . $result;
                    }
                    $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';"  href="' . $assetProductOptimized . '" />' . "\n" . $result;
                } else {
                    $result = '<link  rel="stylesheet" type="text/css" media="all" href="' . $assetProductOptimized . '" />' . "\n" . $result;
                }
            } else if (!$assetProductOptimized && $isProduct) {
                if ($optimisedProductCSSFileCriticalPath && file_exists($optimisedProductCSSFileCriticalPath)) {
                    $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $optimisedProductCSSFileCriticalUrl . '" />' . "\n" . $result;
                    $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';"  href="' . $assetNotOptimisedMobile . '" />' . "\n" . $result;
                }
            }
            if ($assetCategoryOptimized && $isCategory) {
                if ($optimisedCategoryCSSFileCriticalPath && file_exists($optimisedCategoryCSSFileCriticalPath)) {
                    $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $optimisedCategoryCSSFileCriticalUrl . '" />' . "\n" . $result;
                    $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';"  href="' . $assetCategoryOptimized . '" />' . "\n" . $result;
                } else {
                    $result = '<link  rel="stylesheet" type="text/css" media="all" href="' . $assetCategoryOptimized . '" />' . "\n" . $result;
                }
            } else if (!$assetCategoryOptimized && $isCategory) {
                if ($optimisedCategoryCSSFileCriticalPath && file_exists($optimisedCategoryCSSFileCriticalPath)) {
                    $result = '<link rel="stylesheet" type="text/css" media="all" href="' . $optimisedProductCSSFileCriticalUrl . '" />' . "\n" . $result;
                    $result = '<link rel="stylesheet" media="print" onload="this.onload=null;this.media=\'all\';"  href="' . $$assetNotOptimisedLarge . '" />' . "\n" . $result;
                }
            }
        }

        if ($removeAdobeJSJunk && $type === 'js' && $removeController) {
            // dd($result);
            // Remove RequireJS and other scripts if needed
            $result = preg_replace('/<script[^>]*require[^>]*><\/script>/', '', $result);
        }
        $endTime = microtime(true);
        $time = $endTime - $startTime;
        //header("Server-Timing: x-mag-react;dur=" . number_format($time * 1000, 2), false);
        return $result;
    }

    public function checkFile($file)
    {
        // TODO: add APCu or file cache optimisation
        return file_exists($file);
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
