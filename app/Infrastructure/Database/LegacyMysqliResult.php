<?php

declare(strict_types=1);

namespace VentasPos\Infrastructure\Database;

final class LegacyMysqliResult
{
    private int $pointer = 0;

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(private readonly array $rows)
    {
    }

    public function fetch_assoc(): ?array
    {
        if (!isset($this->rows[$this->pointer])) {
            return null;
        }

        return $this->rows[$this->pointer++];
    }

    public function fetchArray(int $mode = MYSQLI_BOTH): array|false
    {
        $row = $this->fetch_assoc();

        if ($row === null) {
            return false;
        }

        if ($mode === MYSQLI_ASSOC) {
            return $row;
        }

        if ($mode === MYSQLI_NUM) {
            return array_values($row);
        }

        return array_merge(array_values($row), $row);
    }

    public function numRows(): int
    {
        return count($this->rows);
    }
}
