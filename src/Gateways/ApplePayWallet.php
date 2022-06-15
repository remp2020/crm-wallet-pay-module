<?php

namespace Crm\WalletPayModule\Gateways;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\PaymentsModule\CannotProcessPayment;
use Crm\PaymentsModule\Gateways\GatewayAbstract;
use Crm\PaymentsModule\Gateways\ProcessResponse;
use Crm\WalletPayModule\Model\ApplePayResult;
use Crm\WalletPayModule\Model\ApplePayWalletInterface;
use Crm\WalletPayModule\Repositories\WalletPayTokensRepository;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;
use Nette\Localization\Translator;
use Tracy\Debugger;

class ApplePayWallet extends GatewayAbstract
{
    public const GATEWAY_CODE = 'applepay_wallet';
    public const APPLE_PAY_TOKEN = 'apple_pay_token';

    private WalletPayTokensRepository $walletPayTokensRepository;
    private ApplePayWalletInterface $applePayWallet;
    private ?ApplePayResult $applePayResult;
    private ?string $variableSymbol;

    public function __construct(
        LinkGenerator $linkGenerator,
        ApplicationConfig $applicationConfig,
        Response $httpResponse,
        Translator $translator,
        ApplePayWalletInterface $applePayWallet,
        WalletPayTokensRepository $walletPayTokensRepository
    ) {
        parent::__construct($linkGenerator, $applicationConfig, $httpResponse, $translator);
        $this->applePayWallet = $applePayWallet;
        $this->walletPayTokensRepository = $walletPayTokensRepository;
    }

    public function begin($payment)
    {
        $applePayToken = $this->walletPayTokensRepository->findByPaymentAndType($payment, self::APPLE_PAY_TOKEN);
        if (!$applePayToken) {
            Debugger::log("Missing apple pay token for payment #{$payment->id}", Debugger::ERROR);
            return;
        }
        $this->variableSymbol = $payment->variable_symbol;
        $this->applePayResult = $this->applePayWallet->process($payment, $applePayToken);
    }

    public function complete($payment): ?bool
    {
        return null;
    }

    public function process($allowRedirect = true)
    {
        if (!$this->applePayResult || $this->applePayResult->isError()) {
            throw new CannotProcessPayment("ApplePayWallet - payment was not successful");
        }

        $responseData = [];

        $responseData['redirect_url'] = $this->linkGenerator->link('SalesFunnel:SalesFunnel:success', ['variableSymbol' => $this->variableSymbol]);

        return new ProcessResponse('apple_pay', $responseData);
    }

    public function isSuccessful(): bool
    {
        return isset($this->applePayResult) && $this->applePayResult->isOk();
    }

    public function getResponseData()
    {
        return isset($this->applePayResult) ? $this->applePayResult->meta() : [];
    }
}
