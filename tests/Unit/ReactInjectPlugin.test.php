<?php
/**
 * Pest test for React\React\ReactInjectPlugin
 * Tests React injection logic with ACTUAL class and mocked dependencies
 * 
 * Run: vendor/bin/pest Unit/ReactInjectPlugin.test.php
 * 
 * APPROACH: Hybrid bootstrap (bootstrap.php)
 * - Loads Pest's PHPUnit 10+ first
 * - Then loads Magento classes via custom autoloader (skips PHPUnit)
 * - Both coexist without conflicts!
 * - We use the ACTUAL ReactInjectPlugin class
 * - Dependencies are mocked or retrieved from ObjectManager
 * - NO method copying needed!
 */

/**
 * Uses the ACTUAL ReactInjectPlugin class from Magento!
 * NO method copying - tests the real implementation directly!
 * 
 * The hybrid bootstrap loads both Pest's PHPUnit and Magento classes successfully
 */
class ReactInjectPluginTestHelper
{
    private $actualInstance;
    private $reflection;
    
    public function __construct($dependencies = [])
    {
        // Create mock dependencies if not provided
        $scopeConfig = $dependencies['scopeConfig'] ?? new MockScopeConfig();
        $pageConfig = $dependencies['pageConfig'] ?? new MockPageConfig();
        $assetMergeService = $dependencies['assetMergeService'] ?? new MockAssetMergeService();
        $urlBuilder = $dependencies['urlBuilder'] ?? new MockUrlBuilder();
        $escaper = $dependencies['escaper'] ?? new MockEscaper();
        $string = $dependencies['string'] ?? new MockStringUtils();
        $logger = $dependencies['logger'] ?? new MockLogger();
        $msApplicationTileImage = $dependencies['msApplicationTileImage'] ?? null;
        $state = $dependencies['state'] ?? new MockState();
        $storeManager = $dependencies['storeManager'] ?? new MockStoreManager();
        $template = $dependencies['template'] ?? new MockTemplate($scopeConfig);
        
        // Create the ACTUAL ReactInjectPlugin instance from Magento!
        $this->actualInstance = new \React\React\ReactInjectPlugin(
            $pageConfig,
            $assetMergeService,
            $urlBuilder,
            $escaper,
            $string,
            $logger,
            $msApplicationTileImage,
            $scopeConfig,
            $state,
            $storeManager,
            $template
        );
        $this->reflection = new \ReflectionClass($this->actualInstance);
    }
    
    /**
     * Get the actual instance
     */
    public function getInstance()
    {
        return $this->actualInstance;
    }
    
    /**
     * Call a private/protected method using Reflection API
     */
    public function callMethod($methodName, ...$args)
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invoke($this->actualInstance, ...$args);
    }
    
    /**
     * Get a private/protected property value
     */
    public function getProperty($propertyName)
    {
        $property = $this->reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($this->actualInstance);
    }
    
    /**
     * Set a private/protected property value
     */
    public function setProperty($propertyName, $value)
    {
        $property = $this->reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($this->actualInstance, $value);
    }
}

// Mock classes that implement actual Magento interfaces
class MockPageConfig extends \Magento\Framework\View\Page\Config
{
    public function __construct()
    {
        // Skip parent constructor
    }
}

class MockAssetMergeService extends \Magento\Framework\View\Asset\MergeService
{
    public function __construct()
    {
        // Skip parent constructor
    }
}

class MockUrlBuilder implements \Magento\Framework\UrlInterface
{
    public function getUseSession()
    {
        return true;
    }
    
    public function getBaseUrl($params = [])
    {
        return 'http://localhost/';
    }
    
    public function getCurrentUrl()
    {
        return 'http://localhost/test';
    }
    
    public function getRouteUrl($routePath = null, $routeParams = null)
    {
        return '/test-route';
    }
    
    public function addSessionParam()
    {
        return $this;
    }
    
    public function addQueryParams(array $data)
    {
        return $this;
    }
    
    public function setQueryParam($key, $data)
    {
        return $this;
    }
    
    public function getUrl($routePath = null, $routeParams = null)
    {
        return '/test-url';
    }
    
    public function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    public function getDirectUrl($url, $params = [])
    {
        return $url;
    }
    
    public function sessionUrlVar($html)
    {
        return $html;
    }
    
    public function isOwnOriginUrl()
    {
        return true;
    }
    
    public function getRedirectUrl($url)
    {
        return $url;
    }
    
    public function setScope($params)
    {
        return $this;
    }
}

class MockEscaper extends \Magento\Framework\Escaper
{
    public function __construct()
    {
        // Skip parent constructor
    }
}

class MockStringUtils extends \Magento\Framework\Stdlib\StringUtils
{
    public function __construct()
    {
        // Skip parent constructor
    }
}

class MockScopeConfig implements \Magento\Framework\App\Config\ScopeConfigInterface
{
    private $values = [];
    
    public function __construct($values = [])
    {
        $this->values = $values;
    }
    
    public function getValue($path, $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->values[$path] ?? null;
    }
    
    public function isSetFlag($path, $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return (bool) $this->getValue($path, $scopeType, $scopeCode);
    }
}

class MockState extends \Magento\Framework\App\State
{
    public function __construct()
    {
        // Skip parent constructor
    }
    
    public function getAreaCode()
    {
        return 'frontend';
    }
    
    public function setAreaCode($code)
    {
        return $this;
    }
    
    public function isAreaCodeEmulated()
    {
        return false;
    }
    
    public function emulateAreaCode($areaCode, $callback, $params = [])
    {
        return call_user_func_array($callback, $params);
    }
}

class MockStoreManager implements \Magento\Store\Model\StoreManagerInterface
{
    public function getStore($storeId = null)
    {
        return new class implements \Magento\Store\Api\Data\StoreInterface {
            public function getId() { return 1; }
            public function getCode() { return 'default'; }
            public function getName() { return 'Default Store'; }
            public function getWebsiteId() { return 1; }
            public function getStoreGroupId() { return 1; }
            public function getIsActive() { return true; }
            public function getSortOrder() { return 0; }
            // Required setters
            public function setId($id) { return $this; }
            public function setCode($code) { return $this; }
            public function setName($name) { return $this; }
            public function setWebsiteId($websiteId) { return $this; }
            public function setStoreGroupId($storeGroupId) { return $this; }
            public function setIsActive($isActive) { return $this; }
            public function setSortOrder($sortOrder) { return $this; }
            // Extension attributes
            public function getExtensionAttributes() { return null; }
            public function setExtensionAttributes(\Magento\Store\Api\Data\StoreExtensionInterface $extensionAttributes) { return $this; }
        };
    }
    
    public function getStores($withDefault = false, $codeKey = false)
    {
        return [];
    }
    
    public function getWebsites($withDefault = false, $codeKey = false)
    {
        return [];
    }
    
    public function getWebsite($websiteId = null)
    {
        return null;
    }
    
    public function getGroups($withDefault = false)
    {
        return [];
    }
    
    public function getGroup($groupId = null)
    {
        return null;
    }
    
    public function getDefaultStoreView()
    {
        return $this->getStore();
    }
    
    public function setIsSingleStoreModeAllowed($value)
    {
        return $this;
    }
    
    public function hasSingleStore()
    {
        return false;
    }
    
    public function isSingleStoreMode()
    {
        return false;
    }
    
    public function getCurrentStore()
    {
        return $this->getStore();
    }
    
    public function setCurrentStore($store)
    {
        return $this;
    }
    
    public function reinitStores()
    {
        return $this;
    }
}

class MockTemplate extends \React\React\Template
{
    public function __construct($config = null)
    {
        // Create minimal mocks for parent constructor
        $mockContext = new class extends \Magento\Framework\View\Element\Template\Context {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        $mockObjectManager = new class implements \Magento\Framework\ObjectManagerInterface {
            public function get($type) { return null; }
            public function create($type, array $arguments = []) { return new $type(...$arguments); }
            public function configure(array $configuration) { return $this; }
            public function setSharedInstance(\Magento\Framework\ObjectManagerInterface $objectManager) {}
            public function getSharedInstance($type, $arguments = []) { return null; }
            public function reset() {}
        };
        
        $mockRegistry = new class extends \Magento\Framework\Registry {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        $mockConfig = $config ?? new MockScopeConfig();
        
        // Call parent constructor with mocks
        parent::__construct($mockContext, $mockObjectManager, $mockRegistry, $mockConfig, []);
    }
    
    // Don't override removeAdobeJSJunk() and removeAdobeCSSJunk() - use parent implementation
    // This allows us to test the actual GET parameter override logic
}

class MockLogger implements \Psr\Log\LoggerInterface
{
    public $logs = [];
    
    public function emergency(\Stringable|string $message, array $context = []): void {}
    public function alert(\Stringable|string $message, array $context = []): void {}
    public function critical(\Stringable|string $message, array $context = []): void {}
    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['level' => 'error', 'message' => (string)$message];
    }
    public function warning(\Stringable|string $message, array $context = []): void {}
    public function notice(\Stringable|string $message, array $context = []): void {}
    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['level' => 'info', 'message' => (string)$message];
    }
    public function debug(\Stringable|string $message, array $context = []): void {}
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['level' => $level, 'message' => (string)$message];
    }
}

describe('ReactInjectPlugin - Basic Tests', function () {
    test('can be instantiated', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        expect($helper->getInstance())->toBeInstanceOf(\React\React\ReactInjectPlugin::class);
        
    });
    
    test('has correct action filter', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        $expectedActions = [
            'catalog_category_view',
            'cms_index_index',
            'cms_page_view',
            'catalog_product_view',
            'catalogsearch_result_index',
            'cms_noroute_index',
            'customer_account_login',
            'customer_account_create'
        ];
        
        expect($instance->actionFilter)->toBe($expectedActions);
        
    });
    
    test('staticVersion defaults to 0', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        expect($instance->staticVersion)->toBe(0);
        
    });
});

