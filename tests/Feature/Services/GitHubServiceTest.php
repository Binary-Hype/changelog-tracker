<?php

use App\Services\GitHubService;
use Illuminate\Support\Facades\Http;

test('parses full github url', function () {
    $service = new GitHubService;

    $result = $service->parseGithubUrl('https://github.com/laravel/framework');

    expect($result)->toBe(['owner' => 'laravel', 'repo' => 'framework']);
});

test('parses shorthand owner/repo format', function () {
    $service = new GitHubService;

    $result = $service->parseGithubUrl('laravel/framework');

    expect($result)->toBe(['owner' => 'laravel', 'repo' => 'framework']);
});

test('throws exception for invalid url', function () {
    $service = new GitHubService;

    $service->parseGithubUrl('not-a-valid-url');
})->throws(\InvalidArgumentException::class);

test('fetches repo metadata from github api', function () {
    Http::fake([
        'api.github.com/repos/laravel/framework' => Http::response([
            'name' => 'framework',
            'description' => 'The Laravel Framework.',
            'owner' => ['login' => 'laravel'],
        ]),
    ]);

    $service = new GitHubService;
    $result = $service->fetchRepoMetadata('laravel', 'framework');

    expect($result)
        ->toHaveKey('name', 'framework')
        ->toHaveKey('description', 'The Laravel Framework.')
        ->toHaveKey('owner', 'laravel')
        ->toHaveKey('repo', 'framework');
});

test('throws exception when repo metadata fetch fails', function () {
    Http::fake([
        'api.github.com/repos/fake/repo' => Http::response([], 404),
    ]);

    $service = new GitHubService;
    $service->fetchRepoMetadata('fake', 'repo');
})->throws(\RuntimeException::class);

test('fetches releases from github api', function () {
    Http::fake([
        'api.github.com/repos/laravel/framework/releases*' => Http::response([
            [
                'id' => 1,
                'tag_name' => 'v10.0.0',
                'name' => 'Laravel 10',
                'body' => 'Release notes',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v10.0.0',
                'published_at' => '2023-02-14T00:00:00Z',
                'prerelease' => false,
            ],
            [
                'id' => 2,
                'tag_name' => 'v10.1.0-beta.1',
                'name' => 'Laravel 10.1 Beta',
                'body' => 'Beta notes',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v10.1.0-beta.1',
                'published_at' => '2023-03-01T00:00:00Z',
                'prerelease' => true,
            ],
        ]),
    ]);

    $service = new GitHubService;
    $releases = $service->fetchReleases('laravel', 'framework');

    expect($releases)->toHaveCount(1)
        ->and($releases[0]['tag_name'])->toBe('v10.0.0');
});

test('includes prereleases when requested', function () {
    Http::fake([
        'api.github.com/repos/laravel/framework/releases*' => Http::response([
            ['id' => 1, 'tag_name' => 'v10.0.0', 'prerelease' => false],
            ['id' => 2, 'tag_name' => 'v10.1.0-beta.1', 'prerelease' => true],
        ]),
    ]);

    $service = new GitHubService;
    $releases = $service->fetchReleases('laravel', 'framework', includePrereleases: true);

    expect($releases)->toHaveCount(2);
});

test('returns empty array on rate limit', function () {
    Http::fake([
        'api.github.com/repos/laravel/framework/releases*' => Http::response([], 403),
    ]);

    $service = new GitHubService;
    $releases = $service->fetchReleases('laravel', 'framework');

    expect($releases)->toBeEmpty();
});
