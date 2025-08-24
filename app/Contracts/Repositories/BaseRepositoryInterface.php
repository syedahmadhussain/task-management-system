<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(): Collection;
    
    public function find(int $id): ?Model;
    
    public function findOrFail(int $id): Model;
    
    public function create(array $data): Model;
    
    public function update(Model $model, array $data): bool;
    
    public function delete(Model $model): bool;
    
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    
    public function where(string $column, $value): Collection;
    
    public function whereIn(string $column, array $values): Collection;
}
