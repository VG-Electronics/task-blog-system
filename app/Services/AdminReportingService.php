<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportingService
{
    public function resolveFormat(Request $request): string
    {
        $format = $request->input('format', 'json');

        return in_array($format, ['json', 'csv', 'xls']) ? $format : 'json';
    }

    public function resolveSort(Request $request): string
    {
        $sort = $request->input('sort_by', 'created_at');

        return in_array($sort, ['created_at', 'updated_at']) ? $sort : 'created_at';
    }

    public function resolveSortDir(Request $request): string
    {
        return $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
    }

    public function applyDateRange(Builder $query, Request $request): void
    {
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [
                $request->date('from')->startOfDay(),
                $request->date('to')->endOfDay(),
            ]);
        }
    }

    public function applyPostFilters(Builder $query, Request $request): void
    {
        $riskLevel = $request->input('risk_level');
        $minComments = $request->input('min_comments');
        $userRole = $request->input('user_role');

        if (!$riskLevel && !$minComments && !$userRole) {
            return;
        }

        if ($request->input('filtering_logic') === 'or') {
            $query->where(function (Builder $q) use ($riskLevel, $minComments, $userRole) {
                if ($riskLevel) {
                    $q->orWhere('risk_level', $riskLevel);
                }
                if ($minComments) {
                    $q->orHas('comments', '>=', (int) $minComments);
                }
                if ($userRole) {
                    $q->orWhereHas('user.roles', fn(Builder $r) => $r->where('name', $userRole));
                }
            });
        } else {
            if ($riskLevel) {
                $query->where('risk_level', $riskLevel);
            }
            if ($minComments) {
                $query->has('comments', '>=', (int) $minComments);
            }
            if ($userRole) {
                $query->whereHas('user.roles', fn(Builder $r) => $r->where('name', $userRole));
            }
        }
    }

    public function applyCommentFilters(Builder $query, Request $request): void
    {
        $hasFlag = $request->has('flag');
        $userRole = $request->input('user_role');

        if (!$hasFlag && !$userRole) {
            return;
        }

        if ($request->input('filtering_logic') === 'or') {
            $query->where(function (Builder $q) use ($hasFlag, $request, $userRole) {
                if ($hasFlag) {
                    $q->orWhere('flag', $request->boolean('flag'));
                }
                if ($userRole) {
                    $q->orWhereHas('user.roles', fn(Builder $r) => $r->where('name', $userRole));
                }
            });
        } else {
            if ($hasFlag) {
                $query->where('flag', $request->boolean('flag'));
            }
            if ($userRole) {
                $query->whereHas('user.roles', fn(Builder $r) => $r->where('name', $userRole));
            }
        }
    }

    public function respondAsCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function respondAsXls(array $headers, array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers, ...$rows]);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
