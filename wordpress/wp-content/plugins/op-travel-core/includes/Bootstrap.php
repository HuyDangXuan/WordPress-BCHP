<?php

namespace OPTravelCore;

use OPTravelCore\Rest\PaymentConfirmController;

final class Bootstrap
{
    public static function boot()
    {
        CmsSetup::boot();
        ProductMeta::boot();
        BookingHooks::boot();
        CheckoutSelectionFlow::boot();
        CustomerAuthFlow::boot();
        CustomerRegistrationOtp::boot();
        DemoSeeder::boot();
        PaymentConfirmController::boot();
    }
}
