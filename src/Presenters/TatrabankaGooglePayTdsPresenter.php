<?php

namespace Crm\WalletPayModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\PaymentsModule\Models\Wallet\CardPayDirectService;
use Crm\PaymentsModule\Repository\PaymentMetaRepository;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\WalletPayModule\Gateways\GooglePayWallet;
use Crm\WalletPayModule\Model\Constants;

class TatrabankaGooglePayTdsPresenter extends FrontendPresenter
{
    private PaymentsRepository $paymentsRepository;

    private PaymentMetaRepository $paymentMetaRepository;

    private CardPayDirectService $cardPayDirectService;

    public function __construct(
        PaymentsRepository $paymentsRepository,
        PaymentMetaRepository $paymentMetaRepository,
        CardPayDirectService $cardPayDirectService
    ) {
        parent::__construct();
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentMetaRepository = $paymentMetaRepository;
        $this->cardPayDirectService = $cardPayDirectService;
    }

    public function renderRedirect($vs)
    {
        $payment = $this->paymentsRepository->findByVs($vs);
        if (!$payment) {
            $this->setErrorRedirect();
            return;
        }
        if ($payment->status !== PaymentsRepository::STATUS_FORM) {
            $this->setErrorRedirect();
            return;
        }
        if ($payment->payment_gateway->code !== GooglePayWallet::GATEWAY_CODE) {
            $this->setErrorRedirect();
            return;
        }

        $meta = $this->paymentMetaRepository->findByPaymentAndKey($payment, Constants::WALLET_PAY_PROCESSING_ID);
        if (!$meta) {
            $this->setErrorRedirect();
            return;
        }
        $processingId = $meta->value;

        $mid = $this->applicationConfig->get('cardpay_mid');
        $result = $this->cardPayDirectService->checkTransaction($processingId, $mid);

        if (!$result->isSuccess()) {
            $this->setErrorRedirect();
            return;
        }

        $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_PAID);

        $this->template->url = $this->link(':SalesFunnel:SalesFunnel:success', ['variableSymbol' => $payment->variable_symbol]);
    }

    private function setErrorRedirect()
    {
        $this->template->url = $this->link(':SalesFunnel:SalesFunnel:Error');
    }
}
