<?php
declare(strict_types = 1);

namespace Opengento\EarlyHints\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\HeaderManager;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendHeaders implements ObserverInterface
{
    /**
     * @var Http
     */
    protected Http $httpResponse;

    /**
     * @var HeaderManager
     */
    protected HeaderManager $headerManager;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param Http                 $httpResponse
     * @param HeaderManager        $headerManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Http $httpResponse,
        HeaderManager $headerManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->httpResponse  = $httpResponse;
        $this->headerManager = $headerManager;
        $this->scopeConfig   = $scopeConfig;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $httpResponse = $this->httpResponse;

        //Set default header to disable buffering
        $this->setDefaultHeaderToDisableBuffering($httpResponse);

        //Add preload link headers
        $this->addPreloadLinkHeaders($httpResponse);

        //Calculate httpResponse headers based on HeaderProviders
        $this->headerManager->beforeSendResponse($httpResponse);

        //@todo: add headers coming from CSP module (or other module with same architecture)

        //Send all headers to browser
        $this->sendAllHeadersToBrowser($httpResponse);
    }

    /**
     * @param $type
     *
     * @return string[]
     */
    protected function _getStaticUris($type): array
    {
        $value = $this->scopeConfig->getValue('opengento/earlyhint/uri_' . $type);

        return \explode("\n", $value);
    }

    /**
     * @param Http  $httpResponse
     *
     * @return void
     */
    protected function addPreloadLinkHeaders(Http $httpResponse): void
    {
        $stylesUri    = $this->_getStaticUris('style');
        $scriptsUri   = $this->_getStaticUris('script');

        $linkHeader = [];
        foreach ($stylesUri as $uri) {
            $linkHeader[] = "<{$uri}>; rel=preload; as=style";
        }

        //Loop scripts
        foreach ($scriptsUri as $uri) {
            $linkHeader[] = "<{$uri}>; rel=preload; as=script";
        }

        $httpResponse->setHeader('Link', \implode(',', $linkHeader));
    }

    /**
     * @param Http $httpResponse
     *
     * @return void
     */
    protected function setDefaultHeaderToDisableBuffering(Http $httpResponse): void
    {
        $httpResponse->setHeader('Early-Hints', 'true');
        $httpResponse->setHeader('X-Accel-Buffering', 'no'); // for nginx no buffering
        $httpResponse->setHeader(
            'Surrogate-Control',
            'OpengentoBroadcast/1.0'); // for varnish no buffering (vcl have to be modified, check Readme.md)
    }

    /**
     * @param Http $httpResponse
     *
     * @return void
     */
    protected function sendAllHeadersToBrowser(Http $httpResponse): void
    {
        $httpResponse->sendHeaders();

        //@todo : find a better way to trigger header to be sent to browser
        echo '<div></div>';
        flush();
    }
}
