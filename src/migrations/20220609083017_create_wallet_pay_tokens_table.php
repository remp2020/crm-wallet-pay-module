<?php

use Phinx\Migration\AbstractMigration;

class CreateWalletPayTokensTable extends AbstractMigration
{
    public function change()
    {
        $this->table('wallet_pay_tokens')
            ->addColumn('payment_id', 'integer')
            ->addColumn('type', 'string', ['null' => false])
            ->addColumn('value', 'text')
            ->addColumn('created_at', 'datetime')
            ->addForeignKey('payment_id', 'payments')
            ->create();
    }
}
