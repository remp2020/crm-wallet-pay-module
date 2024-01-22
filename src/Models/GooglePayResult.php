<?php

namespace Crm\WalletPayModule\Models;

class GooglePayResult
{
    const OK = 'OK';
    const ERROR = 'ERROR';
    const TDS = 'TDS';

    private string $status;

    private array $meta;

    private ?string $tdsHtml;

    public function __construct(string $status, array $meta = [], ?string $tdsHtml = null)
    {
        $this->status = $status;
        $this->meta = $meta;
        $this->tdsHtml = $tdsHtml;
    }

    public function isOk(): bool
    {
        return $this->status == self::OK;
    }

    public function isError(): bool
    {
        return $this->status == self::ERROR;
    }

    public function is3ds(): bool
    {
        return $this->status == self::TDS;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function tdsHtml(): ?string
    {
        return $this->tdsHtml;
    }
}
