<?php

namespace Crm\WalletPayModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\PaymentsModule\Repositories\PaymentsRepository;
use Nette\Application\LinkGenerator;

class TatrabankaGooglePayTdsPresenter extends FrontendPresenter
{
    private PaymentsRepository $paymentsRepository;
    private LinkGenerator $linkGenerator;

    public function __construct(PaymentsRepository $paymentsRepository, LinkGenerator $linkGenerator)
    {
        parent::__construct();
        $this->paymentsRepository = $paymentsRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function renderRedirect($vs)
    {
        $payment = $this->paymentsRepository->findByVs($vs);
        if (!$payment) {
            $this->setErrorRedirect();
            return;
        }

        $url = $this->linkGenerator->link('Payments:Return:gateway', [
            'gatewayCode' => $payment->payment_gateway->code,
            'VS' => $payment->variable_symbol,
        ]);

        $this->template->url = $url;
    }

    private function setErrorRedirect()
    {
        $this->template->url = $this->link(':SalesFunnel:SalesFunnel:Error');
    }
}
