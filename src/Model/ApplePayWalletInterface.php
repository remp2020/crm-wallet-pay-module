<?php

namespace Crm\WalletPayModule\Model;

use Nette\Database\Table\ActiveRow;

interface ApplePayWalletInterface
{
    public function process(ActiveRow $payment, string $applePayToken): ApplePayResult;
}
