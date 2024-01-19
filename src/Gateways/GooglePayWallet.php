<?php

namespace Crm\WalletPayModule\Gateways;

use Crm\ApplicationModule\Models\Config\ApplicationConfig;
use Crm\PaymentsModule\Models\CannotProcessPayment;
use Crm\PaymentsModule\Models\Gateways\GatewayAbstract;
use Crm\PaymentsModule\Models\Gateways\ProcessResponse;
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
    private $payment;

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
        $this->googlePayResult = $this->googlePayWallet->process($payment, $googlePayToken->value);
        $this->payment = $payment;
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
            $responseData['redirect_url'] = $this->generateReturnUrl($this->payment, ['VS' => $this->payment->variable_symbol]);
        }
        return new ProcessResponse('google_pay', $responseData);
    }

    public function complete($payment): ?bool
    {
        return $this->googlePayWallet->checkPayment($payment);
    }

    public function isSuccessful(): bool
    {
        return isset($this->googlePayResult) && ($this->googlePayResult->isOk() || $this->googlePayResult->is3ds());
    }

    public function getResponseData()
    {
        return isset($this->googlePayResult) ? $this->googlePayResult->meta() : [];
    }
}
