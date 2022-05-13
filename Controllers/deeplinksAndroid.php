<?php

/**
 * Deeplink endpoint for Android
 * ./well-known/assetlinks.json
 */

namespace Minds\Controllers;

class deeplinksAndroid extends deeplinks
{
    protected $applinks = [[
        "relation" => ["delegate_permission/common.handle_all_urls"],
        "target" => [
            "namespace" => "android_app",
            "package_name" => "com.minds.mobile",
            "sha256_cert_fingerprints" => ["89:50:DC:59:48:02:D6:6F:2E:F8:EA:C3:1F:F6:C9:ED:42:71:6B:68:5B:DC:DA:FA:0F:E7:74:89:4B:A0:71:B9"]
        ]
    ]];
}
