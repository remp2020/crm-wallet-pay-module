<?php

namespace Crm\WalletPayModule\Model;

use Crm\ApplicationModule\Models\Config\ApplicationConfig;
use Crm\ApplicationModule\Models\Request;
use Crm\PaymentsModule\Models\Wallet\CardPayDirectService;
use Crm\PaymentsModule\Models\Wallet\TransactionPayload;
use Crm\PaymentsModule\Models\Wallet\WrongTransactionPayloadData;
use Crm\PaymentsModule\Repositories\PaymentMetaRepository;
use Crm\PaymentsModule\Repositories\PaymentsRepository;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Table\ActiveRow;
use Tracy\Debugger;
use Tracy\ILogger;

class TatraBankaGooglePayWallet implements GooglePayWalletInterface
{
    private ApplicationConfig $applicationConfig;
    private LinkGenerator $linkGenerator;
    private PaymentMetaRepository $paymentMetaRepository;
    private CardPayDirectService $cardPayDirectService;
    private PaymentsRepository $paymentsRepository;

    public function __construct(
        ApplicationConfig $applicationConfig,
        LinkGenerator $linkGenerator,
        PaymentMetaRepository $paymentMetaRepository,
        CardPayDirectService $cardPayDirectService,
        PaymentsRepository $paymentsRepository
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->linkGenerator = $linkGenerator;
        $this->paymentMetaRepository = $paymentMetaRepository;
        $this->cardPayDirectService = $cardPayDirectService;
        $this->paymentsRepository = $paymentsRepository;
    }

    public function checkPayment(ActiveRow $payment): bool
    {
        $processingId = $this->paymentMetaRepository->findByPaymentAndKey($payment, Constants::WALLET_PAY_PROCESSING_ID);
        if (!$processingId) {
            return false;
        }

        $mid = $this->applicationConfig->get('cardpay_mid');
        $result = $this->cardPayDirectService->checkTransaction($processingId->value, $mid);
        return $result->isSuccess();
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
            Debugger::log("TatraBankaGooglePayWallet - transaction error: " . $result->message(), ILogger::ERROR);
            // Update payment status here instead of redirecting to ReturnPresenter (user may want to stay on the sales funnel in case of ERROR)
            $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_FAIL);
            return new GooglePayResult(GooglePayResult::ERROR, ['error' => $result->message()]);
        }

        $meta = array_filter([
            'processing_id' => $resultData->getProcessingId(),
            'authorization_code' => $resultData->getAuthorizationCode(),
            'response_code' => $resultData->getResponseCode(),
            'status' => $resultData->getStatus()->rawStatus(),
        ], static fn($value) => $value !== null);

        if (!$result->isSuccess()) {
            // Update payment status here instead of redirecting to ReturnPresenter (user may want to stay on the sales funnel in case of ERROR)
            $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_FAIL);
            return new GooglePayResult(GooglePayResult::ERROR, $meta);
        }

        $this->paymentMetaRepository->add($payment, Constants::WALLET_PAY_PROCESSING_ID, $result->resultData()->getProcessingId());

        if ($result->resultData()->getTdsRedirectionFormHtml()) {
            return new GooglePayResult(GooglePayResult::TDS, $meta, $result->resultData()->getTdsRedirectionFormHtml());
        }

        return new GooglePayResult(GooglePayResult::OK, $meta);
    }
}
