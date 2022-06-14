<?php

namespace Crm\WalletPayModule\DataProviders;

use Crm\ApplicationModule\DataProvider\DataProviderException;
use Crm\SalesFunnelModule\DataProvider\SalesFunnelPaymentFormDataProviderInterface;
use Crm\WalletPayModule\Gateways\ApplePayWallet;
use Crm\WalletPayModule\Gateways\GooglePayWallet;
use Crm\WalletPayModule\Repositories\WalletPayTokensRepository;

class WalletPayTokensDataProvider implements SalesFunnelPaymentFormDataProviderInterface
{
    private WalletPayTokensRepository $walletPayTokensRepository;

    public function __construct(WalletPayTokensRepository $walletPayTokensRepository)
    {
        $this->walletPayTokensRepository = $walletPayTokensRepository;
    }

    public function provide(array $params)
    {
        if (!isset($params['payment'])) {
            throw new DataProviderException('missing [payment] within data provider params');
        }
        if (!isset($params['post_data'])) {
            throw new DataProviderException('missing [post_data] within data provider params');
        }

        $payment = $params['payment'];
        $postData = $params['post_data'];

        if (isset($postData['google_pay_token'])) {
            $this->walletPayTokensRepository->add($payment, GooglePayWallet::GOOGLE_PAY_TOKEN, $postData['google_pay_token']);
        }
        if (isset($postData['apple_pay_token'])) {
            $this->walletPayTokensRepository->add($payment, ApplePayWallet::APPLE_PAY_TOKEN, $postData['apple_pay_token']);
        }
    }
}
