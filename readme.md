The pub/index.php needs to be changed that way

```php
<?php
/**
 * Public alias for the application entry point
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\App\Bootstrap;

$filepath = __DIR__ . '/103links.txt';

if (file_exists($filepath)) {

    $fp = fopen($filepath, 'r');

    if ($fp) {

        header('HTTP/1.1 103 Early Hints');
        header('X-Accel-Buffering: no'); // for nginx
        header('Surrogate-Control: OpengentoBroadcast/1.0'); // for varnish

        while (($buffer = fgets($fp, 4096)) !== false) {
            header($buffer, false);
        }


        header('HTTP/1.1 200');
        flush(); // force send data
    }
    fclose($fp);
}

try {
    require __DIR__ . '/../app/bootstrap.php';
} catch (\Exception $e) {
    echo <<<HTML
<div style="font:12px/1.35em arial, helvetica, sans-serif;">
    <div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;">
        <h3 style="margin:0;font-size:1.7em;font-weight:normal;text-transform:none;text-align:left;color:#2f2f2f;">
        Autoload error</h3>
    </div>
    <p>{$e->getMessage()}</p>
</div>
HTML;
    http_response_code(500);
    exit(1);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
$bootstrap->run($app);

```