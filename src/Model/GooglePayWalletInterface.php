<?php

namespace Crm\WalletPayModule\Model;

use Nette\Database\Table\ActiveRow;

interface GooglePayWalletInterface
{
    public function process(ActiveRow $payment, string $googlePayToken): GooglePayResult;
}
