<?php

namespace Crm\WalletPayModule\Model;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\ApplicationModule\Request;
use Crm\PaymentsModule\Models\Wallet\CardPayDirectService;
use Crm\PaymentsModule\Models\Wallet\TransactionPayload;
use Crm\PaymentsModule\Models\Wallet\WrongTransactionPayloadData;
use Crm\PaymentsModule\Repository\PaymentMetaRepository;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Table\ActiveRow;
use Tracy\Debugger;

class TatraBankaGooglePayWallet implements GooglePayWalletInterface
{
    private ApplicationConfig $applicationConfig;

    private LinkGenerator $linkGenerator;

    private PaymentsRepository $paymentsRepository;

    private PaymentMetaRepository $paymentMetaRepository;

    private CardPayDirectService $cardPayDirectService;

    public function __construct(
        ApplicationConfig $applicationConfig,
        LinkGenerator $linkGenerator,
        PaymentsRepository $paymentsRepository,
        PaymentMetaRepository $paymentMetaRepository,
        CardPayDirectService $cardPayDirectService
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->linkGenerator = $linkGenerator;
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentMetaRepository = $paymentMetaRepository;
        $this->cardPayDirectService = $cardPayDirectService;
    }

    /**
     * @throws WrongTransactionPayloadData
     * @throws InvalidLinkException
     */
    public function process(ActiveRow $payment, string $googlePayToken): GooglePayResult
    {
        $currencyCode = (new ISOCurrencies())->numericCodeFor(new Currency($this->applicationConfig->get('currency')));

        $payload = new TransactionPayload();
        $payload->setMerchantId($this->applicationConfig->get('cardpay_mid'))
            ->setAmount($payment->amount * 100)
            ->setCurrency($currencyCode)
            ->setVariableSymbol($payment->variable_symbol)
            ->setClientIpAddress(Request::getIp())
            ->setClientName($payment->user->email)
            ->setGooglePayToken($googlePayToken)
            ->setTdsTermUrl($this->linkGenerator->link('WalletPay:TatrabankaGooglePayTds:redirect', ['vs' => $payment->variable_symbol]));

        $result = $this->cardPayDirectService->postTransaction($payload);

        $resultData = $result->resultData();
        if (!$resultData) {
            Debugger::log("TatraBankaGooglePayWallet - missing result data after transaction");
            return new GooglePayResult(GooglePayResult::ERROR);
        }

        $meta = array_filter([
            'processing_id' => $resultData->getProcessingId(),
            'authorization_code' => $resultData->getAuthorizationCode(),
            'response_code' => $resultData->getResponseCode(),
            'status' => $resultData->getStatus()->rawStatus(),
        ], static fn($value) => $value !== null);

        if (!$result->isSuccess()) {
            return new GooglePayResult(GooglePayResult::ERROR, $meta);
        }

        $this->paymentMetaRepository->add($payment, Constants::WALLET_PAY_PROCESSING_ID, $result->resultData()->getProcessingId());

        if ($result->resultData()->getTdsRedirectionFormHtml()) {
            return new GooglePayResult(GooglePayResult::TDS, $meta, $result->resultData()->getTdsRedirectionFormHtml());
        }

        $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_PAID);

        return new GooglePayResult(GooglePayResult::OK, $meta);
    }
}
