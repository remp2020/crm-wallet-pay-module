<?php

namespace Crm\WalletPayModule\Models;

use Crm\ApplicationModule\Models\Config\ApplicationConfig;
use Crm\ApplicationModule\Models\Request;
use Crm\PaymentsModule\Models\Payment\PaymentStatusEnum;
use Crm\PaymentsModule\Models\Wallet\CardPayDirectService;
use Crm\PaymentsModule\Models\Wallet\TransactionPayload;
use Crm\PaymentsModule\Models\Wallet\WrongTransactionPayloadData;
use Crm\PaymentsModule\Repositories\PaymentMetaRepository;
use Crm\PaymentsModule\Repositories\PaymentsRepository;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Table\ActiveRow;
use Tracy\Debugger;
use Tracy\ILogger;

class TatraBankaApplePayWallet implements ApplePayWalletInterface
{
    private ApplicationConfig $applicationConfig;

    private PaymentsRepository $paymentsRepository;

    private PaymentMetaRepository $paymentMetaRepository;

    private CardPayDirectService $cardPayDirectService;

    public function __construct(
        ApplicationConfig $applicationConfig,
        PaymentsRepository $paymentsRepository,
        PaymentMetaRepository $paymentMetaRepository,
        CardPayDirectService $cardPayDirectService,
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentMetaRepository = $paymentMetaRepository;
        $this->cardPayDirectService = $cardPayDirectService;
    }

    /**
     * @throws WrongTransactionPayloadData
     * @throws InvalidLinkException
     */
    public function process(ActiveRow $payment, string $applePayToken): ApplePayResult
    {
        $currencyCode = (new ISOCurrencies())->numericCodeFor(new Currency($this->applicationConfig->get('currency')));

        $payload = new TransactionPayload();
        $payload->setMerchantId($this->applicationConfig->get('cardpay_mid'))
            ->setAmount($payment->amount * 100)
            ->setCurrency($currencyCode)
            ->setVariableSymbol($payment->variable_symbol)
            ->setClientIpAddress(Request::getIp())
            ->setClientName($payment->user->email)
            ->setApplePayToken($applePayToken);

        $result = $this->cardPayDirectService->postTransaction($payload);

        $resultData = $result->resultData();
        if (!$resultData) {
            Debugger::log("TatraBankaApplePayWallet - transaction error: " . $result->message(), ILogger::ERROR);
            // Update payment status here instead of redirecting to ReturnPresenter (user may want to stay on the sales funnel in case of ERROR)
            $this->paymentsRepository->updateStatus($payment, PaymentStatusEnum::Fail->value);
            return new ApplePayResult(ApplePayResult::ERROR, ['error' => $result->message()]);
        }

        $meta = array_filter([
            'processing_id' => $resultData->getProcessingId(),
            'authorization_code' => $resultData->getAuthorizationCode(),
            'response_code' => $resultData->getResponseCode(),
            'status' => $resultData->getStatus()->rawStatus(),
        ], static fn($value) => $value !== null);

        if (!$result->isSuccess()) {
            // Update payment status here instead of redirecting to ReturnPresenter (user may want to stay on the sales funnel in case of ERROR)
            $this->paymentsRepository->updateStatus($payment, PaymentStatusEnum::Fail->value);
            return new ApplePayResult(ApplePayResult::ERROR, $meta);
        }

        // assigning PROCESSING_ID means payment was successful
        $this->paymentMetaRepository->add($payment, Constants::WALLET_PAY_PROCESSING_ID, $result->resultData()->getProcessingId());
        return new ApplePayResult(ApplePayResult::OK, $meta);
    }
}
