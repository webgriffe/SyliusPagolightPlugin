<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPagolightPlugin\Behat\Context\Api;

use Behat\Behat\Context\Context;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Tests\Webgriffe\SyliusPagolightPlugin\Behat\Context\PayumPaymentTrait;
use Webgriffe\SyliusPagolightPlugin\Client\PaymentState;
use Webgriffe\SyliusPagolightPlugin\Entity\WebhookTokenInterface;
use Webgriffe\SyliusPagolightPlugin\Repository\WebhookTokenRepositoryInterface;
use Webmozart\Assert\Assert;

final class PagolightContext implements Context
{
    use PayumPaymentTrait;

    /**
     * @param RepositoryInterface<PaymentSecurityTokenInterface> $paymentTokenRepository
     * @param PaymentRepositoryInterface<PaymentInterface> $paymentRepository
     */
    public function __construct(
        private readonly RepositoryInterface $paymentTokenRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ClientInterface $client,
        private readonly WebhookTokenRepositoryInterface $webhookTokenRepository,
    ) {
        // TODO: Why config parameters are not loaded?
        $this->urlGenerator->setContext(new RequestContext('', 'GET', '127.0.0.1:8080', 'https'));
    }

    /**
     * @When Pagolight notify the store about the successful payment
     */
    public function pagolightNotifyTheStoreAboutTheSuccessfulPayment(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentPaymentSecurityTokens($payment);
        $webhookToken = $this->webhookTokenRepository->findOneByPayment($payment);
        Assert::isInstanceOf($webhookToken, WebhookTokenInterface::class);

        $this->notifyPaymentState($paymentNotifySecurityToken, [
            'status' => PaymentState::SUCCESS,
            'token' => $webhookToken->getToken(),
        ]);
    }

    /**
     * @When /^Pagolight notify the store about the failed payment$/
     */
    public function pagolightNotifyTheStoreAboutTheFailedPayment(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentPaymentSecurityTokens($payment);
        $webhookToken = $this->webhookTokenRepository->findOneByPayment($payment);
        Assert::isInstanceOf($webhookToken, WebhookTokenInterface::class);

        $this->notifyPaymentState($paymentNotifySecurityToken, [
            'status' => PaymentState::CANCELLED,
            'token' => $webhookToken->getToken(),
        ]);
    }

    /**
     * @return PaymentRepositoryInterface<PaymentInterface>
     */
    protected function getPaymentRepository(): PaymentRepositoryInterface
    {
        return $this->paymentRepository;
    }

    /**
     * @return RepositoryInterface<PaymentSecurityTokenInterface>
     */
    protected function getPaymentTokenRepository(): RepositoryInterface
    {
        return $this->paymentTokenRepository;
    }

    private function notifyPaymentState(PaymentSecurityTokenInterface $token, array $responsePayload): void
    {
        $formParams = http_build_query($responsePayload);
        $request = new Request(
            'POST',
            $this->getNotifyUrl($token),
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            $formParams,
        );
        $this->client->sendRequest($request);
    }

    private function getNotifyUrl(PaymentSecurityTokenInterface $token): string
    {
        return $this->urlGenerator->generate(
            'payum_notify_do',
            ['payum_token' => $token->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
