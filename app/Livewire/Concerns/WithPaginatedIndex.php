<?php

namespace App\Livewire\Concerns;

trait WithPaginatedIndex
{
    public int $perPage = 10;

    /**
     * @return array<int, int>
     */
    public function perPageOptions(): array
    {
        return [10, 20, 50, 100, 200, 500];
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, $this->perPageOptions(), true)) {
            $this->perPage = 10;
        }

        $this->resetPage($this->indexPaginationPageName());
    }

    protected function indexPaginationPageName(): string
    {
        return 'articles-page';
    }
}
