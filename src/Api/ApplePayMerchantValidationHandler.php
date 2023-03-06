<?php

namespace Crm\WalletPayModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApplicationModule\Config\ApplicationConfig;
use GuzzleHttp\Client;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Utils\Json;
use Tomaj\NetteApi\Params\GetInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class ApplePayMerchantValidationHandler extends ApiHandler
{
    // according to https://developer.apple.com/documentation/apple_pay_on_the_web/setting_up_your_server
    private const ALLOWED_APPLE_PAY_DOMAINS = [
        'apple-pay-gateway.apple.com',
        'apple-pay-gateway-nc-pod1.apple.com',
        'apple-pay-gateway-nc-pod2.apple.com',
        'apple-pay-gateway-nc-pod3.apple.com',
        'apple-pay-gateway-nc-pod4.apple.com',
        'apple-pay-gateway-nc-pod5.apple.com',
        'apple-pay-gateway-pr-pod1.apple.com',
        'apple-pay-gateway-pr-pod2.apple.com',
        'apple-pay-gateway-pr-pod3.apple.com',
        'apple-pay-gateway-pr-pod4.apple.com',
        'apple-pay-gateway-pr-pod5.apple.com',
    ];

    private Request $request;
    private ApplicationConfig $applicationConfig;

    public function __construct(
        ApplicationConfig $applicationConfig,
        Request $request
    ) {
        parent::__construct();
        $this->applicationConfig = $applicationConfig;
        $this->request = $request;
    }

    public function params(): array
    {
        return [
            (new GetInputParam('url'))->setRequired(),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $url = $params['url'];
        $parsedUrl = parse_url($url);
        if ($parsedUrl['scheme'] !== 'https' || !in_array($parsedUrl['host'], self::ALLOWED_APPLE_PAY_DOMAINS, true)) {
            return new JsonApiResponse(Response::S400_BAD_REQUEST, ["error" => "Incorrect 'url' parameter"]);
        }

        $currentHost = $this->request->getUrl()->getHost();

        $certPath = $this->applicationConfig->get('apple_pay_cert_path');
        $certKeyPath = $this->applicationConfig->get('apple_pay_cert_key_path');
        $certKeyPass = $this->applicationConfig->get('apple_pay_cert_key_pass');
        $merchantId = openssl_x509_parse(file_get_contents($certPath))['subject']['UID'];

        $data = [
            "domainName" => $currentHost,
            "merchantIdentifier" => $merchantId,
            "displayName" => $this->applicationConfig->get('site_title'),
        ];

        try {
            $client = new Client();
            $response = $client->post($url, [
                'cert' => $certPath,
                'ssl_key' => $certKeyPass ? [$certKeyPath, $certKeyPass] : $certKeyPath,
                'json' => $data,
            ]);
            $result = $response->getBody()->getContents();
            return new JsonApiResponse(Response::S200_OK, Json::decode($result, true));
        } catch (\Exception $exception) {
            Debugger::log($exception->getMessage(), ILogger::ERROR);
            return new JsonApiResponse(Response::S400_BAD_REQUEST, ["error" => "Error while validating merchant."]);
        }
    }
}
