<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPagolightPlugin\Infrastructure\Payum\Action\CancelAction;
use Webgriffe\SyliusPagolightPlugin\Infrastructure\Payum\Action\CaptureAction;
use Webgriffe\SyliusPagolightPlugin\Infrastructure\Payum\Action\ConvertPaymentAction;
use Webgriffe\SyliusPagolightPlugin\Infrastructure\Payum\Action\FailAction;
use Webgriffe\SyliusPagolightPlugin\Infrastructure\Payum\Action\StatusAction;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pagolight.payum.action.capture', CaptureAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_pagolight.client'),
        ])
        ->tag('payum.action', ['factory' => 'pagolight', 'alias' => 'payum.action.capture'])
    ;

    $services->set('webgriffe_sylius_pagolight.payum.action.status', StatusAction::class)
        ->public()
    ;

    $services->set('webgriffe_sylius_pagolight.payum.action.convert_payment', ConvertPaymentAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_pagolight.converter.contract'),
        ])
        ->tag('payum.action', ['factory' => 'pagolight', 'alias' => 'payum.action.convert_payment'])
    ;

    $services->set('webgriffe_sylius_pagolight.payum.action.cancel', CancelAction::class)
        ->public()
        ->tag('payum.action', ['factory' => 'pagolight', 'alias' => 'payum.action.cancel'])
    ;

    $services->set('webgriffe_sylius_pagolight.payum.action.fail', FailAction::class)
        ->public()
        ->tag('payum.action', ['factory' => 'pagolight', 'alias' => 'payum.action.fail'])
    ;
};