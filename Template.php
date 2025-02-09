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
}