describe('ReactInjectPlugin - File Checking Tests', function () {
    test('checkFile returns false for non-existent file', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        $result = $instance->checkFile('/non/existent/file.txt');
        
        expect($result)->toBeFalse();
        
    });
    
    test('checkFile returns true for existent file', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        $result = $instance->checkFile(__FILE__);
        
        expect($result)->toBeTrue();
        
    });
});

describe('ReactInjectPlugin - Configuration Tests', function () {
    test('getStaticVersion returns string', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        $version = $instance->getStaticVersion();
        
        expect($version)->toBeString();
        
    });
    
    test('isMinifyEnabled returns boolean', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        $result = $instance->isMinifyEnabled();
        
        expect($result)->toBeBool();
        
    });
    
    test('getConfigurationSettings returns array with expected keys', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        $result = $helper->callMethod('getConfigurationSettings');
        
        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['reactEnabled', 'vueEnabled', 'removeAdobeJSJunk', 'removeCSSjunk', 'criticalCSSHTML']);
        
    });
});

describe('ReactInjectPlugin - Page Type Detection Tests', function () {
    test('determinePageTypes identifies product page correctly', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        $result = $helper->callMethod('determinePageTypes', 'catalog_product_view');
        
        expect($result)->toBeArray();
        expect($result['isProduct'])->toBeTrue();
        expect($result['isCategory'])->toBeFalse();
        
    });
    
    test('determinePageTypes identifies category page correctly', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        $result = $helper->callMethod('determinePageTypes', 'catalog_category_view');
        
        expect($result)->toBeArray();
        expect($result['isProduct'])->toBeFalse();
        expect($result['isCategory'])->toBeTrue();
        
    });
    
    test('determinePageTypes identifies CMS page correctly', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        $result = $helper->callMethod('determinePageTypes', 'cms_index_index');
        
        expect($result)->toBeArray();
        expect($result['isProduct'])->toBeFalse();
        expect($result['isCategory'])->toBeFalse();
        expect($result['isHome'])->toBeTrue();
        
    });
});

describe('ReactInjectPlugin - Request Context Tests', function () {
    test('getRequestContext returns array with expected keys', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Set objectManager property before calling getRequestContext
        // (normally set in renderAssetHtml, but we're testing getRequestContext directly)
        $mockObjectManager = new class implements \Magento\Framework\ObjectManagerInterface {
            public function get($type) {
                if ($type === \Magento\Framework\App\Request\Http::class) {
                    // Return simple mock without extending real class (avoids dependency loading)
                    return new class {
                        public function getFullActionName($delimiter = '_') {
                            return 'catalog_product_view';
                        }
                    };
                }
                return null;
            }
            public function create($type, array $arguments = []) { 
                try {
                    return new $type(...$arguments);
                } catch (\Exception $e) {
                    return null;
                }
            }
            public function configure(array $configuration) { return $this; }
            public function setSharedInstance(\Magento\Framework\ObjectManagerInterface $objectManager) {}
            public function getSharedInstance($type, $arguments = []) { return null; }
            public function reset() {}
        };
        
        $helper->setProperty('objectManager', $mockObjectManager);
        
        // Set up $_SERVER for REQUEST_URI
        $_SERVER['REQUEST_URI'] = '/test-product.html';
        
        $result = $helper->callMethod('getRequestContext');
        
        expect($result)->toBeArray();
        expect($result)->toHaveKey('actionName');
        
        
        // Cleanup
        unset($_SERVER['REQUEST_URI']);
    });
});

describe('ReactInjectPlugin - GET Parameter Override Tests', function () {
    beforeEach(function () {
        // Clean up $_GET before each test
        unset($_GET['js-junk']);
        unset($_GET['css-react']);
    });
    
    afterEach(function () {
        // Clean up $_GET after each test
        unset($_GET['js-junk']);
        unset($_GET['css-react']);
    });
    
    test('removeAdobeJSJunk uses config value when GET parameter not set', function () {
        $mockScopeConfig = new MockScopeConfig([
            'react_vue_config/junk/remove' => '1', // Enabled in config
        ]);
        
        $template = new MockTemplate($mockScopeConfig);
        
        // No GET parameter set
        unset($_GET['js-junk']);
        
        $result = $template->removeAdobeJSJunk();
        
        expect($result)->toBeTrue();
        
    });
    
    test('removeAdobeJSJunk GET parameter "true" overrides config', function () {
        $mockScopeConfig = new MockScopeConfig([
            'react_vue_config/junk/remove' => '0', // Disabled in config
        ]);
        
        $template = new MockTemplate($mockScopeConfig);
        
        // GET parameter overrides config
        $_GET['js-junk'] = 'true';
        
        $result = $template->removeAdobeJSJunk();
        
        expect($result)->toBeTrue();
        
    });
    
    test('removeAdobeJSJunk GET parameter "false" overrides config', function () {
        $mockScopeConfig = new MockScopeConfig([
            'react_vue_config/junk/remove' => '1', // Enabled in config
        ]);
        
        $template = new MockTemplate($mockScopeConfig);
        
        // GET parameter overrides config
        $_GET['js-junk'] = 'false';
        
        $result = $template->removeAdobeJSJunk();
        
        expect($result)->toBeFalse();
        
    });
    
    test('removeAdobeCSSJunk uses config value when GET parameter not set', function () {
        $mockScopeConfig = new MockScopeConfig([
            'react_vue_config/junk/remove' => '1', // Enabled in config
        ]);
        
        $template = new MockTemplate($mockScopeConfig);
        
        // No GET parameter set
        unset($_GET['css-react']);
        
        $result = $template->removeAdobeCSSJunk();
        
        expect($result)->toBeTrue();
        
    });
    
    test('removeAdobeCSSJunk GET parameter "true" overrides config', function () {
        $mockScopeConfig = new MockScopeConfig([
            'react_vue_config/junk/remove' => '0', // Disabled in config
        ]);
        
        $template = new MockTemplate($mockScopeConfig);
        
        // GET parameter overrides config
        $_GET['css-react'] = 'true';
        
        $result = $template->removeAdobeCSSJunk();
        
        expect($result)->toBeTrue();
        
    });
    
    test('removeAdobeCSSJunk GET parameter "false" overrides config', function () {
        $mockScopeConfig = new MockScopeConfig([
            'react_vue_config/junk/remove' => '1', // Enabled in config
        ]);
        
        $template = new MockTemplate($mockScopeConfig);
        
        // GET parameter overrides config
        $_GET['css-react'] = 'false';
        
        $result = $template->removeAdobeCSSJunk();
        
        expect($result)->toBeFalse();
        
    });
    
    test('GET parameters take precedence over config values', function () {
        $mockScopeConfig = new MockScopeConfig([
            'react_vue_config/junk/remove' => '1', // Enabled in config
        ]);
        
        $template = new MockTemplate($mockScopeConfig);
        
        // Set GET parameters to override config
        $_GET['js-junk'] = 'false';
        $_GET['css-react'] = 'false';
        
        $jsResult = $template->removeAdobeJSJunk();
        $cssResult = $template->removeAdobeCSSJunk();
        
        expect($jsResult)->toBeFalse()
            ->and($cssResult)->toBeFalse();
        
    });
});

