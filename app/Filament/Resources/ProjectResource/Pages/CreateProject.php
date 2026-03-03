<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Services\GitHubService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('github_url')
                    ->label('GitHub URL')
                    ->placeholder('https://github.com/owner/repo or owner/repo')
                    ->required()
                    ->helperText('Enter a full GitHub URL or shorthand (e.g. laravel/framework)'),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $github = app(GitHubService::class);

        $parsed = $github->parseGithubUrl($data['github_url']);
        $metadata = $github->fetchRepoMetadata($parsed['owner'], $parsed['repo']);

        return [
            'name' => $metadata['name'],
            'owner' => $metadata['owner'],
            'repo' => $metadata['repo'],
            'description' => $metadata['description'],
            'is_active' => true,
            'check_interval_minutes' => config('changelog-tracker.defaults.check_interval_minutes'),
            'include_prereleases' => config('changelog-tracker.defaults.include_prereleases'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
