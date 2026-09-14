<?php

namespace App\Imports;

use App\Enums\UserRole;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BangunanMultiSheetImport implements WithMultipleSheets
{
    private $mapping;
    private $headers;
    private $startRow;
    private $currentUserId;
    private $userOpdId;
    private $userRole;

    public function __construct(
        array $mapping,
        array $headers,
        int $startRow = 2,
        ?int $currentUserId = null,
        ?int $userOpdId = null,
        ?UserRole $userRole = null
    ) {
        $this->mapping = $mapping;
        $this->headers = $headers;
        $this->startRow = $startRow;
        $this->currentUserId = $currentUserId;
        $this->userOpdId = $userOpdId;
        $this->userRole = $userRole;
    }

    public function sheets(): array
    {
        BangunanImport::resetSharedState();

        // Terapkan import pada sheet pertama
        return [
            0 => new BangunanImport(
                $this->mapping,
                $this->headers,
                $this->startRow,
                $this->currentUserId,
                $this->userOpdId,
                $this->userRole
            ),
        ];
    }
}