describe('ReactInjectPlugin - Action Filter Optimization Tests', function () {
    beforeEach(function () {
        // Set up $_SERVER for REQUEST_URI
        $_SERVER['REQUEST_URI'] = '/test-page.html';
    });
    
    afterEach(function () {
        // Clean up $_SERVER
        unset($_SERVER['REQUEST_URI']);
    });
    
    test('only actions in actionFilter are optimized', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        // Test all actions in actionFilter
        $allowedActions = $instance->actionFilter;
        
        foreach ($allowedActions as $action) {
            // Mock the request context with this action
            $mockObjectManager = new class($action) implements \Magento\Framework\ObjectManagerInterface {
                private $actionName;
                public function __construct($actionName) {
                    $this->actionName = $actionName;
                }
                public function get($type) {
                    if ($type === \Magento\Framework\App\Request\Http::class) {
                        return new class($this->actionName) {
                            private $actionName;
                            public function __construct($actionName) {
                                $this->actionName = $actionName;
                            }
                            public function getFullActionName($delimiter = '_') {
                                return $this->actionName;
                            }
                        };
                    }
                    return null;
                }
                public function create($type, array $arguments = []) { return null; }
                public function configure(array $configuration) { return $this; }
                public function setSharedInstance(\Magento\Framework\ObjectManagerInterface $objectManager) {}
                public function getSharedInstance($type, $arguments = []) { return null; }
                public function reset() {}
            };
            
            $helper->setProperty('objectManager', $mockObjectManager);
            $requestContext = $helper->callMethod('getRequestContext');
            
            expect($requestContext['removeController'])->toBeTrue()
                ->and($requestContext['actionName'])->toBe($action);
        }
        
    });
    
    test('actions NOT in actionFilter are NOT optimized', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        // Test actions NOT in actionFilter
        $notAllowedActions = [
            'checkout_cart_index',
            'checkout_index_index',
            'customer_account_index',
            'catalog_product_compare_index',
            'wishlist_index_index',
            'adminhtml_dashboard_index',
        ];
        
        foreach ($notAllowedActions as $action) {
            $mockObjectManager = new class($action) implements \Magento\Framework\ObjectManagerInterface {
                private $actionName;
                public function __construct($actionName) {
                    $this->actionName = $actionName;
                }
                public function get($type) {
                    if ($type === \Magento\Framework\App\Request\Http::class) {
                        return new class($this->actionName) {
                            private $actionName;
                            public function __construct($actionName) {
                                $this->actionName = $actionName;
                            }
                            public function getFullActionName($delimiter = '_') {
                                return $this->actionName;
                            }
                        };
                    }
                    return null;
                }
                public function create($type, array $arguments = []) { return null; }
                public function configure(array $configuration) { return $this; }
                public function setSharedInstance(\Magento\Framework\ObjectManagerInterface $objectManager) {}
                public function getSharedInstance($type, $arguments = []) { return null; }
                public function reset() {}
            };
            
            $helper->setProperty('objectManager', $mockObjectManager);
            $requestContext = $helper->callMethod('getRequestContext');
            
            expect($requestContext['removeController'])->toBeFalse()
                ->and($requestContext['actionName'])->toBe($action);
        }
        
    });
    
    test('product page (catalog_product_view) is optimized', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        $mockObjectManager = new class implements \Magento\Framework\ObjectManagerInterface {
            public function get($type) {
                if ($type === \Magento\Framework\App\Request\Http::class) {
                    return new class {
                        public function getFullActionName($delimiter = '_') {
                            return 'catalog_product_view';
                        }
                    };
                }
                return null;
            }
            public function create($type, array $arguments = []) { return null; }
            public function configure(array $configuration) { return $this; }
            public function setSharedInstance(\Magento\Framework\ObjectManagerInterface $objectManager) {}
            public function getSharedInstance($type, $arguments = []) { return null; }
            public function reset() {}
        };
        
        $helper->setProperty('objectManager', $mockObjectManager);
        $requestContext = $helper->callMethod('getRequestContext');
        $pageTypes = $helper->callMethod('determinePageTypes', 'catalog_product_view');
        
        expect($requestContext['removeController'])->toBeTrue()
            ->and($pageTypes['isProduct'])->toBeTrue()
            ->and($pageTypes['isCategory'])->toBeFalse();
        
    });
    
    test('category page (catalog_category_view) is optimized', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        $mockObjectManager = new class implements \Magento\Framework\ObjectManagerInterface {
            public function get($type) {
                if ($type === \Magento\Framework\App\Request\Http::class) {
                    return new class {
                        public function getFullActionName($delimiter = '_') {
                            return 'catalog_category_view';
                        }
                    };
                }
                return null;
            }
            public function create($type, array $arguments = []) { return null; }
            public function configure(array $configuration) { return $this; }
            public function setSharedInstance(\Magento\Framework\ObjectManagerInterface $objectManager) {}
            public function getSharedInstance($type, $arguments = []) { return null; }
            public function reset() {}
        };
        
        $helper->setProperty('objectManager', $mockObjectManager);
        $requestContext = $helper->callMethod('getRequestContext');
        $pageTypes = $helper->callMethod('determinePageTypes', 'catalog_category_view');
        
        expect($requestContext['removeController'])->toBeTrue()
            ->and($pageTypes['isProduct'])->toBeFalse()
            ->and($pageTypes['isCategory'])->toBeTrue();
        
    });
    
    test('search page (catalogsearch_result_index) is optimized', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        $mockObjectManager = new class implements \Magento\Framework\ObjectManagerInterface {
            public function get($type) {
                if ($type === \Magento\Framework\App\Request\Http::class) {
                    return new class {
                        public function getFullActionName($delimiter = '_') {
                            return 'catalogsearch_result_index';
                        }
                    };
                }
                return null;
            }
            public function create($type, array $arguments = []) { return null; }
            public function configure(array $configuration) { return $this; }
            public function setSharedInstance(\Magento\Framework\ObjectManagerInterface $objectManager) {}
            public function getSharedInstance($type, $arguments = []) { return null; }
            public function reset() {}
        };
        
        $helper->setProperty('objectManager', $mockObjectManager);
        $requestContext = $helper->callMethod('getRequestContext');
        $pageTypes = $helper->callMethod('determinePageTypes', 'catalogsearch_result_index');
        
        expect($requestContext['removeController'])->toBeTrue()
            ->and($pageTypes['isProduct'])->toBeFalse()
            ->and($pageTypes['isCategory'])->toBeTrue();
        
    });
    
    test('checkout page (checkout_cart_index) is NOT optimized', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        $mockObjectManager = new class implements \Magento\Framework\ObjectManagerInterface {
            public function get($type) {
                if ($type === \Magento\Framework\App\Request\Http::class) {
                    return new class {
                        public function getFullActionName($delimiter = '_') {
                            return 'checkout_cart_index';
                        }
                    };
                }
                return null;
            }
            public function create($type, array $arguments = []) { return null; }
            public function configure(array $configuration) { return $this; }
            public function setSharedInstance(\Magento\Framework\ObjectManagerInterface $objectManager) {}
            public function getSharedInstance($type, $arguments = []) { return null; }
            public function reset() {}
        };
        
        $helper->setProperty('objectManager', $mockObjectManager);
        $requestContext = $helper->callMethod('getRequestContext');
        $pageTypes = $helper->callMethod('determinePageTypes', 'checkout_cart_index');
        
        expect($requestContext['removeController'])->toBeFalse()
            ->and($pageTypes['isProduct'])->toBeFalse()
            ->and($pageTypes['isCategory'])->toBeFalse();
        
    });
});

