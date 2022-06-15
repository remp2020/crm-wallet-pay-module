<?php

namespace Crm\WalletPayModule\Gateways;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\PaymentsModule\CannotProcessPayment;
use Crm\PaymentsModule\Gateways\GatewayAbstract;
use Crm\PaymentsModule\Gateways\ProcessResponse;
use Crm\WalletPayModule\Model\GooglePayResult;
use Crm\WalletPayModule\Model\GooglePayWalletInterface;
use Crm\WalletPayModule\Repositories\WalletPayTokensRepository;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;
use Nette\Localization\Translator;
use Tracy\Debugger;

class GooglePayWallet extends GatewayAbstract
{
    public const GOOGLE_PAY_TOKEN = 'google_pay_token';

    public const GATEWAY_CODE = 'googlepay_wallet';

    private GooglePayWalletInterface $googlePayWallet;
    private WalletPayTokensRepository $walletPayTokensRepository;
    private ?GooglePayResult $googlePayResult;
    private ?string $variableSymbol;

    public function __construct(
        LinkGenerator $linkGenerator,
        ApplicationConfig $applicationConfig,
        Response $httpResponse,
        Translator $translator,
        GooglePayWalletInterface $googlePayWallet,
        WalletPayTokensRepository $walletPayTokensRepository
    ) {
        parent::__construct($linkGenerator, $applicationConfig, $httpResponse, $translator);
        $this->googlePayWallet = $googlePayWallet;
        $this->walletPayTokensRepository = $walletPayTokensRepository;
    }

    public function begin($payment)
    {
        $googlePayToken = $this->walletPayTokensRepository->findByPaymentAndType($payment, self::GOOGLE_PAY_TOKEN);
        if (!$googlePayToken) {
            Debugger::log("Missing google pay token for payment #{$payment->id}", Debugger::ERROR);
            return;
        }
        $this->variableSymbol = $payment->variable_symbol;
        $this->googlePayResult = $this->googlePayWallet->process($payment, $googlePayToken->value);
    }

    /**
     * @throws CannotProcessPayment
     */
    public function process($allowRedirect = true)
    {
        if (!$this->googlePayResult || $this->googlePayResult->isError()) {
            throw new CannotProcessPayment("GooglePayWallet - payment was not successful");
        }

        $responseData = [];
        if ($this->googlePayResult->is3ds()) {
            $responseData['tds_html'] = $this->googlePayResult->tdsHtml();
        } else {
            $responseData['redirect_url'] = $this->linkGenerator->link('SalesFunnel:SalesFunnel:success', ['variableSymbol' => $this->variableSymbol]);
        }
        return new ProcessResponse('google_pay', $responseData);
    }

    public function complete($payment): ?bool
    {
        return null;
    }

    public function isSuccessful(): bool
    {
        // TODO: nespracovat 3ds nejak specialne?
        return isset($this->googlePayResult) && ($this->googlePayResult->isOk() || $this->googlePayResult->is3ds());
    }

    public function isCancelled()
    {
        // TODO: co tu?
        return parent::isCancelled();
    }

    public function isNotSettled()
    {
        // TODO: co tu?
        return parent::isNotSettled();
    }

    public function getResponseData()
    {
        return isset($this->googlePayResult) ? $this->googlePayResult->meta() : [];
    }
}
