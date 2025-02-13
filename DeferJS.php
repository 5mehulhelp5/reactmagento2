<?php

namespace React\React;

use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Magento\Framework\Event\ObserverInterface;

class DeferJS implements ObserverInterface
{

    public function __construct(
        protected Config $config
    ) {
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $removeAdobeJSJunk = boolval($this->config->getValue('react_vue_config/junk/remove'));

        if ($removeAdobeJSJunk) {
            return;
        }
        $response = $observer->getEvent()->getData('response');
        if (!$response) {
            return;
        }
        $html = $response->getBody();
        if ($html == '') {
            return;
        }
        $conditionalJsPattern = '@(?:<script type="text/javascript"|<script)(.*)</script>@msU';
        preg_match_all($conditionalJsPattern, $html, $_matches);
        $jsHtml = implode('', $_matches[0]);
        $html = preg_replace($conditionalJsPattern, '', $html);
        $html .= $jsHtml;
        $response->setBody($html);
    }
}
