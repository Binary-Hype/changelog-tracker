<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    /**
     * Parse a GitHub URL or shorthand into owner/repo components.
     *
     * @return array{owner: string, repo: string}
     */
    public function parseGithubUrl(string $url): array
    {
        $url = trim($url);

        // Handle shorthand "owner/repo" format
        if (preg_match('#^([a-zA-Z0-9\-_.]+)/([a-zA-Z0-9\-_.]+)$#', $url, $matches)) {
            return ['owner' => $matches[1], 'repo' => $matches[2]];
        }

        // Handle full GitHub URL
        if (preg_match('#github\.com/([a-zA-Z0-9\-_.]+)/([a-zA-Z0-9\-_.]+)#', $url, $matches)) {
            return ['owner' => $matches[1], 'repo' => rtrim($matches[2], '/')];
        }

        throw new \InvalidArgumentException("Unable to parse GitHub URL: {$url}");
    }

    /**
     * Fetch repository metadata from GitHub API.
     *
     * @return array{name: string, description: ?string, owner: string, repo: string}
     */
    public function fetchRepoMetadata(string $owner, string $repo): array
    {
        $response = $this->client()
            ->get("/repos/{$owner}/{$repo}");

        if ($response->failed()) {
            Log::error('GitHub API: Failed to fetch repo metadata', [
                'owner' => $owner,
                'repo' => $repo,
                'status' => $response->status(),
            ]);

            throw new \RuntimeException("Failed to fetch repository metadata for {$owner}/{$repo}");
        }

        $data = $response->json();

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'owner' => $data['owner']['login'],
            'repo' => $data['name'],
        ];
    }

    /**
     * Fetch the latest releases from GitHub API.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchReleases(string $owner, string $repo, bool $includePrereleases = false, int $perPage = 10): array
    {
        $response = $this->client()
            ->get("/repos/{$owner}/{$repo}/releases", [
                'per_page' => $perPage,
            ]);

        if ($response->status() === 403) {
            Log::warning('GitHub API: Rate limit hit', [
                'owner' => $owner,
                'repo' => $repo,
            ]);

            return [];
        }

        if ($response->failed()) {
            Log::error('GitHub API: Failed to fetch releases', [
                'owner' => $owner,
                'repo' => $repo,
                'status' => $response->status(),
            ]);

            return [];
        }

        $releases = $response->json();

        if (! $includePrereleases) {
            $releases = array_filter($releases, fn (array $release) => ! $release['prerelease']);
        }

        return array_values($releases);
    }

    private function client(): PendingRequest
    {
        $request = Http::baseUrl(config('changelog-tracker.github.api_base'))
            ->withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => config('changelog-tracker.github.user_agent'),
            ]);

        $token = config('changelog-tracker.github.token');
        if ($token) {
            $request->withToken($token);
        }

        return $request;
    }
}
