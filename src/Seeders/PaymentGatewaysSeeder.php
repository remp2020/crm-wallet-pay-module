<?php

namespace Crm\WalletPayModule\Seeders;

use Crm\ApplicationModule\Seeders\ISeeder;
use Crm\PaymentsModule\Repositories\PaymentGatewaysRepository;
use Symfony\Component\Console\Output\OutputInterface;

class PaymentGatewaysSeeder implements ISeeder
{
    private $paymentGatewaysRepository;

    public function __construct(PaymentGatewaysRepository $paymentGatewaysRepository)
    {
        $this->paymentGatewaysRepository = $paymentGatewaysRepository;
    }

    public function seed(OutputInterface $output)
    {
        if (!$this->paymentGatewaysRepository->exists('applepay_wallet')) {
            $this->paymentGatewaysRepository->add(
                'ApplePay Wallet',
                'applepay_wallet',
                600,
                true,
            );
            $output->writeln('  <comment>* payment type <info>applepay_wallet</info> created</comment>');
        } else {
            $output->writeln('  * payment type <info>applepay_wallet</info> exists');
        }

        if (!$this->paymentGatewaysRepository->exists('googlepay_wallet')) {
            $this->paymentGatewaysRepository->add(
                'GooglePay Wallet',
                'googlepay_wallet',
                700,
                true,
            );
            $output->writeln('  <comment>* payment type <info>googlepay_wallet</info> created</comment>');
        } else {
            $output->writeln('  * payment type <info>googlepay_wallet</info> exists');
        }
    }
}
