<?php

namespace App\Exports;

use App\Models\CryptoSignal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SignalsExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder<\App\Models\CryptoSignal>
     */
    private Builder $query;

    /**
     * @var array<int, string>
     */
    private array $columns;

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\CryptoSignal> $query
     * @param array<int, string> $columns
     */
    public function __construct(Builder $query, array $columns)
    {
        $this->query = $query;
        $this->columns = $columns;
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return $this->columns;
    }

    /**
     * @param \App\Models\CryptoSignal $signal
     */
    public function map($signal): array
    {
        $row = [];

        foreach ($this->columns as $column) {
            $value = $signal->{$column} ?? null;

            if ($value instanceof Carbon) {
                $value = $value->toDateTimeString();
            }

            $row[] = $value;
        }

        return $row;
    }
}

