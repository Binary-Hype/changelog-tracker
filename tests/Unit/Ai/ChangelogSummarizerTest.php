<?php

use App\Ai\Agents\ChangelogSummarizer;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Enums\Lab;

test('uses anthropic provider via attribute', function () {
    $reflection = new ReflectionClass(ChangelogSummarizer::class);
    $attributes = $reflection->getAttributes(Provider::class);

    expect($attributes)->toHaveCount(1);
    expect($attributes[0]->newInstance()->value)->toBe(Lab::Anthropic);
});

test('pins haiku 4.5 model via attribute', function () {
    $reflection = new ReflectionClass(ChangelogSummarizer::class);
    $attributes = $reflection->getAttributes(Model::class);

    expect($attributes)->toHaveCount(1);
    expect($attributes[0]->newInstance()->value)->toBe('claude-haiku-4-5-20251001');
});

test('instructions lock the system prompt against drift', function () {
    $instructions = (new ChangelogSummarizer)->instructions();

    expect($instructions)
        ->toContain('Preserve every breaking change')
        ->toContain('GitHub-flavored markdown')
        ->toContain('No notable changes.')
        ->toContain('600 and 1000 characters');
});
