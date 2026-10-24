<?php

use App\Support\RegistrableDomain;

test('subdomains belong to the registered domain', function (string $host, string $expected) {
    expect(RegistrableDomain::of($host))->toBe($expected);
})->with([
    ['example.com', 'example.com'],
    ['www.example.com', 'example.com'],
    ['shop.accounts.example.com', 'example.com'],
    ['WWW.Example.COM.', 'example.com'],
    ['example.com:8443', 'example.com'],
]);

test('Brunei and other country domains keep their second level', function (string $host, string $expected) {
    expect(RegistrableDomain::of($host))->toBe($expected);
})->with([
    ['www.bibd.com.bn', 'bibd.com.bn'],
    ['online.bibd.com.bn', 'bibd.com.bn'],
    ['lms.pb.edu.bn', 'pb.edu.bn'],
    ['www.mofe.gov.bn', 'mofe.gov.bn'],
    ['a.b.example.co.uk', 'example.co.uk'],
    ['www.maybank2u.com.my', 'maybank2u.com.my'],
]);

test('every subdomain of a free hosting platform is a different owner', function () {
    expect(RegistrableDomain::of('a.github.io'))->toBe('a.github.io')
        ->and(RegistrableDomain::of('www.a.github.io'))->toBe('a.github.io')
        ->and(RegistrableDomain::of('shop.weebly.com'))->toBe('shop.weebly.com')
        ->and(RegistrableDomain::same('a.github.io', 'b.github.io'))->toBeFalse()
        ->and(RegistrableDomain::same('www.a.github.io', 'a.github.io'))->toBeTrue();
});

test('sibling subdomains are the same site, different domains are not', function () {
    expect(RegistrableDomain::same('shop.example.com', 'accounts.example.com'))->toBeTrue()
        ->and(RegistrableDomain::same('www.bibd.com.bn', 'login.bibd.com.bn'))->toBeTrue()
        ->and(RegistrableDomain::same('lms.pb.edu.bn', 'sso.pb.edu.bn'))->toBeTrue()
        ->and(RegistrableDomain::same('shop.example.com', 'evil.example'))->toBeFalse()
        ->and(RegistrableDomain::same('lms.pb.edu.bn', 'pb.edu.bn.evil.example'))->toBeFalse()
        ->and(RegistrableDomain::same('example.com', 'example.com.evil.example'))->toBeFalse();
});

test('IP addresses are kept as they are and a missing host matches nothing', function () {
    expect(RegistrableDomain::of('203.0.113.9'))->toBe('203.0.113.9')
        ->and(RegistrableDomain::of('[2001:db8::1]'))->toBe('2001:db8::1')
        ->and(RegistrableDomain::of(null))->toBeNull()
        ->and(RegistrableDomain::of(''))->toBeNull()
        ->and(RegistrableDomain::same(null, null))->toBeFalse();
});
