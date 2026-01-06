<?php
/**
 * Pest test for React\React\DeferJS
 * Tests styles-l.css deferral logic with ACTUAL class and mocked dependencies
 * 
 * Run: vendor/bin/pest Unit/DeferJS.test.php
 * 
 * APPROACH: Hybrid bootstrap (bootstrap.php)
 * - Loads Pest's PHPUnit 10+ first
 * - Then loads Magento classes via custom autoloader (skips PHPUnit)
 * - Both coexist without conflicts!
 * - We use the ACTUAL DeferJS class
 * - Dependencies are mocked
 * - NO method copying needed!
 */

/**
 * Uses the ACTUAL DeferJS class from Magento!
 * NO method copying - tests the real implementation directly!
 * 
 * The hybrid bootstrap loads both Pest's PHPUnit and Magento classes successfully
 */
class DeferJSTestHelper
{
    private $actualInstance;
    private $reflection;
    
    public function __construct($dependencies = [])
    {
        // Create mock dependencies if not provided
        $scopeConfig = $dependencies['scopeConfig'] ?? new MockScopeConfig();
        
        // Create the ACTUAL DeferJS instance from Magento!
        // The bootstrap autoloader will handle loading interfaces
        $this->actualInstance = new \React\React\DeferJS($scopeConfig);
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
     * Convenience methods for testing private methods
     */
    public function shouldDeferJS(): bool
    {
        return $this->callMethod('shouldDeferJS');
    }
}

// Mock classes that implement actual Magento interfaces
// MockScopeConfig is loaded from Unit/Mocks.php

beforeEach(function () {
    $this->helper = new DeferJSTestHelper();
});

test('shouldDeferJS returns false when GET parameter defer-js is false', function () {
    $_GET['defer-js'] = 'false';
    $result = $this->helper->callMethod('shouldDeferJS');
    unset($_GET['defer-js']);
    
    expect($result)->toBeFalse();
});

test('shouldDeferJS returns true when GET parameter defer-js is true', function () {
    $_GET['defer-js'] = 'true';
    $result = $this->helper->callMethod('shouldDeferJS');
    unset($_GET['defer-js']);
    
    expect($result)->toBeTrue();
});

test('shouldDeferJS uses config value when GET parameter not set', function () {
    // Clear GET parameter
    unset($_GET['defer-js']);
    
    // Test with config disabled
    $helperDisabled = new DeferJSTestHelper([
        'scopeConfig' => new MockScopeConfig(['react_vue_config/junk/defer_js' => '0'])
    ]);
    expect($helperDisabled->callMethod('shouldDeferJS'))->toBeFalse();
    
    // Test with config enabled
    $helperEnabled = new DeferJSTestHelper([
        'scopeConfig' => new MockScopeConfig(['react_vue_config/junk/defer_js' => '1'])
    ]);
    expect($helperEnabled->callMethod('shouldDeferJS'))->toBeTrue();
});

test('shouldDeferJS defaults to true when config is not set', function () {
    // Clear GET parameter
    unset($_GET['defer-js']);
    
    // Test with config not set (null)
    $helperDefault = new DeferJSTestHelper([
        'scopeConfig' => new MockScopeConfig([])
    ]);
    expect($helperDefault->callMethod('shouldDeferJS'))->toBeTrue();
});


