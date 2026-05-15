<?php

namespace OPTravelStorefrontCMS;

final class Bootstrap
{
    public static function boot()
    {
        StorefrontDocumentPostType::boot();
        \OPTravelStorefrontCMS\Admin\AdminAssets::boot();
        \OPTravelStorefrontCMS\Admin\DocumentMetaBoxes::boot();
        \OPTravelStorefrontCMS\Admin\DocumentSave::boot();
    }
}
