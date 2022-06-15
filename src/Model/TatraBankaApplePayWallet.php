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
        CardPayDirectService $cardPayDirectService
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
            $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_FAIL);
            return new ApplePayResult(ApplePayResult::ERROR, ['error' => $result->message()]);
        }

        $meta = array_filter([
            'processing_id' => $resultData->getProcessingId(),
            'authorization_code' => $resultData->getAuthorizationCode(),
            'response_code' => $resultData->getResponseCode(),
            'status' => $resultData->getStatus()->rawStatus(),
        ], static fn($value) => $value !== null);

        if (!$result->isSuccess()) {
            $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_FAIL);
            return new ApplePayResult(ApplePayResult::ERROR, $meta);
        }

        $this->paymentMetaRepository->add($payment, Constants::WALLET_PAY_PROCESSING_ID, $result->resultData()->getProcessingId());
        $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_PAID);

        return new ApplePayResult(ApplePayResult::OK, $meta);
    }
}
