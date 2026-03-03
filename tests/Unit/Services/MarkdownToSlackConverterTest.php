<?php

use App\Services\MarkdownToSlackConverter;

beforeEach(function () {
    $this->converter = new MarkdownToSlackConverter;
});

test('converts bold markdown to slack bold', function () {
    expect($this->converter->convert('**bold text**'))->toBe('*bold text*');
});

test('converts links to slack format', function () {
    expect($this->converter->convert('[Click here](https://example.com)'))
        ->toBe('<https://example.com|Click here>');
});

test('converts headings to slack bold', function () {
    expect($this->converter->convert('## My Heading'))->toBe('*My Heading*');
});

test('strips images', function () {
    $result = $this->converter->convert('![alt text](https://example.com/image.png)');

    expect($result)->not->toContain('image.png');
});

test('strips html tags', function () {
    expect($this->converter->convert('<div>content</div>'))->toBe('content');
});

test('converts code block language markers', function () {
    $input = "```php\necho 'hello';\n```";
    $result = $this->converter->convert($input);

    expect($result)->toContain("```\necho 'hello';\n```");
});

test('cleans up excessive blank lines', function () {
    $result = $this->converter->convert("line 1\n\n\n\n\nline 2");

    expect($result)->toBe("line 1\n\nline 2");
});
