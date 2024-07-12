<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Models;

use PhpSpec\ObjectBehavior;

class TenantSpec extends ObjectBehavior
{
    public function it_should_unserialize_tenant_and_initialize_missing_sub_properties()
    {
        // Pre-serialized string with a missing digestEmailEnabled property.
        $serializedTenant = 'O:36:"Minds\Core\MultiTenant\Models\Tenant":7:{s:2:"id";i:1;s:6:"domain";s:8:"test.com";s:9:"ownerGuid";i:1;s:12:"rootUserGuid";i:1;s:6:"config";O:55:"Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig":13:{s:8:"siteName";s:8:"siteName";s:9:"siteEmail";s:9:"siteEmail";s:11:"colorScheme";E:64:"Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme:DARK";s:12:"primaryColor";s:7:"#ff0000";s:18:"federationDisabled";b:1;s:10:"replyEmail";s:20:"replyEmail@minds.com";s:11:"nsfwEnabled";b:1;s:12:"boostEnabled";b:1;s:21:"customHomePageEnabled";b:1;s:25:"customHomePageDescription";s:25:"customHomePageDescription";s:19:"walledGardenEnabled";b:1;s:16:"updatedTimestamp";i:1720795282;s:18:"lastCacheTimestamp";N;}s:4:"plan";E:48:"Minds\Core\MultiTenant\Enums\TenantPlanEnum:TEAM";s:19:"trialStartTimestamp";i:1720795282;}';
      
        // Try to unserialize the object.
        $unserializedTenant = unserialize($serializedTenant);

        // Will throw an exception if the property is not initialized.
        $unserializedTenant->config->digestEmailEnabled;
    }
}
