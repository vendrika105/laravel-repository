<?php

namespace Vendrika105\LaravelRepository;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Repository
{
    protected string $table_name;

    protected string $connection_name;

    protected Connection $connection;

    protected Builder $builder;

    public function getConnectionName(): string
    {
        return $this->connection_name;
    }

    public function setConnectionName(string $connection_name): static
    {
        $this->connection_name = $connection_name;

        return $this->createConnection();
    }

    public function createConnection(): static
    {
        $this->connection = DB::connection($this->getConnectionName());

        return $this;
    }

    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    public function createBuilder(?string $table_name = null, ?string $connection_name = null): self
    {
        if (!is_null($table_name)) {
            $this->setTableName($table_name);
        }

        if (!is_null($connection_name)) {
            $this->setConnectionName($connection_name);
        }

        if (!isset($this->table_name)) {
            $this->setTableName($this->getDefaultTableName());
        }

        if (!isset($this->connection_name)) {
            $this->setConnectionName($this->getDefaultConnectionName());
        }

        $this->builder = $this->getConnection()->table($this->getTableName());

        return $this;
    }

    public function getDefaultTableName(): string
    {
        $namespace = explode('\\', get_called_class());
        $className = explode('Repository', array_pop($namespace))[0];

        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $className));
    }

    public function getDefaultConnectionName(): string
    {
        return config('database.default');
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }

    public function setTableName(string $table_name): static
    {
        $this->table_name = $table_name;

        return $this;
    }
}