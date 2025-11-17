<?php

namespace Fperdomo\App\Url;

final readonly class Scanner
{
    public function __construct()
    {
    }

    public static function create(): self
    {
        return new static();
    }

    public function fromFile(string $file): FileScanner
    {
        return FileScanner::create($file);
    }

    public function fromArray(array $urls): ArrayScanner
    {
        return ArrayScanner::create($urls);
    }
}
