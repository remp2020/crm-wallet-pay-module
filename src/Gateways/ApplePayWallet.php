<?php

namespace Crm\WalletPayModule\Gateways;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\PaymentsModule\Models\CannotProcessPayment;
use Crm\PaymentsModule\Models\Gateways\GatewayAbstract;
use Crm\PaymentsModule\Models\Gateways\ProcessResponse;
use Crm\PaymentsModule\Repositories\PaymentMetaRepository;
use Crm\WalletPayModule\Model\ApplePayResult;
use Crm\WalletPayModule\Model\ApplePayWalletInterface;
use Crm\WalletPayModule\Model\Constants;
use Crm\WalletPayModule\Repositories\WalletPayTokensRepository;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;
use Nette\Localization\Translator;
use Tracy\Debugger;

class ApplePayWallet extends GatewayAbstract
{
    public const GATEWAY_CODE = 'applepay_wallet';
    public const APPLE_PAY_TOKEN = 'apple_pay_token';

    private PaymentMetaRepository $paymentMetaRepository;
    private WalletPayTokensRepository $walletPayTokensRepository;
    private ApplePayWalletInterface $applePayWallet;
    private ?ApplePayResult $applePayResult;
    private $payment;

    public function __construct(
        LinkGenerator $linkGenerator,
        ApplicationConfig $applicationConfig,
        Response $httpResponse,
        Translator $translator,
        ApplePayWalletInterface $applePayWallet,
        WalletPayTokensRepository $walletPayTokensRepository,
        PaymentMetaRepository $paymentMetaRepository
    ) {
        parent::__construct($linkGenerator, $applicationConfig, $httpResponse, $translator);
        $this->applePayWallet = $applePayWallet;
        $this->walletPayTokensRepository = $walletPayTokensRepository;
        $this->paymentMetaRepository = $paymentMetaRepository;
    }

    public function begin($payment)
    {
        $applePayToken = $this->walletPayTokensRepository->findByPaymentAndType($payment, self::APPLE_PAY_TOKEN);
        if (!$applePayToken) {
            Debugger::log("Missing apple pay token for payment #{$payment->id}", Debugger::ERROR);
            return;
        }
        $this->payment = $payment;
        $this->applePayResult = $this->applePayWallet->process($payment, $applePayToken->value);
    }

    public function process($allowRedirect = true)
    {
        if (!$this->applePayResult || $this->applePayResult->isError()) {
            throw new CannotProcessPayment("ApplePayWallet - payment was not successful");
        }
        $responseData = [
            'redirect_url' => $this->generateReturnUrl($this->payment, ['VS' => $this->payment->variable_symbol])
        ];

        return new ProcessResponse('apple_pay', $responseData);
    }

    public function complete($payment): ?bool
    {
        // PROCESSING_ID is assigned only in case of successful payment
        $walletPayProcessingId = $this->paymentMetaRepository->findByPaymentAndKey($payment, Constants::WALLET_PAY_PROCESSING_ID);
        if ($walletPayProcessingId) {
            return true;
        }
        return false;
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
