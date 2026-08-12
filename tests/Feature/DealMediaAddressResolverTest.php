<?php

namespace Tests\Feature;

use App\Services\DealMediaAddressResolver;
use Tests\TestCase;

class DealMediaAddressResolverTest extends TestCase
{
    public function test_it_rejects_reserved_and_private_ipv4_and_ipv6_addresses(): void
    {
        $resolver = app(DealMediaAddressResolver::class);

        $this->assertFalse($resolver->isPublicAddress('127.0.0.1'));
        $this->assertFalse($resolver->isPublicAddress('10.0.0.1'));
        $this->assertFalse($resolver->isPublicAddress('::1'));
        $this->assertFalse($resolver->isPublicAddress('fc00::1'));
        $this->assertTrue($resolver->isPublicAddress('1.1.1.1'));
    }
}