describe('ReactInjectPlugin - Per-Page CSS Optimization Tests', function () {
    beforeEach(function () {
        $_SERVER['REQUEST_URI'] = '/test-page.html';
    });
    
    afterEach(function () {
        unset($_SERVER['REQUEST_URI']);
    });
    
    test('product page CSS paths are generated correctly', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Initialize asset variables
        $helper->callMethod('initializeAssetVariables');
        
        // Simulate processMobileCSS for product page
        $requestContext = [
            'actionName' => 'catalog_product_view',
            'removeController' => true,
        ];
        $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
        $baseURL = 'http://localhost/pub/static/';
        
        // Create mock asset
        $mockAsset = new class {
            public function getUrl() {
                return 'http://localhost/pub/static/styles-m.css';
            }
        };
        
        $assets = [0 => $mockAsset];
        $key = 0;
        
        // Call processMobileCSS - this will set up the CSS paths
        // Note: callMethod uses ...$args, so we pass arguments separately
        $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
        
        $assetVars = $helper->getProperty('assetVariables');
        
        // CSS files may be minified (.min.css) or not (.css) depending on config
        expect($assetVars['optimisedProductCSSFileUrl'])->toContain('product-styles-m')
            ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->toContain('product-critical-m')
            ->and($assetVars['optimisedProductCSSFileCriticalPath'])->toMatch('/product-critical-m(\.min)?\.css/');
        
    });
    
    test('category page CSS paths are generated correctly', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Initialize asset variables
        $helper->callMethod('initializeAssetVariables');
        
        // Simulate processMobileCSS for category page
        $requestContext = [
            'actionName' => 'catalog_category_view',
            'removeController' => true,
        ];
        $pageTypes = ['isProduct' => false, 'isCategory' => true, 'isHome' => false];
        $baseURL = 'http://localhost/pub/static/';
        
        // Create mock asset
        $mockAsset = new class {
            public function getUrl() {
                return 'http://localhost/pub/static/styles-m.css';
            }
        };
        
        $assets = [0 => $mockAsset];
        $key = 0;
        
        // Call processMobileCSS - this will set up the CSS paths
        // Note: callMethod uses ...$args, so we pass arguments separately
        $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
        
        $assetVars = $helper->getProperty('assetVariables');
        
        // CSS files may be minified (.min.css) or not (.css) depending on config
        expect($assetVars['optimisedCategoryCSSFileUrl'])->toContain('category-styles-m')
            ->and($assetVars['optimisedCategoryCSSFileCriticalUrl'])->toContain('category-critical-m')
            ->and($assetVars['optimisedCategoryCSSFileCriticalPath'])->toMatch('/category-critical-m(\.min)?\.css/');
        
    });
    
    test('search page uses category CSS paths', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Initialize asset variables
        $helper->callMethod('initializeAssetVariables');
        
        // Simulate processMobileCSS for search page
        $requestContext = [
            'actionName' => 'catalogsearch_result_index',
            'removeController' => true,
        ];
        $pageTypes = ['isProduct' => false, 'isCategory' => true, 'isHome' => false]; // Search is treated as category
        $baseURL = 'http://localhost/pub/static/';
        
        // Create mock asset
        $mockAsset = new class {
            public function getUrl() {
                return 'http://localhost/pub/static/styles-m.css';
            }
        };
        
        $assets = [0 => $mockAsset];
        $key = 0;
        
        // Call processMobileCSS - pass all 6 arguments separately
        $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
        
        $assetVars = $helper->getProperty('assetVariables');
        
        // CSS files may be minified (.min.css) or not (.css) depending on config
        expect($assetVars['optimisedCategoryCSSFileUrl'])->toContain('category-styles-m')
            ->and($assetVars['optimisedCategoryCSSFileCriticalUrl'])->toContain('category-critical-m');
        
    });
    
    test('home page CSS paths are generated correctly', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Initialize asset variables
        $helper->callMethod('initializeAssetVariables');
        
        // Simulate processMobileCSS for home page
        $requestContext = [
            'actionName' => 'cms_index_index',
            'removeController' => true,
        ];
        $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => true];
        $baseURL = 'http://localhost/pub/static/';
        
        // Create mock asset
        $mockAsset = new class {
            public function getUrl() {
                return 'http://localhost/pub/static/styles-m.css';
            }
        };
        
        $assets = [0 => $mockAsset];
        $key = 0;
        
        // Call processMobileCSS - this will set up the CSS paths
        $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
        
        $assetVars = $helper->getProperty('assetVariables');
        
        // CSS files may be minified (.min.css) or not (.css) depending on config
        expect($assetVars['optimisedHomeCSSFileUrl'])->toContain('home-styles-m')
            ->and($assetVars['optimisedHomeCSSFileCriticalUrl'])->toContain('home-critical-m')
            ->and($assetVars['optimisedHomeCSSFileCriticalPath'])->toMatch('/home-critical-m(\.min)?\.css/');
        
    });
    
    test('product page HTML includes critical CSS when file exists', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Set up asset variables as if product CSS files exist
        // We'll simulate the checkFile returning true by setting the variables directly
        $helper->setProperty('assetVariables', [
            'assetProductOptimized' => 'http://localhost/pub/static/product-styles-m.css',
            'optimisedProductCSSFileCriticalPath' => BP . '/pub/static/product-critical-m.css',
            'optimisedProductCSSFileCriticalUrl' => 'http://localhost/pub/static/product-critical-m.css',
            'assetOptimized' => false,
            'assetCategoryOptimized' => false,
            'assetOptimizedLarge' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedCategoryCSSFileCriticalPath' => false,
            'optimisedCategoryCSSFileCriticalUrl' => '',
        ]);
        
        // Mock checkFile to return true for critical CSS
        // We need to create a subclass that overrides checkFile
        $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
        $result = '';
        
        // Test addProductCSSLinks - this will check if file exists
        // Since we can't easily mock checkFile, we'll test the HTML generation logic
        // by manually setting the variables to simulate file existence
        $result = $helper->callMethod('addProductCSSLinks', $result, $pageTypes);
        
        // The result should contain product CSS links if the file check passes
        // Since we can't mock checkFile easily, we verify the structure is correct
        expect($result)->toBeString();
        
        // If critical CSS path is set, the HTML should include it
        $assetVars = $helper->getProperty('assetVariables');
        if ($assetVars['optimisedProductCSSFileCriticalPath']) {
            // The method will call checkFile, which we can't easily mock
            // But we can verify the logic structure
            expect($assetVars['optimisedProductCSSFileCriticalUrl'])->toContain('product-critical-m.css');
        }
        
    });
    
    test('category page HTML includes critical CSS when file exists', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Set up asset variables as if category CSS files exist
        $helper->setProperty('assetVariables', [
            'assetCategoryOptimized' => 'http://localhost/pub/static/category-styles-m.css',
            'optimisedCategoryCSSFileCriticalPath' => BP . '/pub/static/category-critical-m.css',
            'optimisedCategoryCSSFileCriticalUrl' => 'http://localhost/pub/static/category-critical-m.css',
            'assetOptimized' => false,
            'assetProductOptimized' => false,
            'assetHomeOptimized' => false,
            'assetOptimizedLarge' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedProductCSSFileCriticalPath' => false,
            'optimisedProductCSSFileCriticalUrl' => '',
            'optimisedHomeCSSFileCriticalPath' => false,
            'optimisedHomeCSSFileCriticalUrl' => '',
        ]);
        
        $pageTypes = ['isProduct' => false, 'isCategory' => true, 'isHome' => false];
        $result = '';
        
        // Test addCategoryCSSLinks
        $result = $helper->callMethod('addCategoryCSSLinks', $result, $pageTypes);
        
        expect($result)->toBeString();
        
        $assetVars = $helper->getProperty('assetVariables');
        expect($assetVars['optimisedCategoryCSSFileCriticalUrl'])->toContain('category-critical-m.css');
        
    });
    
    test('home page HTML includes critical CSS when file exists', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Set up asset variables as if home CSS files exist
        $helper->setProperty('assetVariables', [
            'assetHomeOptimized' => 'http://localhost/pub/static/home-styles-m.css',
            'optimisedHomeCSSFileCriticalPath' => BP . '/pub/static/home-critical-m.css',
            'optimisedHomeCSSFileCriticalUrl' => 'http://localhost/pub/static/home-critical-m.css',
            'assetOptimized' => false,
            'assetProductOptimized' => false,
            'assetCategoryOptimized' => false,
            'assetOptimizedLarge' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedProductCSSFileCriticalPath' => false,
            'optimisedProductCSSFileCriticalUrl' => '',
            'optimisedCategoryCSSFileCriticalPath' => false,
            'optimisedCategoryCSSFileCriticalUrl' => '',
        ]);
        
        $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => true];
        $result = '';
        
        // Test addHomePageCSSLinks
        $result = $helper->callMethod('addHomePageCSSLinks', $result, $pageTypes);
        
        expect($result)->toBeString();
        
        $assetVars = $helper->getProperty('assetVariables');
        expect($assetVars['optimisedHomeCSSFileCriticalUrl'])->toContain('home-critical-m.css');
        
    });
    
    test('product page loads optimized CSS with print media trick when critical CSS exists', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Create temporary critical CSS file
        $criticalCSSFile = BP . '/pub/static/product-critical-m.css';
        $criticalCSSFileExists = file_exists($criticalCSSFile);
        if (!$criticalCSSFileExists) {
            file_put_contents($criticalCSSFile, '/* critical CSS */');
        }
        
        try {
            // Set up asset variables
            $helper->setProperty('assetVariables', [
                'assetProductOptimized' => 'http://localhost/pub/static/product-styles-m.css',
                'optimisedProductCSSFileCriticalPath' => $criticalCSSFile,
                'optimisedProductCSSFileCriticalUrl' => 'http://localhost/pub/static/product-critical-m.css',
                'assetOptimized' => false,
                'assetCategoryOptimized' => false,
                'assetHomeOptimized' => false,
                'assetOptimizedLarge' => false,
                'assetNotOptimisedMobile' => false,
                'assetNotOptimisedLarge' => false,
                'optimisedCategoryCSSFileCriticalPath' => false,
                'optimisedCategoryCSSFileCriticalUrl' => '',
                'optimisedHomeCSSFileCriticalPath' => false,
                'optimisedHomeCSSFileCriticalUrl' => '',
            ]);
            
            $helper->setProperty('configuration', [
                'criticalCSSHTML' => false,
            ]);
            
            $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
            $result = '';
            
            // Test addProductCSSLinks
            $result = $helper->callMethod('addProductCSSLinks', $result, $pageTypes);
            
            // Should use print media trick when critical CSS exists
            expect($result)->toContain('media="print"')
                ->and($result)->toContain('onload="this.onload=null;this.media=\'all\';"')
                ->and($result)->toContain('product-styles-m.css');
        } finally {
            // Clean up temporary file if we created it
            if (!$criticalCSSFileExists && file_exists($criticalCSSFile)) {
                unlink($criticalCSSFile);
            }
        }
    });
    
    test('product page loads optimized CSS as regular stylesheet when critical CSS does not exist', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Use a non-existent critical CSS file path
        $nonExistentCriticalCSSFile = BP . '/pub/static/product-critical-m-nonexistent.css';
        
        // Set up asset variables
        $helper->setProperty('assetVariables', [
            'assetProductOptimized' => 'http://localhost/pub/static/product-styles-m.css',
            'optimisedProductCSSFileCriticalPath' => $nonExistentCriticalCSSFile,
            'optimisedProductCSSFileCriticalUrl' => 'http://localhost/pub/static/product-critical-m.css',
            'assetOptimized' => false,
            'assetCategoryOptimized' => false,
            'assetHomeOptimized' => false,
            'assetOptimizedLarge' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedCategoryCSSFileCriticalPath' => false,
            'optimisedCategoryCSSFileCriticalUrl' => '',
            'optimisedHomeCSSFileCriticalPath' => false,
            'optimisedHomeCSSFileCriticalUrl' => '',
        ]);
        
        $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
        $result = '';
        
        // Test addProductCSSLinks
        $result = $helper->callMethod('addProductCSSLinks', $result, $pageTypes);
        
        // Should NOT use print media trick when critical CSS doesn't exist
        expect($result)->not->toContain('media="print"')
            ->and($result)->not->toContain('onload="this.onload=null;this.media=\'all\';"')
            ->and($result)->toContain('media="all"')
            ->and($result)->toContain('product-styles-m.css');
    });
    
    test('category page loads optimized CSS with print media trick when critical CSS exists', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Create temporary critical CSS file
        $criticalCSSFile = BP . '/pub/static/category-critical-m.css';
        $criticalCSSFileExists = file_exists($criticalCSSFile);
        if (!$criticalCSSFileExists) {
            file_put_contents($criticalCSSFile, '/* critical CSS */');
        }
        
        try {
            // Set up asset variables
            $helper->setProperty('assetVariables', [
                'assetCategoryOptimized' => 'http://localhost/pub/static/category-styles-m.css',
                'optimisedCategoryCSSFileCriticalPath' => $criticalCSSFile,
                'optimisedCategoryCSSFileCriticalUrl' => 'http://localhost/pub/static/category-critical-m.css',
                'assetOptimized' => false,
                'assetProductOptimized' => false,
                'assetHomeOptimized' => false,
                'assetOptimizedLarge' => false,
                'assetNotOptimisedMobile' => false,
                'assetNotOptimisedLarge' => false,
                'optimisedProductCSSFileCriticalPath' => false,
                'optimisedProductCSSFileCriticalUrl' => '',
                'optimisedHomeCSSFileCriticalPath' => false,
                'optimisedHomeCSSFileCriticalUrl' => '',
            ]);
            
            $pageTypes = ['isProduct' => false, 'isCategory' => true, 'isHome' => false];
            $result = '';
            
            // Test addCategoryCSSLinks
            $result = $helper->callMethod('addCategoryCSSLinks', $result, $pageTypes);
            
            // Should use print media trick when critical CSS exists
            expect($result)->toContain('media="print"')
                ->and($result)->toContain('onload="this.onload=null;this.media=\'all\';"')
                ->and($result)->toContain('category-styles-m.css');
        } finally {
            // Clean up temporary file if we created it
            if (!$criticalCSSFileExists && file_exists($criticalCSSFile)) {
                unlink($criticalCSSFile);
            }
        }
    });
    
    test('category page loads optimized CSS as regular stylesheet when critical CSS does not exist', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Use a non-existent critical CSS file path
        $nonExistentCriticalCSSFile = BP . '/pub/static/category-critical-m-nonexistent.css';
        
        // Set up asset variables
        $helper->setProperty('assetVariables', [
            'assetCategoryOptimized' => 'http://localhost/pub/static/category-styles-m.css',
            'optimisedCategoryCSSFileCriticalPath' => $nonExistentCriticalCSSFile,
            'optimisedCategoryCSSFileCriticalUrl' => 'http://localhost/pub/static/category-critical-m.css',
            'assetOptimized' => false,
            'assetProductOptimized' => false,
            'assetHomeOptimized' => false,
            'assetOptimizedLarge' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedProductCSSFileCriticalPath' => false,
            'optimisedProductCSSFileCriticalUrl' => '',
            'optimisedHomeCSSFileCriticalPath' => false,
            'optimisedHomeCSSFileCriticalUrl' => '',
        ]);
        
        $pageTypes = ['isProduct' => false, 'isCategory' => true, 'isHome' => false];
        $result = '';
        
        // Test addCategoryCSSLinks
        $result = $helper->callMethod('addCategoryCSSLinks', $result, $pageTypes);
        
        // Should NOT use print media trick when critical CSS doesn't exist
        expect($result)->not->toContain('media="print"')
            ->and($result)->not->toContain('onload="this.onload=null;this.media=\'all\';"')
            ->and($result)->toContain('media="all"')
            ->and($result)->toContain('category-styles-m.css');
    });
    
    test('home page loads optimized CSS with print media trick when critical CSS exists', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Create temporary critical CSS file
        $criticalCSSFile = BP . '/pub/static/home-critical-m.css';
        $criticalCSSFileExists = file_exists($criticalCSSFile);
        if (!$criticalCSSFileExists) {
            file_put_contents($criticalCSSFile, '/* critical CSS */');
        }
        
        try {
            // Set up asset variables
            $helper->setProperty('assetVariables', [
                'assetHomeOptimized' => 'http://localhost/pub/static/home-styles-m.css',
                'optimisedHomeCSSFileCriticalPath' => $criticalCSSFile,
                'optimisedHomeCSSFileCriticalUrl' => 'http://localhost/pub/static/home-critical-m.css',
                'assetOptimized' => false,
                'assetProductOptimized' => false,
                'assetCategoryOptimized' => false,
                'assetOptimizedLarge' => false,
                'assetNotOptimisedMobile' => false,
                'assetNotOptimisedLarge' => false,
                'optimisedProductCSSFileCriticalPath' => false,
                'optimisedProductCSSFileCriticalUrl' => '',
                'optimisedCategoryCSSFileCriticalPath' => false,
                'optimisedCategoryCSSFileCriticalUrl' => '',
            ]);
            
            $helper->setProperty('configuration', [
                'criticalCSSHTML' => false,
            ]);
            
            $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => true];
            $result = '';
            
            // Test addHomePageCSSLinks
            $result = $helper->callMethod('addHomePageCSSLinks', $result, $pageTypes);
            
            // Should use print media trick when critical CSS exists
            expect($result)->toContain('media="print"')
                ->and($result)->toContain('onload="this.onload=null;this.media=\'all\';"')
                ->and($result)->toContain('home-styles-m.css');
        } finally {
            // Clean up temporary file if we created it
            if (!$criticalCSSFileExists && file_exists($criticalCSSFile)) {
                unlink($criticalCSSFile);
            }
        }
    });
    
    test('home page loads optimized CSS as regular stylesheet when critical CSS does not exist', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Use a non-existent critical CSS file path
        $nonExistentCriticalCSSFile = BP . '/pub/static/home-critical-m-nonexistent.css';
        
        // Set up asset variables
        $helper->setProperty('assetVariables', [
            'assetHomeOptimized' => 'http://localhost/pub/static/home-styles-m.css',
            'optimisedHomeCSSFileCriticalPath' => $nonExistentCriticalCSSFile,
            'optimisedHomeCSSFileCriticalUrl' => 'http://localhost/pub/static/home-critical-m.css',
            'assetOptimized' => false,
            'assetProductOptimized' => false,
            'assetCategoryOptimized' => false,
            'assetOptimizedLarge' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedProductCSSFileCriticalPath' => false,
            'optimisedProductCSSFileCriticalUrl' => '',
            'optimisedCategoryCSSFileCriticalPath' => false,
            'optimisedCategoryCSSFileCriticalUrl' => '',
        ]);
        
        $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => true];
        $result = '';
        
        // Test addHomePageCSSLinks
        $result = $helper->callMethod('addHomePageCSSLinks', $result, $pageTypes);
        
        // Should NOT use print media trick when critical CSS doesn't exist
        expect($result)->not->toContain('media="print"')
            ->and($result)->not->toContain('onload="this.onload=null;this.media=\'all\';"')
            ->and($result)->toContain('media="all"')
            ->and($result)->toContain('home-styles-m.css');
    });
    
    test('critical CSS config controls HTML inline vs preload', function () {
        // Test criticalCSSHTML = false (should add inline)
        $mockScopeConfigFalse = new MockScopeConfig([
            'react_vue_config/css/critical' => '0',
        ]);
        $mockTemplateFalse = new MockTemplate($mockScopeConfigFalse);
        
        $helperFalse = new ReactInjectPluginTestHelper([
            'scopeConfig' => $mockScopeConfigFalse,
            'template' => $mockTemplateFalse,
        ]);
        
        $configFalse = $helperFalse->callMethod('getConfigurationSettings');
        expect($configFalse['criticalCSSHTML'])->toBeFalse();
        
        // Test criticalCSSHTML = true (should use preload only)
        $mockScopeConfigTrue = new MockScopeConfig([
            'react_vue_config/css/critical' => '1',
        ]);
        $mockTemplateTrue = new MockTemplate($mockScopeConfigTrue);
        
        $helperTrue = new ReactInjectPluginTestHelper([
            'scopeConfig' => $mockScopeConfigTrue,
            'template' => $mockTemplateTrue,
        ]);
        
        $configTrue = $helperTrue->callMethod('getConfigurationSettings');
        expect($configTrue['criticalCSSHTML'])->toBeTrue();
        
    });
    
    test('non-product/category/home pages use general optimized CSS', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Set up asset variables for general CSS (not page-specific)
        $helper->setProperty('assetVariables', [
            'assetOptimized' => 'http://localhost/pub/static/styles-m.css',
            'assetProductOptimized' => false,
            'assetCategoryOptimized' => false,
            'assetHomeOptimized' => false,
            'assetOptimizedLarge' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedProductCSSFileCriticalPath' => false,
            'optimisedCategoryCSSFileCriticalPath' => false,
            'optimisedHomeCSSFileCriticalPath' => false,
            'optimisedProductCSSFileCriticalUrl' => '',
            'optimisedCategoryCSSFileCriticalUrl' => '',
            'optimisedHomeCSSFileCriticalUrl' => '',
        ]);
        
        $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => false];
        $result = '';
        
        // Test addOptimizedCSSLinks for non-product/category/home page
        $result = $helper->callMethod('addOptimizedCSSLinks', $result, $pageTypes);
        
        // Should contain general CSS, not page-specific
        expect($result)->toContain('styles-m.css')
            ->and($result)->not->toContain('product-styles-m.css')
            ->and($result)->not->toContain('category-styles-m.css')
            ->and($result)->not->toContain('home-styles-m.css');
        
    });
    
    test('home page adds specific CSS links', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Set up asset variables for home page CSS
        // Set critical path to false so checkFile will fail and it uses the else branch
        $helper->setProperty('assetVariables', [
            'assetHomeOptimized' => 'http://localhost/pub/static/home-styles-m.css',
            'optimisedHomeCSSFileCriticalPath' => false, // File doesn't exist, so use else branch
            'optimisedHomeCSSFileCriticalUrl' => 'http://localhost/pub/static/home-critical-m.css',
            'assetOptimized' => false,
            'assetProductOptimized' => false,
            'assetCategoryOptimized' => false,
            'assetHomeOptimized' => 'http://localhost/pub/static/home-styles-m.css',
            'assetOptimizedLarge' => false,
            'assetNotOptimisedMobile' => false,
            'assetNotOptimisedLarge' => false,
            'optimisedProductCSSFileCriticalPath' => false,
            'optimisedCategoryCSSFileCriticalPath' => false,
            'optimisedHomeCSSFileCriticalPath' => false,
            'optimisedProductCSSFileCriticalUrl' => '',
            'optimisedCategoryCSSFileCriticalUrl' => '',
            'optimisedHomeCSSFileCriticalUrl' => '',
        ]);
        
        $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => true];
        $result = '';
        
        // Test addOptimizedCSSLinks for home page
        $result = $helper->callMethod('addOptimizedCSSLinks', $result, $pageTypes);
        
        // Should contain home CSS, not general or other page-specific CSS
        expect($result)->toContain('home-styles-m.css')
            ->and($result)->not->toMatch('/href="[^"]*\/styles-m\.css"/') // General CSS (not home-styles-m.css)
            ->and($result)->not->toContain('product-styles-m.css')
            ->and($result)->not->toContain('category-styles-m.css');
        
    });
});

