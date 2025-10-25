<?php

use function Pest\Faker\fake;

uses(\Illuminate\Foundation\Testing\WithFaker::class);

it('is an example', function () {
    $name = fake()->firstName;

    expect($name)->not->toBeEmpty()->toBeString();
});
