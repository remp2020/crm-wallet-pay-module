<?php

namespace Crm\WalletPayModule;

use Crm\ApiModule\Models\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Models\Authorization\NoAuthorization;
use Crm\ApiModule\Models\Router\ApiIdentifier;
use Crm\ApiModule\Models\Router\ApiRoute;
use Crm\ApplicationModule\AssetsManager;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\DataProvider\DataProviderManager;
use Crm\ApplicationModule\SeederManager;
use Crm\WalletPayModule\Api\ApplePayMerchantValidationHandler;
use Crm\WalletPayModule\DataProviders\WalletPayTokensDataProvider;
use Crm\WalletPayModule\Seeders\ConfigsSeeder;
use Crm\WalletPayModule\Seeders\PaymentGatewaysSeeder;

class WalletPayModule extends CrmModule
{
    public function registerAssets(AssetsManager $assetsManager)
    {
        $assetsManager->copyAssets(__DIR__ . '/assets/dist/js', 'layouts/wallet-pay-module/js');
        $assetsManager->copyAssets(__DIR__ . '/assets/img/', 'layouts/wallet-pay-module/img');
    }

    public function registerSeeders(SeederManager $seederManager)
    {
        $seederManager->addSeeder($this->getInstance(ConfigsSeeder::class));
        $seederManager->addSeeder($this->getInstance(PaymentGatewaysSeeder::class));
    }

    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(
                new ApiIdentifier('1', 'apple-pay', 'merchant-validation'),
                ApplePayMerchantValidationHandler::class,
                NoAuthorization::class
            )
        );
    }

    public function registerDataProviders(DataProviderManager $dataProviderManager)
    {
        $dataProviderManager->registerDataProvider(
            'salesfunnel.dataprovider.payment_form_data',
            $this->getInstance(WalletPayTokensDataProvider::class)
        );
    }
}
