<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPagolightPlugin\Payum\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Webgriffe\SyliusPagolightPlugin\Client\ClientInterface;
use Webgriffe\SyliusPagolightPlugin\Payum\PagolightApi;
use Webgriffe\SyliusPagolightPlugin\Payum\Request\Api\Auth;
use Webmozart\Assert\Assert;

/**
 * This action is responsible for authenticating the client against the Pagolight API.
 * It will use a layer of cache to speed up the authentication process.
 */
final class AuthAction implements ActionInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @param Auth|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, Auth::class);

        $pagolightApi = $request->getPagolightApi();
        $client = $this->client;

        $bearerToken = $this->cache->get($this->getCacheKey($pagolightApi), function (ItemInterface $item) use ($pagolightApi, $client) {
            $item->expiresAfter(82_800); // 23 hours (the token expires after 24 hours)

            $client->setSandbox($pagolightApi->isSandBox());

            return $this->client->auth($pagolightApi->getMerchantKey());
        });
        Assert::stringNotEmpty($bearerToken);

        $request->setBearerToken($bearerToken);
    }

    public function supports($request): bool
    {
        return $request instanceof Auth;
    }

    private function getCacheKey(PagolightApi $pagolightApi): string
    {
        return sprintf(
            'webgriffe_pagolight_bearer_token_mer_%s_sand_%s',
            hash('md5', $pagolightApi->getMerchantKey()),
            (string) $pagolightApi->isSandBox(),
        );
    }
}
