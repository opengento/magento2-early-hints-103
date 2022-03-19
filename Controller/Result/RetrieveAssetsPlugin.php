<?php
/**
 * @author Christophe Ferreboeuf <christophe@crealoz.fr>
 */

namespace Opengento\EarlyHints\Controller\Result;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Layout;

class RetrieveAssetsPlugin
{

    private \Magento\Framework\Filesystem\DirectoryList $directoryList;

    public function __construct(\Magento\Framework\Filesystem\DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }


    public function afterRenderResult(Layout $subject, Layout $result, ResponseInterface $httpResponse)
    {
        $filePath = $this->directoryList->getPath('pub') . DIRECTORY_SEPARATOR . '103links.txt';
        if (file_exists($filePath)) {

            /** @var \Magento\Framework\App\Response\Http $httpResponse */
            $content = (string)$httpResponse->getContent();
            /**
             * Let's retrieve css links
             */
            preg_match_all('/<link .* href="(.*)".*\/>/i', $content, $matches, PREG_SET_ORDER);

            $cssLinks = [];
            foreach ($matches as $cssLink) {
                $cssLinks[] = $cssLink[1];
            }

            $links = 'Link: <' . implode(',', $cssLinks) . '>; rel=preload; as=style' . PHP_EOL;

            /**
             * Then js src
             */
            preg_match_all('/<script.*src="(.*)".*>/i', $content, $matches, PREG_SET_ORDER);

            $jsSrcs = [];
            foreach ($matches as $jsSrc) {
                $jsSrcs[] = $jsSrc[1];
            }

            $links .= 'Link: <' . implode(',', $jsSrcs) . '>; rel=preload; as=script';
            @touch($filePath);
            if (is_writable($filePath)) {
                if (!$fp = fopen($filePath, 'w')) {
                    exit;
                }

                if (fwrite($fp, $links) === false) {
                    exit;
                }

                fclose($fp);
            }
        }
    }
}
