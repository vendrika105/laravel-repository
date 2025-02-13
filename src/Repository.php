<?php

namespace Vendrika105\LaravelRepository;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class Repository
{
    /**
     * The name of the database table associated with this repository.
     *
     * @var string
     */
    protected string $table_name;

    /**
     * The name of the database connection being used.
     *
     * @var string
     */
    protected string $connection_name;

    /**
     * The active database connection instance.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The query builder instance for constructing database queries.
     *
     * @var Builder
     */
    protected Builder $builder;


    /**
     * Create a new instance of the class with the given arguments.
     *
     * This method acts as a static constructor, allowing for
     * easy instantiation while passing arguments to the class constructor.
     *
     * @param mixed ...$args Arguments to be passed to the constructor.
     * @return static A new instance of the class.
     */
    static public function init(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Get the current database connection name.
     *
     * @return string The name of the current database connection.
     */
    public function getConnectionName(): string
    {
        return $this->connection_name;
    }

    /**
     * Set the database connection name and create a new connection.
     *
     * @param string $connection_name The name of the database connection.
     * @return static The repository instance.
     */
    public function setConnectionName(string $connection_name): static
    {
        $this->connection_name = $connection_name;
        return $this->createConnection();
    }

    /**
     * Create a new database connection instance.
     *
     * @return static The repository instance.
     */
    public function createConnection(): static
    {
        $this->connection = DB::connection($this->getConnectionName());
        return $this;
    }

    /**
     * Create a new query builder instance.
     *
     * @param string|null $table_name Optional table name to set.
     * @param string|null $connection_name Optional connection name to set.
     * @return static The repository instance with an active query builder.
     */
    public function createBuilder(?string $table_name = null, ?string $connection_name = null): static
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

    /**
     * Get the default table name based on the class name.
     *
     * @return string The default table name.
     */
    public function getDefaultTableName(): string
    {
        $namespace = explode('\\', get_called_class());
        $className = explode('Repository', array_pop($namespace))[0];

        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $className));
    }

    /**
     * Get the default database connection name.
     *
     * @return string The default database connection name.
     */
    public function getDefaultConnectionName(): string
    {
        return config('database.default');
    }

    /**
     * Get the active database connection instance.
     *
     * @return Connection The active database connection.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get the table name.
     *
     * @return string The table name.
     */
    public function getTableName(): string
    {
        return $this->table_name;
    }

    /**
     * Set the table name for queries.
     *
     * @param string $table_name The table name to set.
     * @return static The repository instance.
     */
    public function setTableName(string $table_name): static
    {
        $this->table_name = $table_name;
        return $this;
    }

    /**
     * Create a query with various clauses.
     *
     * @param array $selects Columns to select.
     * @param array $wheres Conditions for the WHERE clause.
     * @param array $orders Sorting options.
     * @param array $groups Group by columns.
     * @param int|null $limit Number of rows to limit.
     * @param int|null $offset Offset for pagination.
     * @return static The repository instance.
     */
    public function createFindQuery(array $selects = [], array $wheres = [], array $orders = [], array $groups = [], int $limit = null, int $offset = null): static
    {
        return $this->addSelectClause($selects)
            ->addWhereClause($wheres)
            ->addOrderClause($orders)
            ->addGroupClause($groups)
            ->addLimitClause($limit)
            ->addOffsetClause($offset);
    }

    /**
     * Add an offset clause to the query.
     *
     * @param int|null $offset The number of rows to skip.
     * @return static The repository instance.
     */
    protected function addOffsetClause(?int $offset): static
    {
        $limitProperty = $this->getBuilder()->unions ? 'unionLimit' : 'limit';

        if ($this->getBuilder()->$limitProperty !== null) {
            $this->getBuilder()->offset($offset ?? config('repository.default_offset', 0));
        }

        return $this;
    }

    /**
     * Get the active query builder instance.
     *
     * @return Builder The query builder instance.
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * Add a limit clause to the query.
     *
     * @param int|null $limit The maximum number of rows to retrieve.
     * @return static The repository instance.
     */
    protected function addLimitClause(?int $limit): static
    {
        $this->getBuilder()->limit($limit ?? config('repository.default_limit', 10));
        return $this;
    }

    /**
     * Add a group by clause to the query.
     *
     * @param array $groups Columns to group by.
     * @return static The repository instance.
     */
    protected function addGroupClause(array $groups): static
    {
        foreach ($groups as $column) {
            $this->getBuilder()->groupBy($this->addColumnPrefix($column));
        }
        return $this;
    }

    /**
     * Prefix a column name with the table name if needed.
     *
     * @param string $column The column name.
     * @return string The prefixed column name.
     */
    protected function addColumnPrefix(string $column): string
    {
        return !str_contains($column, '.') ? $this->getTableName() . '.' . $column : $column;
    }

    /**
     * Add an order by clause to the query.
     *
     * @param array $orders Columns and directions for ordering.
     * @return static The repository instance.
     */
    protected function addOrderClause(array $orders): static
    {
        foreach ($orders as $column => $direction) {
            $this->getBuilder()->orderBy($this->addColumnPrefix($column), $direction);
        }
        return $this;
    }

    /**
     * Add a where clause to the query.
     *
     * @param array $where Conditions for filtering results.
     * @return static The repository instance.
     */
    protected function addWhereClause(array $where): static
    {
        foreach ($where as $column => $parameter) {
            $column = $this->addColumnPrefix($column);
            $this->getBuilder()->where($column, $parameter);
        }
        return $this;
    }

    /**
     * Add a select clause to the query.
     *
     * @param array $selects Columns to select.
     * @return static The repository instance.
     */
    protected function addSelectClause(array $selects): static
    {
        foreach ($selects as $select) {
            if ($select instanceof Expression) {
                $this->getBuilder()->addSelect($select);
                continue;
            }
            $this->getBuilder()->addSelect($this->addColumnPrefix($select));
        }
        return $this;
    }
}
