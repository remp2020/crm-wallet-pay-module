<?php

namespace Crm\WalletPayModule\Models;

use Nette\Database\Table\ActiveRow;

interface GooglePayWalletInterface
{
    public function process(ActiveRow $payment, string $googlePayToken): GooglePayResult;

    public function checkPayment(ActiveRow $payment): bool;
}
