<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCustomer\Service;

use Flexgrid\Modules\AdminCustomer\Application\ReadModel\CustomerDetailContext;
use Flexgrid\Modules\AdminCustomer\Contract\CustomerDetailExtensionInterface;

/** Ontdekt optionele klantdetailuitbreidingen zonder concrete module-import. */
final class CustomerDetailExtensionLoader
{
    /** @var string */ private $modulesRoot;
    /** @var string[] */ private $extensionClasses;

    public function __construct(string $modulesRoot, array $extensionClasses = [])
    {
        $this->modulesRoot = rtrim(str_replace('\\', '/', $modulesRoot), '/');
        $this->extensionClasses = $extensionClasses;
    }

    public function render(CustomerDetailContext $context): string
    {
        $html = '';
        foreach ($this->classes() as $class) {
            $state = '\\Flexgrid\\Modules\\AdminCore\\Integration\\Flexgrid\\Service\\AdminModuleState';
            $visibility = '\\Flexgrid\\Modules\\AdminCore\\Integration\\Flexgrid\\Service\\AdminModuleInterfaceVisibility';
            if ((class_exists($state) && !$state::isEnabledForClass($class))
                || (class_exists($visibility) && !$visibility::isVisibleForCurrentUserClass($class))) {
                continue;
            }
            if (!class_exists($class)) {
                continue;
            }
            $extension = new $class();
            if (!$extension instanceof CustomerDetailExtensionInterface) {
                throw new \LogicException($class . ' moet CustomerDetailExtensionInterface implementeren.');
            }
            $html .= $extension->render($context);
        }
        return $html;
    }

    /** @return string[] */
    private function classes(): array
    {
        if ($this->extensionClasses !== []) {
            return array_values(array_unique(array_filter($this->extensionClasses, 'is_string')));
        }
        if ($this->modulesRoot === '' || !is_dir($this->modulesRoot)) {
            return [];
        }

        $classes = [];
        foreach (glob($this->modulesRoot . '/*/src/Integration/Customer/CustomerDetailExtension.php') ?: [] as $file) {
            $module = basename(dirname($file, 4));
            if (preg_match('/^[A-Za-z][A-Za-z0-9]*$/D', $module) !== 1 || $module === 'AdminCustomer') {
                continue;
            }
            $classes[] = 'Flexgrid\\Modules\\' . $module . '\\Integration\\Customer\\CustomerDetailExtension';
        }
        sort($classes);
        return array_values(array_unique($classes));
    }
}
