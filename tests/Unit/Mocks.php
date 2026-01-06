<?php
/**
 * Shared mock classes for tests
 * Prevents duplicate class declarations when running all tests
 */

if (!class_exists('MockScopeConfig')) {
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
        
        public function setValue($path, $value)
        {
            $this->values[$path] = $value;
        }
    }
}
