<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPagolightPlugin\Behat\Page\Shop\Payum\Capture;

use Behat\Mink\Element\DocumentElement;
use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;

final class PayumCaptureDoPage extends SymfonyPage implements PayumCaptureDoPageInterface
{
    public function getRouteName(): string
    {
        return 'payum_capture_do';
    }

    public function waitForRedirect(): void
    {
        $this->getDocument()->waitFor(5, function (DocumentElement $document) {
            return !str_contains($document->getContent(), 'Stiamo processando il tuo pagamento');
        });
    }
}