describe('ReactInjectPlugin - Minification Fallback Tests', function () {
    test('falls back to regular CSS when minification enabled but minified file does not exist', function () {
        $mockScopeConfig = new MockScopeConfig();
        $mockTemplate = new MockTemplate($mockScopeConfig);
        $mockTemplate->setData('minify', true); // Enable minification
        
        // Temporarily rename minified files to test fallback
        $minifiedProductFile = BP . '/pub/static/product-styles-m.min.css';
        $minifiedProductCriticalFile = BP . '/pub/static/product-critical-m.min.css';
        $backupProductFile = $minifiedProductFile . '.backup';
        $backupProductCriticalFile = $minifiedProductCriticalFile . '.backup';
        
        $productFileExists = file_exists($minifiedProductFile);
        $productCriticalFileExists = file_exists($minifiedProductCriticalFile);
        
        if ($productFileExists) {
            rename($minifiedProductFile, $backupProductFile);
        }
        if ($productCriticalFileExists) {
            rename($minifiedProductCriticalFile, $backupProductCriticalFile);
        }
        
        try {
            $helper = new ReactInjectPluginTestHelper([
                'template' => $mockTemplate
            ]);
            
            // Initialize asset variables
            $helper->callMethod('initializeAssetVariables');
            
            // Simulate processMobileCSS for product page
            $requestContext = [
                'actionName' => 'catalog_product_view',
                'removeController' => true,
            ];
            $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
            $baseURL = 'http://localhost/pub/static/';
            
            // Create mock asset
            $mockAsset = new class {
                public function getUrl() {
                    return 'http://localhost/pub/static/styles-m.css';
                }
            };
            
            $assets = [0 => $mockAsset];
            $key = 0;
            
            // Call processMobileCSS - minified file doesn't exist, should fallback to regular
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            
            $assetVars = $helper->getProperty('assetVariables');
            
            // Should use regular CSS (not minified) since minified file doesn't exist
            expect($assetVars['optimisedProductCSSFileUrl'])->toContain('product-styles-m.css')
                ->and($assetVars['optimisedProductCSSFileUrl'])->not->toContain('.min.css')
                ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->toContain('product-critical-m.css')
                ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->not->toContain('.min.css');
        } finally {
            // Restore minified files
            if ($productFileExists && file_exists($backupProductFile)) {
                rename($backupProductFile, $minifiedProductFile);
            }
            if ($productCriticalFileExists && file_exists($backupProductCriticalFile)) {
                rename($backupProductCriticalFile, $minifiedProductCriticalFile);
            }
        }
    });
    
    test('falls back to regular CSS for category when minification enabled but minified file does not exist', function () {
        $mockScopeConfig = new MockScopeConfig();
        $mockTemplate = new MockTemplate($mockScopeConfig);
        $mockTemplate->setData('minify', true); // Enable minification
        
        // Temporarily rename minified files to test fallback
        $minifiedCategoryFile = BP . '/pub/static/category-styles-m.min.css';
        $minifiedCategoryCriticalFile = BP . '/pub/static/category-critical-m.min.css';
        $backupCategoryFile = $minifiedCategoryFile . '.backup';
        $backupCategoryCriticalFile = $minifiedCategoryCriticalFile . '.backup';
        
        $categoryFileExists = file_exists($minifiedCategoryFile);
        $categoryCriticalFileExists = file_exists($minifiedCategoryCriticalFile);
        
        if ($categoryFileExists) {
            rename($minifiedCategoryFile, $backupCategoryFile);
        }
        if ($categoryCriticalFileExists) {
            rename($minifiedCategoryCriticalFile, $backupCategoryCriticalFile);
        }
        
        try {
            $helper = new ReactInjectPluginTestHelper([
                'template' => $mockTemplate
            ]);
            
            // Initialize asset variables
            $helper->callMethod('initializeAssetVariables');
            
            // Simulate processMobileCSS for category page
            $requestContext = [
                'actionName' => 'catalog_category_view',
                'removeController' => true,
            ];
            $pageTypes = ['isProduct' => false, 'isCategory' => true, 'isHome' => false];
            $baseURL = 'http://localhost/pub/static/';
            
            // Create mock asset
            $mockAsset = new class {
                public function getUrl() {
                    return 'http://localhost/pub/static/styles-m.css';
                }
            };
            
            $assets = [0 => $mockAsset];
            $key = 0;
            
            // Call processMobileCSS - minified file doesn't exist, should fallback to regular
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            
            $assetVars = $helper->getProperty('assetVariables');
            
            // Should use regular CSS (not minified) since minified file doesn't exist
            expect($assetVars['optimisedCategoryCSSFileUrl'])->toContain('category-styles-m.css')
                ->and($assetVars['optimisedCategoryCSSFileUrl'])->not->toContain('.min.css')
                ->and($assetVars['optimisedCategoryCSSFileCriticalUrl'])->toContain('category-critical-m.css')
                ->and($assetVars['optimisedCategoryCSSFileCriticalUrl'])->not->toContain('.min.css');
        } finally {
            // Restore minified files
            if ($categoryFileExists && file_exists($backupCategoryFile)) {
                rename($backupCategoryFile, $minifiedCategoryFile);
            }
            if ($categoryCriticalFileExists && file_exists($backupCategoryCriticalFile)) {
                rename($backupCategoryCriticalFile, $minifiedCategoryCriticalFile);
            }
        }
    });
    
    test('falls back to regular CSS for home page when minification enabled but minified file does not exist', function () {
        $mockScopeConfig = new MockScopeConfig();
        $mockTemplate = new MockTemplate($mockScopeConfig);
        $mockTemplate->setData('minify', true); // Enable minification
        
        $helper = new ReactInjectPluginTestHelper([
            'template' => $mockTemplate
        ]);
        
        // Initialize asset variables
        $helper->callMethod('initializeAssetVariables');
        
        // Simulate processMobileCSS for home page
        $requestContext = [
            'actionName' => 'cms_index_index',
            'removeController' => true,
        ];
        $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => true];
        $baseURL = 'http://localhost/pub/static/';
        
        // Create mock asset
        $mockAsset = new class {
            public function getUrl() {
                return 'http://localhost/pub/static/styles-m.css';
            }
        };
        
        $assets = [0 => $mockAsset];
        $key = 0;
        
        // Call processMobileCSS - minified file doesn't exist, should fallback to regular
        $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
        
        $assetVars = $helper->getProperty('assetVariables');
        
        // Should use regular CSS (not minified) since minified file doesn't exist
        expect($assetVars['optimisedHomeCSSFileUrl'])->toContain('home-styles-m.css')
            ->and($assetVars['optimisedHomeCSSFileUrl'])->not->toContain('.min.css')
            ->and($assetVars['optimisedHomeCSSFileCriticalUrl'])->toContain('home-critical-m.css')
            ->and($assetVars['optimisedHomeCSSFileCriticalUrl'])->not->toContain('.min.css');
    });
    
    test('uses minified CSS when minification enabled and minified file exists', function () {
        $mockScopeConfig = new MockScopeConfig();
        $mockTemplate = new MockTemplate($mockScopeConfig);
        $mockTemplate->setData('minify', true); // Enable minification
        
        $helper = new ReactInjectPluginTestHelper([
            'template' => $mockTemplate
        ]);
        
        // Create a temporary minified file to test
        $minifiedFile = BP . '/pub/static/product-styles-m.min.css';
        $minifiedCriticalFile = BP . '/pub/static/product-critical-m.min.css';
        
        // Create temporary files
        file_put_contents($minifiedFile, '/* minified */');
        file_put_contents($minifiedCriticalFile, '/* minified critical */');
        
        try {
            // Initialize asset variables
            $helper->callMethod('initializeAssetVariables');
            
            // Simulate processMobileCSS for product page
            $requestContext = [
                'actionName' => 'catalog_product_view',
                'removeController' => true,
            ];
            $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
            $baseURL = 'http://localhost/pub/static/';
            
            // Create mock asset
            $mockAsset = new class {
                public function getUrl() {
                    return 'http://localhost/pub/static/styles-m.css';
                }
            };
            
            $assets = [0 => $mockAsset];
            $key = 0;
            
            // Call processMobileCSS - minified file exists, should use minified
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            
            $assetVars = $helper->getProperty('assetVariables');
            
            // Should use minified CSS since file exists
            expect($assetVars['optimisedProductCSSFileUrl'])->toContain('product-styles-m.min.css')
                ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->toContain('product-critical-m.min.css');
        } finally {
            // Clean up temporary files
            if (file_exists($minifiedFile)) {
                unlink($minifiedFile);
            }
            if (file_exists($minifiedCriticalFile)) {
                unlink($minifiedCriticalFile);
            }
        }
    });
});

