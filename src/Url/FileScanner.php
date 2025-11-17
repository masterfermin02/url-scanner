<?php

namespace Fperdomo\App\Url;

use Fperdomo\App\Data\UrlScanProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Spatie\SimpleExcel\SimpleExcelReader;
use Closure;

final readonly class FileScanner
{
    public function __construct(
        public SimpleExcelReader $reader,
        public int $chunkSize = 100,
        public string $field = 'url',
        public ?Closure $callback = null,
    )
    {
    }
    public static function create(string $file): self
    {
        return new static(
            SimpleExcelReader::create($file)
        );
    }

    public function chunk(int $size): self
    {
        return new static(
            $this->reader,
            $size,
            $this->field,
            $this->callback
        );
    }

    public function field(string $field): self
    {
        return new static(
            $this->reader,
            $this->chunkSize,
            $field,
            $this->callback
        );
    }

    public function onProgress(Closure $callback): self
    {
        return new static(
            $this->reader,
            $this->chunkSize,
            $this->field,
            $callback
        );
    }

    public function scan(): UrlScanProgress
    {
        $urls = $this->reader->getRows();
        $resultsOk = 0;
        $resultsErr = 0;
        $totalRowsProcessed = 0;

        $promises = $urls->chunk($this->chunkSize)->map(
            function (LazyCollection|Collection $row) use (&$resultsOk, &$resultsErr, &$totalRowsProcessed) {
                return async(function () use ($row) {
                    $scanner = ArrayScanner::create($row->pluck($this->field)->toArray());

                    return $scanner->getInvalidUrls();
                })->then(function (array $invalidUrls) use (
                    &$resultsOk,
                    &$resultsErr,
                    $row,
                    &$totalRowsProcessed
                ) {
                    $invalidUrlsCount = count($invalidUrls);

                    $resultsErr        += $invalidUrlsCount;
                    $resultsOk         += $row->count() - $invalidUrlsCount;
                    $totalRowsProcessed += $row->count();

                    $progress = new UrlScanProgress(
                        totalRowsProcessed: $totalRowsProcessed,
                        resultsOk: $resultsOk,
                        resultsErr: $resultsErr,
                    );

                    if ($this->callback) {
                        ($this->callback)($progress);
                    }
                });
            }
        );

        // Let the caller decide how to time it; here we just await all.
        $promises->each(fn ($promise) => await($promise));

        return new UrlScanProgress(
            totalRowsProcessed: $totalRowsProcessed,
            resultsOk: $resultsOk,
            resultsErr: $resultsErr,
        );
    }

}
