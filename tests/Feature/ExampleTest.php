<?php

declare(strict_types=1);

it('does not serve the root path in this API-only application', function (): void {
    $this->get('/')->assertNotFound();
});
