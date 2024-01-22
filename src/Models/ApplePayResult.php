<?php

namespace Crm\WalletPayModule\Models;

class ApplePayResult
{
    const OK = 'OK';
    const ERROR = 'ERROR';

    private string $status;

    private array $meta;

    public function __construct(string $status, array $meta = [])
    {
        $this->status = $status;
        $this->meta = $meta;
    }

    public function isOk(): bool
    {
        return $this->status == self::OK;
    }

    public function isError(): bool
    {
        return $this->status == self::ERROR;
    }

    public function meta(): array
    {
        return $this->meta;
    }
}
