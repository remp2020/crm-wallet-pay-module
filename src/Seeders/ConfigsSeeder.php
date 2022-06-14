<?php

namespace Crm\WalletPayModule\Seeders;

use Crm\ApplicationModule\Builder\ConfigBuilder;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\ApplicationModule\Config\Repository\ConfigCategoriesRepository;
use Crm\ApplicationModule\Config\Repository\ConfigsRepository;
use Crm\ApplicationModule\Seeders\ConfigsTrait;
use Crm\ApplicationModule\Seeders\ISeeder;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigsSeeder implements ISeeder
{
    use ConfigsTrait;

    private $configCategoriesRepository;

    private $configsRepository;

    private $configBuilder;

    public function __construct(
        ConfigCategoriesRepository $configCategoriesRepository,
        ConfigsRepository $configsRepository,
        ConfigBuilder $configBuilder
    ) {
        $this->configCategoriesRepository = $configCategoriesRepository;
        $this->configsRepository = $configsRepository;
        $this->configBuilder = $configBuilder;
    }

    public function seed(OutputInterface $output)
    {
        $category = $this->configCategoriesRepository->loadByName('payments.config.category');
        $sorting = 650;
        // mozno nebude treba - da sa precitat z kluca
//        $this->addConfig($output, $category, 'apple_pay_merchant_id', 'Apple Pay Merchant ID', ApplicationConfig::TYPE_STRING, $sorting++);
        $this->addConfig($output, $category, 'apple_pay_cert_path', 'Apple Pay Certificate path', ApplicationConfig::TYPE_STRING, 'Full path to Merchant ID certificate in PEM format', '', $sorting++);
        $this->addConfig($output, $category, 'apple_pay_cert_key_path', 'Apple Pay Certificate key path', ApplicationConfig::TYPE_STRING, 'Full path to Merchant ID Certificate key in PEM format', '', $sorting++);
        $this->addConfig($output, $category, 'apple_pay_cert_key_pass', 'Apple Pay Merchant ID Certificate key password', ApplicationConfig::TYPE_STRING, 'Optional, password to decrypt Apple Pay Merchant ID certificate key (leave empty if not used)', '', $sorting++);
    }
}
