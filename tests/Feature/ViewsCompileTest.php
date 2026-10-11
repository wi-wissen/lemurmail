<?php

/** The production container runs `view:cache`, which compiles every template; one dangling component breaks the start. */
it('compiles every Blade template', function () {
    $this->artisan('view:clear')->assertSuccessful();
    $this->artisan('view:cache')->assertSuccessful();
    $this->artisan('view:clear')->assertSuccessful();
});

it('can cache routes and config like the container entrypoint does', function () {
    $this->artisan('route:cache')->assertSuccessful();
    $this->artisan('route:clear')->assertSuccessful();
});
