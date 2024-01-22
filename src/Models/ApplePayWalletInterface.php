<?php

namespace Crm\WalletPayModule\Models;

use Nette\Database\Table\ActiveRow;

interface ApplePayWalletInterface
{
    public function process(ActiveRow $payment, string $applePayToken): ApplePayResult;
}
