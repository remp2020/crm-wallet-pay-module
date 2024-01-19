<?php

namespace Crm\WalletPayModule\Repositories;

use Crm\ApplicationModule\Models\Database\Repository;
use Nette\Database\Table\ActiveRow;

class WalletPayTokensRepository extends Repository
{
    protected $tableName = 'wallet_pay_tokens';

    public function add(ActiveRow $payment, string $type, string $value)
    {
        return $this->insert([
            'payment_id' => $payment->id,
            'type' => $type,
            'value' => $value,
            'created_at' => new \DateTime()
        ]);
    }

    final public function findByPaymentAndType(ActiveRow $payment, string $type)
    {
        return $this->getTable()->where([
            'payment_id' => $payment->id,
            'type' => $type,
        ])->fetch();
    }
}
