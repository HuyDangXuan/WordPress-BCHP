<?php

namespace OPTravelSePay;

use OPTravelSePay\Payment\SePayQrGateway;

final class Bootstrap
{
    public static function boot()
    {
        if (! class_exists('\OPTravelCore\Support\OrderMeta') || ! class_exists('\OPTravelCore\Support\Env')) {
            return;
        }

        BookingServiceSync::boot();
        SePayPaymentQrHooks::boot();
        SePayQrGateway::boot();
    }
}