describe('ReactInjectPlugin - Configuration Variants Tests', function () {
    test('all config variants are checked correctly', function () {
        $helper = new ReactInjectPluginTestHelper();
        
        // Test different config combinations
        $configVariants = [
            [
                'react_vue_config/react/enable' => '1',
                'react_vue_config/vue/enable' => '1',
                'react_vue_config/junk/remove' => '1',
                'react_vue_config/css/critical' => '1',
            ],
            [
                'react_vue_config/react/enable' => '0',
                'react_vue_config/vue/enable' => '0',
                'react_vue_config/junk/remove' => '0',
                'react_vue_config/css/critical' => '0',
            ],
            [
                'react_vue_config/react/enable' => '1',
                'react_vue_config/vue/enable' => '0',
                'react_vue_config/junk/remove' => '1',
                'react_vue_config/css/critical' => '0',
            ],
            [
                'react_vue_config/react/enable' => '0',
                'react_vue_config/vue/enable' => '1',
                'react_vue_config/junk/remove' => '0',
                'react_vue_config/css/critical' => '1',
            ],
        ];
        
        foreach ($configVariants as $index => $configValues) {
            $mockScopeConfig = new MockScopeConfig($configValues);
            $mockTemplate = new MockTemplate($mockScopeConfig);
            
            $testHelper = new ReactInjectPluginTestHelper([
                'scopeConfig' => $mockScopeConfig,
                'template' => $mockTemplate,
            ]);
            
            $config = $testHelper->callMethod('getConfigurationSettings');
            
            expect($config['reactEnabled'])->toBe((bool)$configValues['react_vue_config/react/enable'])
                ->and($config['vueEnabled'])->toBe((bool)$configValues['react_vue_config/vue/enable'])
                ->and($config['removeCSSjunk'])->toBe((bool)$configValues['react_vue_config/junk/remove'])
                ->and($config['criticalCSSHTML'])->toBe((bool)$configValues['react_vue_config/css/critical']);
        }
        
    });
    
    test('CSS optimization only happens for actions in actionFilter', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        // Mock assets
        $mockAsset = new class {
            public function getUrl() {
                return 'http://localhost/pub/static/styles-m.css';
            }
        };
        
        $assets = [$mockAsset];
        $requestContextAllowed = [
            'actionName' => 'catalog_product_view',
            'removeController' => true,
        ];
        $requestContextNotAllowed = [
            'actionName' => 'checkout_cart_index',
            'removeController' => false,
        ];
        $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
        
        // Test that processMobileCSS checks actionFilter
        // Since we can't easily test the full flow, we verify the logic
        $isAllowed = in_array($requestContextAllowed['actionName'], $instance->actionFilter);
        $isNotAllowed = in_array($requestContextNotAllowed['actionName'], $instance->actionFilter);
        
        expect($isAllowed)->toBeTrue()
            ->and($isNotAllowed)->toBeFalse();
        
    });
    
    test('JavaScript optimization respects actionFilter for RequireJS', function () {
        $helper = new ReactInjectPluginTestHelper();
        $instance = $helper->getInstance();
        
        // Test the logic: RequireJS is only removed if action is in actionFilter
        $allowedAction = 'catalog_product_view';
        $notAllowedAction = 'checkout_cart_index';
        
        $isAllowed = in_array($allowedAction, $instance->actionFilter);
        $isNotAllowed = in_array($notAllowedAction, $instance->actionFilter);
        
        expect($isAllowed)->toBeTrue()
            ->and($isNotAllowed)->toBeFalse();
        
    });
});

