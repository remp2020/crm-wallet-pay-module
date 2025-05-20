<?php

namespace Crm\WalletPayModule\DI;

use Nette\Application\IPresenterFactory;
use Nette\DI\CompilerExtension;

class WalletPayModuleExtension extends CompilerExtension
{
    public function loadConfiguration()
    {
        // load services from config and register them to Nette\DI Container
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__ . '/../config/config.neon')['services'],
        );
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        // load presenters from extension to Nette
        $builder->getDefinition($builder->getByType(IPresenterFactory::class))
            ->addSetup('setMapping', [['WalletPay' => 'Crm\WalletPayModule\Presenters\*Presenter']]);
    }
}
