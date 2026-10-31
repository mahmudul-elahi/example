<?php

declare(strict_types=1);

use App\Services\DeviceDetectorServiceInterface;

it('detects mac and chrome correctly', function (): void {
    $detector = app(DeviceDetectorServiceInterface::class);

    $result = $detector->detect('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    expect($result)->toBe('Chrome on OS X');
});

it('detects iphone correctly', function (): void {
    $detector = app(DeviceDetectorServiceInterface::class);

    $result = $detector->detect('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
    expect($result)->toBe('iPhone (Safari)');
});

it('detects windows and edge correctly', function (): void {
    $detector = app(DeviceDetectorServiceInterface::class);

    $result = $detector->detect('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0');
    expect($result)->toBe('Edge on Windows');
});

it('falls back to Web Browser when user agent is empty', function (): void {
    $detector = app(DeviceDetectorServiceInterface::class);

    $result = $detector->detect('');
    expect($result)->toBe('Web Browser');
});