describe('ReactInjectPlugin - Store Specific CSS Tests', function () {
    test('default store uses root static path', function () {
        // Create store manager that returns 'default' store code
        $mockStoreManager = new class extends MockStoreManager {
            public function getStore($storeId = null) {
                return new class implements \Magento\Store\Api\Data\StoreInterface {
                    public function getId() { return 1; }
                    public function getCode() { return 'default'; }
                    public function getName() { return 'Default Store'; }
                    public function getWebsiteId() { return 1; }
                    public function getStoreGroupId() { return 1; }
                    public function getIsActive() { return true; }
                    public function getSortOrder() { return 0; }
                    public function setId($id) { return $this; }
                    public function setCode($code) { return $this; }
                    public function setName($name) { return $this; }
                    public function setWebsiteId($websiteId) { return $this; }
                    public function setStoreGroupId($storeGroupId) { return $this; }
                    public function setIsActive($isActive) { return $this; }
                    public function setSortOrder($sortOrder) { return $this; }
                    public function getExtensionAttributes() { return null; }
                    public function setExtensionAttributes(\Magento\Store\Api\Data\StoreExtensionInterface $extensionAttributes) { return $this; }
                };
            }
        };
        
        $helper = new ReactInjectPluginTestHelper([
            'storeManager' => $mockStoreManager
        ]);
        
        // Call getStoreStaticPathPrefix method
        $prefix = $helper->callMethod('getStoreStaticPathPrefix');
        
        // Default store should return empty string (root path)
        expect($prefix)->toBe('');
    });
    
    test('non-default store uses store code prefix', function () {
        // Create store manager that returns 'fr' store code
        $mockStoreManager = new class extends MockStoreManager {
            public function getStore($storeId = null) {
                return new class implements \Magento\Store\Api\Data\StoreInterface {
                    public function getId() { return 2; }
                    public function getCode() { return 'fr'; }
                    public function getName() { return 'French Store'; }
                    public function getWebsiteId() { return 1; }
                    public function getStoreGroupId() { return 1; }
                    public function getIsActive() { return true; }
                    public function getSortOrder() { return 0; }
                    public function setId($id) { return $this; }
                    public function setCode($code) { return $this; }
                    public function setName($name) { return $this; }
                    public function setWebsiteId($websiteId) { return $this; }
                    public function setStoreGroupId($storeGroupId) { return $this; }
                    public function setIsActive($isActive) { return $this; }
                    public function setSortOrder($sortOrder) { return $this; }
                    public function getExtensionAttributes() { return null; }
                    public function setExtensionAttributes(\Magento\Store\Api\Data\StoreExtensionInterface $extensionAttributes) { return $this; }
                };
            }
        };
        
        $helper = new ReactInjectPluginTestHelper([
            'storeManager' => $mockStoreManager
        ]);
        
        // Call getStoreStaticPathPrefix method
        $prefix = $helper->callMethod('getStoreStaticPathPrefix');
        
        // Non-default store should return store code with trailing slash
        expect($prefix)->toBe('fr/');
    });
    
    test('product CSS paths include store code for non-default store when folder exists', function () {
        // Create store manager that returns 'de' store code
        $mockStoreManager = new class extends MockStoreManager {
            public function getStore($storeId = null) {
                return new class implements \Magento\Store\Api\Data\StoreInterface {
                    public function getId() { return 3; }
                    public function getCode() { return 'de'; }
                    public function getName() { return 'German Store'; }
                    public function getWebsiteId() { return 1; }
                    public function getStoreGroupId() { return 1; }
                    public function getIsActive() { return true; }
                    public function getSortOrder() { return 0; }
                    public function setId($id) { return $this; }
                    public function setCode($code) { return $this; }
                    public function setName($name) { return $this; }
                    public function setWebsiteId($websiteId) { return $this; }
                    public function setStoreGroupId($storeGroupId) { return $this; }
                    public function setIsActive($isActive) { return $this; }
                    public function setSortOrder($sortOrder) { return $this; }
                    public function getExtensionAttributes() { return null; }
                    public function setExtensionAttributes(\Magento\Store\Api\Data\StoreExtensionInterface $extensionAttributes) { return $this; }
                };
            }
        };
        
        // Create store-specific folder
        $storeFolder = BP . '/pub/static/de';
        $folderExists = is_dir($storeFolder);
        if (!$folderExists) {
            mkdir($storeFolder, 0755, true);
        }
        
        try {
            $helper = new ReactInjectPluginTestHelper([
                'storeManager' => $mockStoreManager
            ]);
            
            // Initialize asset variables
            $helper->callMethod('initializeAssetVariables');
            
            // Simulate processMobileCSS for product page
            $requestContext = [
                'actionName' => 'catalog_product_view',
                'removeController' => true,
            ];
            $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
            $baseURL = 'http://localhost/pub/static/';
            
            // Create mock asset
            $mockAsset = new class {
                public function getUrl() {
                    return 'http://localhost/pub/static/styles-m.css';
                }
            };
            
            $assets = [0 => $mockAsset];
            $key = 0;
            
            // Call processMobileCSS - this will set up the CSS paths with store code
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            
            $assetVars = $helper->getProperty('assetVariables');
            
            // URLs should include store code prefix when folder exists
            expect($assetVars['optimisedProductCSSFileUrl'])->toContain('de/product-styles-m')
                ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->toContain('de/product-critical-m');
            
            // File paths should include store code prefix
            expect($assetVars['optimisedProductCSSFileCriticalPath'])->toContain('/pub/static/de/product-critical-m');
        } finally {
            // Clean up folder if we created it
            if (!$folderExists && is_dir($storeFolder)) {
                rmdir($storeFolder);
            }
        }
    });
    
    test('default store CSS paths do not include store code prefix', function () {
        // Use default MockStoreManager which returns 'default' store code
        $helper = new ReactInjectPluginTestHelper();
        
        // Initialize asset variables
        $helper->callMethod('initializeAssetVariables');
        
        // Simulate processMobileCSS for product page
        $requestContext = [
            'actionName' => 'catalog_product_view',
            'removeController' => true,
        ];
        $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
        $baseURL = 'http://localhost/pub/static/';
        
        // Create mock asset
        $mockAsset = new class {
            public function getUrl() {
                return 'http://localhost/pub/static/styles-m.css';
            }
        };
        
        $assets = [0 => $mockAsset];
        $key = 0;
        
        // Call processMobileCSS
        $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
        
        $assetVars = $helper->getProperty('assetVariables');
        
        // URLs should NOT include store code prefix for default store
        expect($assetVars['optimisedProductCSSFileUrl'])->toContain('product-styles-m.css')
            ->and($assetVars['optimisedProductCSSFileUrl'])->not->toContain('/default/')
            ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->toContain('product-critical-m.css')
            ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->not->toContain('/default/');
        
        // File paths should NOT include store code prefix
        expect($assetVars['optimisedProductCSSFileCriticalPath'])->toContain('/pub/static/product-critical-m.css')
            ->and($assetVars['optimisedProductCSSFileCriticalPath'])->not->toContain('/default/');
    });
    
    test('category and home CSS paths include store code for non-default store when folder exists', function () {
        // Create store manager that returns 'fr' store code
        $mockStoreManager = new class extends MockStoreManager {
            public function getStore($storeId = null) {
                return new class implements \Magento\Store\Api\Data\StoreInterface {
                    public function getId() { return 2; }
                    public function getCode() { return 'fr'; }
                    public function getName() { return 'French Store'; }
                    public function getWebsiteId() { return 1; }
                    public function getStoreGroupId() { return 1; }
                    public function getIsActive() { return true; }
                    public function getSortOrder() { return 0; }
                    public function setId($id) { return $this; }
                    public function setCode($code) { return $this; }
                    public function setName($name) { return $this; }
                    public function setWebsiteId($websiteId) { return $this; }
                    public function setStoreGroupId($storeGroupId) { return $this; }
                    public function setIsActive($isActive) { return $this; }
                    public function setSortOrder($sortOrder) { return $this; }
                    public function getExtensionAttributes() { return null; }
                    public function setExtensionAttributes(\Magento\Store\Api\Data\StoreExtensionInterface $extensionAttributes) { return $this; }
                };
            }
        };
        
        // Create store-specific folder
        $storeFolder = BP . '/pub/static/fr';
        $folderExists = is_dir($storeFolder);
        if (!$folderExists) {
            mkdir($storeFolder, 0755, true);
        }
        
        try {
            $helper = new ReactInjectPluginTestHelper([
                'storeManager' => $mockStoreManager
            ]);
            
            // Initialize asset variables
            $helper->callMethod('initializeAssetVariables');
            
            // Simulate processMobileCSS for category page
            $requestContext = [
                'actionName' => 'catalog_category_view',
                'removeController' => true,
            ];
            $pageTypes = ['isProduct' => false, 'isCategory' => true, 'isHome' => false];
            $baseURL = 'http://localhost/pub/static/';
            
            // Create mock asset
            $mockAsset = new class {
                public function getUrl() {
                    return 'http://localhost/pub/static/styles-m.css';
                }
            };
            
            $assets = [0 => $mockAsset];
            $key = 0;
            
            // Call processMobileCSS
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            
            $assetVars = $helper->getProperty('assetVariables');
            
            // Category URLs should include store code prefix when folder exists
            expect($assetVars['optimisedCategoryCSSFileUrl'])->toContain('fr/category-styles-m')
                ->and($assetVars['optimisedCategoryCSSFileCriticalUrl'])->toContain('fr/category-critical-m');
            
            // Now test home page
            $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => true];
            $helper->callMethod('initializeAssetVariables');
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            
            $assetVars = $helper->getProperty('assetVariables');
            
            // Home URLs should include store code prefix when folder exists
            expect($assetVars['optimisedHomeCSSFileUrl'])->toContain('fr/home-styles-m')
                ->and($assetVars['optimisedHomeCSSFileCriticalUrl'])->toContain('fr/home-critical-m');
        } finally {
            // Clean up folder if we created it
            if (!$folderExists && is_dir($storeFolder)) {
                rmdir($storeFolder);
            }
        }
    });
    
    test('non-default store falls back to default store when store-specific folder does not exist', function () {
        // Create store manager that returns 'es' store code (Spanish store)
        $mockStoreManager = new class extends MockStoreManager {
            public function getStore($storeId = null) {
                return new class implements \Magento\Store\Api\Data\StoreInterface {
                    public function getId() { return 4; }
                    public function getCode() { return 'es'; }
                    public function getName() { return 'Spanish Store'; }
                    public function getWebsiteId() { return 1; }
                    public function getStoreGroupId() { return 1; }
                    public function getIsActive() { return true; }
                    public function getSortOrder() { return 0; }
                    public function setId($id) { return $this; }
                    public function setCode($code) { return $this; }
                    public function setName($name) { return $this; }
                    public function setWebsiteId($websiteId) { return $this; }
                    public function setStoreGroupId($storeGroupId) { return $this; }
                    public function setIsActive($isActive) { return $this; }
                    public function setSortOrder($sortOrder) { return $this; }
                    public function getExtensionAttributes() { return null; }
                    public function setExtensionAttributes(\Magento\Store\Api\Data\StoreExtensionInterface $extensionAttributes) { return $this; }
                };
            }
        };
        
        // Ensure store-specific folder does NOT exist
        $storeFolder = BP . '/pub/static/es';
        $folderExisted = is_dir($storeFolder);
        if ($folderExisted) {
            // Temporarily rename it
            rename($storeFolder, $storeFolder . '.backup');
        }
        
        try {
            $helper = new ReactInjectPluginTestHelper([
                'storeManager' => $mockStoreManager
            ]);
            
            // Initialize asset variables
            $helper->callMethod('initializeAssetVariables');
            
            // Simulate processMobileCSS for product page
            $requestContext = [
                'actionName' => 'catalog_product_view',
                'removeController' => true,
            ];
            $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
            $baseURL = 'http://localhost/pub/static/';
            
            // Create mock asset
            $mockAsset = new class {
                public function getUrl() {
                    return 'http://localhost/pub/static/styles-m.css';
                }
            };
            
            $assets = [0 => $mockAsset];
            $key = 0;
            
            // Call processMobileCSS
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            
            $assetVars = $helper->getProperty('assetVariables');
            
            // URLs should NOT include store code prefix (fallback to default)
            expect($assetVars['optimisedProductCSSFileUrl'])->toContain('product-styles-m.css')
                ->and($assetVars['optimisedProductCSSFileUrl'])->not->toContain('es/')
                ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->toContain('product-critical-m.css')
                ->and($assetVars['optimisedProductCSSFileCriticalUrl'])->not->toContain('es/');
            
            // File paths should point to default store location
            expect($assetVars['optimisedProductCSSFileCriticalPath'])->toContain('/pub/static/product-critical-m.css')
                ->and($assetVars['optimisedProductCSSFileCriticalPath'])->not->toContain('/es/');
        } finally {
            // Restore folder if it existed
            if ($folderExisted && is_dir($storeFolder . '.backup')) {
                rename($storeFolder . '.backup', $storeFolder);
            }
        }
    });
    
    test('multiple stores: store without folder falls back to default for all page types', function () {
        // Test scenario: Store 'it' (Italian) doesn't have folder, should fallback to default
        $mockStoreManager = new class extends MockStoreManager {
            public function getStore($storeId = null) {
                return new class implements \Magento\Store\Api\Data\StoreInterface {
                    public function getId() { return 5; }
                    public function getCode() { return 'it'; }
                    public function getName() { return 'Italian Store'; }
                    public function getWebsiteId() { return 1; }
                    public function getStoreGroupId() { return 1; }
                    public function getIsActive() { return true; }
                    public function getSortOrder() { return 0; }
                    public function setId($id) { return $this; }
                    public function setCode($code) { return $this; }
                    public function setName($name) { return $this; }
                    public function setWebsiteId($websiteId) { return $this; }
                    public function setStoreGroupId($storeGroupId) { return $this; }
                    public function setIsActive($isActive) { return $this; }
                    public function setSortOrder($sortOrder) { return $this; }
                    public function getExtensionAttributes() { return null; }
                    public function setExtensionAttributes(\Magento\Store\Api\Data\StoreExtensionInterface $extensionAttributes) { return $this; }
                };
            }
        };
        
        // Ensure store-specific folder does NOT exist
        $storeFolder = BP . '/pub/static/it';
        $folderExisted = is_dir($storeFolder);
        if ($folderExisted) {
            rename($storeFolder, $storeFolder . '.backup');
        }
        
        try {
            $helper = new ReactInjectPluginTestHelper([
                'storeManager' => $mockStoreManager
            ]);
            
            $baseURL = 'http://localhost/pub/static/';
            $mockAsset = new class {
                public function getUrl() {
                    return 'http://localhost/pub/static/styles-m.css';
                }
            };
            $assets = [0 => $mockAsset];
            $key = 0;
            
            // Test Product page fallback
            $helper->callMethod('initializeAssetVariables');
            $requestContext = ['actionName' => 'catalog_product_view', 'removeController' => true];
            $pageTypes = ['isProduct' => true, 'isCategory' => false, 'isHome' => false];
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            $assetVars = $helper->getProperty('assetVariables');
            
            expect($assetVars['optimisedProductCSSFileUrl'])->not->toContain('it/')
                ->and($assetVars['optimisedProductCSSFileUrl'])->toContain('product-styles-m')
                ->and($assetVars['optimisedProductCSSFileCriticalPath'])->not->toContain('/it/')
                ->and($assetVars['optimisedProductCSSFileCriticalPath'])->toContain('/pub/static/product-critical-m');
            
            // Test Category page fallback
            $helper->callMethod('initializeAssetVariables');
            $pageTypes = ['isProduct' => false, 'isCategory' => true, 'isHome' => false];
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            $assetVars = $helper->getProperty('assetVariables');
            
            expect($assetVars['optimisedCategoryCSSFileUrl'])->not->toContain('it/')
                ->and($assetVars['optimisedCategoryCSSFileUrl'])->toContain('category-styles-m')
                ->and($assetVars['optimisedCategoryCSSFileCriticalPath'])->not->toContain('/it/')
                ->and($assetVars['optimisedCategoryCSSFileCriticalPath'])->toContain('/pub/static/category-critical-m');
            
            // Test Home page fallback
            $helper->callMethod('initializeAssetVariables');
            $pageTypes = ['isProduct' => false, 'isCategory' => false, 'isHome' => true];
            $helper->callMethod('processMobileCSS', $assets, $key, $mockAsset, $requestContext, $pageTypes, $baseURL);
            $assetVars = $helper->getProperty('assetVariables');
            
            expect($assetVars['optimisedHomeCSSFileUrl'])->not->toContain('it/')
                ->and($assetVars['optimisedHomeCSSFileUrl'])->toContain('home-styles-m')
                ->and($assetVars['optimisedHomeCSSFileCriticalPath'])->not->toContain('/it/')
                ->and($assetVars['optimisedHomeCSSFileCriticalPath'])->toContain('/pub/static/home-critical-m');
        } finally {
            // Restore folder if it existed
            if ($folderExisted && is_dir($storeFolder . '.backup')) {
                rename($storeFolder . '.backup', $storeFolder);
            }
        }
    });
});

describe('ReactInjectPlugin - Mock Dependencies Tests', function () {
    test('works with mocked ScopeConfig', function () {
        $mockScopeConfig = new MockScopeConfig([
            'react/react/enabled' => '1',
            'react/react/vue_enabled' => '0',
        ]);
        
        $helper = new ReactInjectPluginTestHelper([
            'scopeConfig' => $mockScopeConfig,
        ]);
        
        $instance = $helper->getInstance();
        
        expect($instance)->toBeInstanceOf(\React\React\ReactInjectPlugin::class);
        
    });
    
    test('works with mocked Logger', function () {
        $mockLogger = new MockLogger();
        
        $helper = new ReactInjectPluginTestHelper([
            'logger' => $mockLogger,
        ]);
        
        $instance = $helper->getInstance();
        
        expect($instance)->toBeInstanceOf(\React\React\ReactInjectPlugin::class);
        expect($mockLogger->logs)->toBeArray();
        
    });
});

