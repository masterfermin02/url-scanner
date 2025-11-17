<?php

namespace Fperdomo\App\Data;

final readonly class UrlScanProgress
{
    public function __construct(
        public int $totalRowsProcessed,
        public int $resultsOk,
        public int $resultsErr,
    ) {}

    public function validPercentage(): float
    {
        return $this->totalRowsProcessed === 0
            ? 0.0
            : ($this->resultsOk / $this->totalRowsProcessed) * 100;
    }

    public function invalidPercentage(): float
    {
        return $this->totalRowsProcessed === 0
            ? 0.0
            : ($this->resultsErr / $this->totalRowsProcessed) * 100;
    }
}
