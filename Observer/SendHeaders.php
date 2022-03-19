<?php
declare(strict_types = 1);

namespace Opengento\EarlyHints\Observer;

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
     * @param Http          $httpResponse
     * @param HeaderManager $headerManager
     */
    public function __construct(
        Http $httpResponse,
        HeaderManager $headerManager
    ) {
        $this->httpResponse  = $httpResponse;
        $this->headerManager = $headerManager;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        //@todo : possibilité de se mettre encore plus haut si on maitrise les headers envoyés !!!
        $httpResponse = $this->httpResponse;

        //Add 103 headers + link (based on config / cache, or anything)
        //@todo: dynamic links
        $httpResponse->setHeader('Early-Hints', 'true');
        $httpResponse->setHeader('Link', '</script.js>; rel=preload; as=script');

        $httpResponse->setHeader('X-Accel-Buffering', 'no'); // for nginx
        $httpResponse->setHeader('Surrogate-Control', 'OpengentoBroadcast/1.0'); // for varnish

        //Calculate httpResponse headers based on HeaderProviders
        $this->headerManager->beforeSendResponse($httpResponse);

        //$headers = $httpResponse->getHeaders();

        //@todo: add headers coming from CSP module (or other module with same architecture)


        //@todo: sendHeaders and fake body
        $httpResponse->sendHeaders();
        echo '...fake content to trigger headers to be sent to browser';
        flush();
        sleep(10); //then all the rest of magento code will be calculated (block, layout, ...)
        //@todo: cuttoff default sendHeaders on beforeSendResponse to avoid "headers already sent" error
        ///home/web/opengento/vendor/laminas/laminas-http/src/PhpEnvironment/Response.php::send() -> keep only sendContent()
    }
}
