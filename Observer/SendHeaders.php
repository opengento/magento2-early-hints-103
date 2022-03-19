<?php
declare(strict_types = 1);

namespace Opengento\EarlyHints\Observer;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http\Proxy;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HeaderManager;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;
use Opengento\EarlyHints\Model\Config;

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
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var LayoutInterface
     */
    protected LayoutInterface $layout;

    public function __construct(
        Http $httpResponse,
        HeaderManager $headerManager,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        Config $config,
        LayoutInterface $layout
    ) {
        $this->layout        = $layout;
        $this->config        = $config;
        $this->request       = $request;
        $this->httpResponse  = $httpResponse;
        $this->headerManager = $headerManager;
        $this->scopeConfig   = $scopeConfig;
    }

    public function execute(Observer $observer): void
    {
        try {
            $httpResponse = $this->httpResponse;
            $this->shouldAddLinkHeader($httpResponse, $observer->getEvent()->getControllerAction());

            //Set default header to disable buffering
            $this->setDefaultHeaderToDisableBuffering($httpResponse);

            //Add preload link headers
            $this->addPreloadLinkHeaders($httpResponse);

            //Calculate httpResponse headers based on HeaderProviders
            $this->headerManager->beforeSendResponse($httpResponse);

            //@todo: add headers coming from CSP module (or other module with same architecture)

            $this->sendAllHeadersToBrowser($httpResponse);
        } catch (\RuntimeException $e) {

        }
    }

    protected function _getStaticUris($type): array
    {
        $value = $this->config->getStaticUris($type);

        return \explode("\n", \str_replace("\r", "", $value));
    }

    protected function addPreloadLinkHeaders(Http $httpResponse): void
    {
        $stylesUri  = $this->_getStaticUris('style');
        $scriptsUri = $this->_getStaticUris('script');

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

    protected function setDefaultHeaderToDisableBuffering(Http $httpResponse): void
    {
        $httpResponse->setHeader('Early-Hints', 'true');
        $httpResponse->setHeader('X-Accel-Buffering', 'no'); // for nginx no buffering
        $httpResponse->setHeader(
            'Surrogate-Control',
            'OpengentoBroadcast/1.0'); // for varnish no buffering (vcl have to be modified, check Readme.md)
    }

    protected function sendAllHeadersToBrowser(Http $httpResponse): void
    {
        $httpResponse->sendHeaders();

        //@todo : find a better way to trigger header to be sent to browser
        echo '<div></div>';
        flush();
    }

    /**
     * @param Http $response
     * @throw \RuntimeException
     *
     * @return void
     */
    protected function shouldAddLinkHeader(Http $response, ActionInterface $action): void
    {
        if (!$this->config->isEnabled()) {
            throw new \RuntimeException('Early Hints config not enabled');
        }

        //check path info is allowed
        $this->checkPathInfoIsAllowed($action);

        if ($response->isRedirect()) {
            throw new \RuntimeException('Response is redirection');
        }

        if ($this->request instanceof Proxy && $this->request->isAjax()) {
            throw new \RuntimeException('Request is ajax');
        }
    }

    /**
     * @param ActionInterface $action
     *
     * @return void
     */
    protected function checkPathInfoIsAllowed(ActionInterface $action): void
    {
        $hasPathAllowed = false;
        $pathInfo       = $action->getRequest()->getPathInfo();
        if (!$pathInfo) {
            $pathInfo = '/' . $action->getRequest()->getModuleName() . '/' . $action->getRequest()->getControllerName() . '/' . $action->getRequest()->getActionName();
        }

        foreach ($this->config->getPathInfoAllowed() as $allowedPath) {
            if (\strpos($pathInfo, $allowedPath) !== false) {
                $hasPathAllowed = true;
            }
        }
        if (!$hasPathAllowed) {
            throw new \RuntimeException('Early Hint Layout Handles not allowed');
        }
    }
}
