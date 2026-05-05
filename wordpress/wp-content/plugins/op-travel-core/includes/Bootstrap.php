<?php

namespace OPTravelCore;

use OPTravelCore\Payment\ZaloPayQrGateway;
use OPTravelCore\Rest\PaymentConfirmController;

final class Bootstrap
{
    public static function boot()
    {
        CmsSetup::boot();
        ProductMeta::boot();
        BookingHooks::boot();
        BookingServiceSync::boot();
        DemoPaymentQrHooks::boot();
        DemoSeeder::boot();
        ZaloPayQrGateway::boot();
        PaymentConfirmController::boot();
    }
}
