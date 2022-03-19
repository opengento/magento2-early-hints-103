<?php declare(strict_types = 1);

namespace Opengento\EarlyHints\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleListInterface;

class Config
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ModuleListInterface  $moduleList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ModuleListInterface $moduleList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->moduleList  = $moduleList;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getStaticUris(string $type): string
    {
        return $this->scopeConfig->getValue('system/opengento_earlyhints/uri_'.$type);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue('system/opengento_earlyhints/enabled');
    }

    public function getPathInfoAllowed(): array
    {
        return \explode("\n", (string) str_replace("\r", '', $this->scopeConfig->getValue('system/opengento_earlyhints/pathinfo_allowed')));
    }

    /**
     * @param string $module
     *
     * @return bool
     */
    private function isModuleEnabled(string $module): bool
    {
        return (bool) $this->moduleList->has($module);
    }
}
