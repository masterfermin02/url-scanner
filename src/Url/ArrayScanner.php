<?php

namespace Fperdomo\App\Url;

use Fperdomo\App\ScannerInterface;

final readonly class ArrayScanner
{
    public function __construct(public array $urls)
    {
    }

    public static function create($urls): self
    {
        return new static($urls);
    }

    public function getInvalidUrls(): array
    {
        // Implementation to return invalid URLs from the array
        return (new \Fperdomo\App\Http\Scanner($this->urls))->getInvalidUrls();
    }
}
