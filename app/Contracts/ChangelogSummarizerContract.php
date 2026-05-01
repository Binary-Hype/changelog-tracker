<?php

namespace App\Contracts;

interface ChangelogSummarizerContract
{
    public function summarize(string $body): string;
}
