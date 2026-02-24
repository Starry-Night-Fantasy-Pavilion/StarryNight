<?php

declare(strict_types=1);

namespace Core\Orm;

use PDO;
use PDOStatement;
use app\services\Database;

/**
 * 查询构建器
 * 提供流畅的数据库查询接口
 */
class QueryBuilder
{
    /**
     * 数据库连接
     */
    protected PDO $pdo;

    /**
     * 表名（不含前缀）
     */
    protected string $table;

    /**
     * 表前缀
     */
    protected string $prefix;

    /**
     * 查询类型
     */
    protected string $type = 'select';

    /**
     * 选择的列
     */
    protected array $columns = ['*'];

    /**
     * WHERE条件
     */
    protected array $wheres = [];

    /**
     * WHERE参数
     */
    protected array $params = [];

    /**
     * JOIN语句
     */
    protected array $joins = [];

    /**
     * ORDER BY语句
     */
    protected array $orders = [];

    /**
     * GROUP BY语句
     */
    protected array $groups = [];

    /**
     * HAVING条件
     */
    protected array $havings = [];

    /**
     * LIMIT值
     */
    protected ?int $limitValue = null;

    /**
     * OFFSET值
     */
    protected ?int $offsetValue = null;

    /**
     * 是否使用 DISTINCT
     */
    protected bool $distinct = false;

    /**
     * 聚合函数缓存
     */
    protected array $aggregateCache = [];

    /**
     * 模型类名
     */
    protected ?string $modelClass = null;

    /**
     * 构造函数
     *
     * @param string $table 表名
     */
    public function __construct(string $table)
    {
        $this->pdo = Database::pdo();
        $this->prefix = Database::prefix();
        $this->table = $table;
    }

    /**
     * 设置模型类
     *
     * @param string $modelClass 模型类名
     * @return static
     */
    public function setModel(string $modelClass): static
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    /**
     * 获取完整表名（含前缀）
     *
     * @return string
     */
    protected function getFullTableName(): string
    {
        return $this->prefix . $this->table;
    }

    /**
     * 设置查询类型
     *
     * @param string $type 类型
     * @return static
     */
    protected function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 选择列
     *
     * @param array|string $columns 列名
     * @return static
     */
    public function select(array|string $columns = ['*']): static
    {
        $this->type = 'select';
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * 添加选择列
     *
     * @param array|string $columns 列名
     * @return static
     */
    public function addSelect(array|string $columns): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->columns = array_merge($this->columns, $columns);
        return $this;
    }

    /**
     * 使用DISTINCT
     *
     * @return static
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * WHERE条件
     *
     * @param string|callable $column 列名或嵌套条件闭包
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @param string $boolean 连接符 (AND/OR)
     * @return static
     */
    public function where(string|callable $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        // 支持嵌套 WHERE 条件（闭包）
        if (is_callable($column)) {
            return $this->whereNested($column, $boolean);
        }

        // 如果只提供两个参数，默认操作符为 =
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = $this->generatePlaceholder($column);
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'placeholder' => $placeholder,
            'boolean' => $boolean,
        ];
        $this->params[$placeholder] = $value;

        return $this;
    }

    /**
     * 嵌套WHERE条件
     *
     * @param callable $callback 回调函数
     * @param string $boolean 连接符
     * @return static
     */
    public function whereNested(callable $callback, string $boolean = 'AND'): static
    {
        $query = new static($this->table);
        $callback($query);

        $this->wheres[] = [
            'type' => 'nested',
            'query' => $query,
            'boolean' => $boolean,
        ];

        // 合并子查询参数
        $this->params = array_merge($this->params, $query->getParams());

        return $this;
    }

    /**
     * OR WHERE条件
     *
     * @param string $column 列名
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @return static
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * WHERE IN条件
     *
     * @param string $column 列名
     * @param array $values 值数组
     * @param string $boolean 连接符
     * @param bool $not 是否为NOT IN
     * @return static
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = $this->generatePlaceholder("{$column}_{$index}");
            $placeholders[] = $placeholder;
            $this->params[$placeholder] = $value;
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'placeholders' => $placeholders,
            'boolean' => $boolean,
            'not' => $not,
        ];

        return $this;
    }

    /**
     * WHERE NOT IN条件
     *
     * @param string $column 列名
     * @param array $values 值数组
     * @param string $boolean 连接符
     * @return static
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * WHERE NULL条件
     *
     * @param string $column 列名
     * @param string $boolean 连接符
     * @param bool $not 是否为NOT NULL
     * @return static
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not,
        ];

        return $this;
    }

    /**
     * WHERE NOT NULL条件
     *
     * @param string $column 列名
     * @param string $boolean 连接符
     * @return static
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * WHERE BETWEEN条件
     *
     * @param string $column 列名
     * @param mixed $min 最小值
     * @param mixed $max 最大值
     * @param string $boolean 连接符
     * @param bool $not 是否为NOT BETWEEN
     * @return static
     */
    public function whereBetween(string $column, mixed $min, mixed $max, string $boolean = 'AND', bool $not = false): static
    {
        $placeholderMin = $this->generatePlaceholder("{$column}_min");
        $placeholderMax = $this->generatePlaceholder("{$column}_max");

        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'placeholders' => [$placeholderMin, $placeholderMax],
            'boolean' => $boolean,
            'not' => $not,
        ];

        $this->params[$placeholderMin] = $min;
        $this->params[$placeholderMax] = $max;

        return $this;
    }

    /**
     * WHERE LIKE条件
     *
     * @param string $column 列名
     * @param string $pattern 模式
     * @param string $boolean 连接符
     * @param bool $not 是否为NOT LIKE
     * @return static
     */
    public function whereLike(string $column, string $pattern, string $boolean = 'AND', bool $not = false): static
    {
        $placeholder = $this->generatePlaceholder($column);
        $this->wheres[] = [
            'type' => 'like',
            'column' => $column,
            'placeholder' => $placeholder,
            'boolean' => $boolean,
            'not' => $not,
        ];
        $this->params[$placeholder] = $pattern;

        return $this;
    }

    /**
     * WHERE EXISTS条件
     *
     * @param callable $callback 子查询回调
     * @param string $boolean 连接符
     * @param bool $not 是否为NOT EXISTS
     * @return static
     */
    public function whereExists(callable $callback, string $boolean = 'AND', bool $not = false): static
    {
        $query = new static($this->table);
        $callback($query);

        $this->wheres[] = [
            'type' => 'exists',
            'query' => $query,
            'boolean' => $boolean,
            'not' => $not,
        ];

        return $this;
    }

    /**
     * 原生WHERE条件
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @param string $boolean 连接符
     * @return static
     */
    public function whereRaw(string $sql, array $params = [], string $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean,
        ];

        foreach ($params as $key => $value) {
            $this->params[$key] = $value;
        }

        return $this;
    }

    /**
     * JOIN语句
     *
     * @param string $table 表名
     * @param string $first 第一个列
     * @param string $operator 操作符
     * @param string $second 第二个列
     * @param string $type JOIN类型
     * @return static
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $this->prefix . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * LEFT JOIN语句
     *
     * @param string $table 表名
     * @param string $first 第一个列
     * @param string $operator 操作符
     * @param string $second 第二个列
     * @return static
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * RIGHT JOIN语句
     *
     * @param string $table 表名
     * @param string $first 第一个列
     * @param string $operator 操作符
     * @param string $second 第二个列
     * @return static
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * ORDER BY语句
     *
     * @param string $column 列名
     * @param string $direction 方向
     * @return static
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    /**
     * 最新排序
     *
     * @param string $column 列名
     * @return static
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * 最旧排序
     *
     * @param string $column 列名
     * @return static
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * 随机排序
     *
     * @return static
     */
    public function inRandomOrder(): static
    {
        $this->orders[] = ['raw' => 'RAND()'];
        return $this;
    }

    /**
     * GROUP BY语句
     *
     * @param array|string $columns 列名
     * @return static
     */
    public function groupBy(array|string $columns): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->groups = array_merge($this->groups, $columns);

        return $this;
    }

    /**
     * HAVING条件
     *
     * @param string $column 列名
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @param string $boolean 连接符
     * @return static
     */
    public function having(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = $this->generatePlaceholder("having_{$column}");
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'placeholder' => $placeholder,
            'boolean' => $boolean,
        ];
        $this->params[$placeholder] = $value;

        return $this;
    }

    /**
     * LIMIT语句
     *
     * @param int $limit 限制数量
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * OFFSET语句
     *
     * @param int $offset 偏移量
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * 分页快捷方法
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return static
     */
    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->limit($perPage)->offset(($page - 1) * $perPage);
    }

    /**
     * 构建SELECT SQL
     *
     * @return string
     */
    protected function buildSelectSql(): string
    {
        $table = $this->getFullTableName();
        $columns = $this->buildColumns();

        $sql = 'SELECT ';
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }
        $sql .= $columns . ' FROM ' . $this->quoteIdentifier($table);

        // JOIN
        foreach ($this->joins as $join) {
            $sql .= sprintf(
                ' %s JOIN %s ON %s %s %s',
                $join['type'],
                $this->quoteIdentifier($join['table']),
                $this->quoteIdentifier($join['first']),
                $join['operator'],
                $this->quoteIdentifier($join['second'])
            );
        }

        // WHERE
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        // GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', array_map([$this, 'quoteIdentifier'], $this->groups));
        }

        // HAVING
        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . $this->buildHavings();
        }

        // ORDER BY
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . $this->buildOrders();
        }

        // LIMIT/OFFSET
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }
        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    /**
     * 构建列名
     *
     * @return string
     */
    protected function buildColumns(): string
    {
        if ($this->columns === ['*']) {
            return '*';
        }

        return implode(', ', array_map(function ($column) {
            if ($column === '*') {
                return '*';
            }
            if (str_contains($column, ' AS ') || str_contains($column, ' as ')) {
                return $column; // 原样保留别名
            }
            return $this->quoteIdentifier($column);
        }, $this->columns));
    }

    /**
     * 构建WHERE条件
     *
     * @return string
     */
    protected function buildWheres(): string
    {
        $clauses = [];

        foreach ($this->wheres as $index => $where) {
            $clause = '';

            if ($index > 0) {
                $clause .= ' ' . $where['boolean'] . ' ';
            }

            switch ($where['type']) {
                case 'basic':
                    $clause .= $this->quoteIdentifier($where['column']) . ' ' . $where['operator'] . ' :' . $where['placeholder'];
                    break;

                case 'in':
                    $in = implode(', ', array_map(fn($p) => ':' . $p, $where['placeholders']));
                    $clause .= $this->quoteIdentifier($where['column']);
                    $clause .= $where['not'] ? ' NOT' : '';
                    $clause .= ' IN (' . $in . ')';
                    break;

                case 'null':
                    $clause .= $this->quoteIdentifier($where['column']);
                    $clause .= $where['not'] ? ' IS NOT NULL' : ' IS NULL';
                    break;

                case 'between':
                    $clause .= $this->quoteIdentifier($where['column']);
                    $clause .= $where['not'] ? ' NOT' : '';
                    $clause .= ' BETWEEN :' . $where['placeholders'][0] . ' AND :' . $where['placeholders'][1];
                    break;

                case 'like':
                    $clause .= $this->quoteIdentifier($where['column']);
                    $clause .= $where['not'] ? ' NOT' : '';
                    $clause .= ' LIKE :' . $where['placeholder'];
                    break;

                case 'exists':
                    $subSql = $where['query']->buildSelectSql();
                    $clause .= $where['not'] ? 'NOT ' : '';
                    $clause .= 'EXISTS (' . $subSql . ')';
                    // 合并子查询参数
                    $this->params = array_merge($this->params, $where['query']->getParams());
                    break;

                case 'raw':
                    $clause .= $where['sql'];
                    break;

                case 'nested':
                    $nestedSql = $where['query']->buildWheres();
                    $clause .= '(' . $nestedSql . ')';
                    break;
            }

            $clauses[] = $clause;
        }

        return implode('', $clauses);
    }

    /**
     * 构建HAVING条件
     *
     * @return string
     */
    protected function buildHavings(): string
    {
        $clauses = [];

        foreach ($this->havings as $index => $having) {
            $clause = '';

            if ($index > 0) {
                $clause .= ' ' . $having['boolean'] . ' ';
            }

            $clause .= $this->quoteIdentifier($having['column']) . ' ' . $having['operator'] . ' :' . $having['placeholder'];
            $clauses[] = $clause;
        }

        return implode('', $clauses);
    }

    /**
     * 构建ORDER BY语句
     *
     * @return string
     */
    protected function buildOrders(): string
    {
        $orders = [];

        foreach ($this->orders as $order) {
            if (isset($order['raw'])) {
                $orders[] = $order['raw'];
            } else {
                $orders[] = $this->quoteIdentifier($order['column']) . ' ' . $order['direction'];
            }
        }

        return implode(', ', $orders);
    }

    /**
     * 生成占位符
     *
     * @param string $column 列名
     * @return string
     */
    protected function generatePlaceholder(string $column): string
    {
        return ':' . str_replace('.', '_', $column) . '_' . count($this->params);
    }

    /**
     * 引用标识符
     *
     * @param string $identifier 标识符
     * @return string
     */
    protected function quoteIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '.')) {
            return implode('.', array_map(fn($part) => '`' . $part . '`', explode('.', $identifier)));
        }
        return '`' . $identifier . '`';
    }

    /**
     * 获取参数
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * 执行查询并获取所有结果
     *
     * @return array
     */
    public function get(): array
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($this->modelClass) {
            return array_map(fn($row) => $this->hydrateModel($row), $results);
        }

        return $results;
    }

    /**
     * 执行查询并获取第一条结果
     *
     * @return array|object|null
     */
    public function first(): array|object|null
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    /**
     * 根据主键查找
     *
     * @param int|string $id 主键值
     * @param string $primaryKey 主键列名
     * @return array|object|null
     */
    public function find(int|string $id, string $primaryKey = 'id'): array|object|null
    {
        return $this->where($primaryKey, $id)->first();
    }

    /**
     * 获取单个列的值
     *
     * @param string $column 列名
     * @return mixed
     */
    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();
        return $result ? ($result[$column] ?? null) : null;
    }

    /**
     * 获取列值数组
     *
     * @param string $column 列名
     * @return array
     */
    public function pluck(string $column): array
    {
        return array_column($this->select($column)->get(), $column);
    }

    /**
     * 获取键值对
     *
     * @param string $key 键列
     * @param string $value 值列
     * @return array
     */
    public function pluckKeyValue(string $key, string $value): array
    {
        $results = $this->select([$key, $value])->get();
        $pairs = [];
        foreach ($results as $row) {
            $pairs[$row[$key]] = $row[$value];
        }
        return $pairs;
    }

    /**
     * 统计数量
     *
     * @param string $column 列名
     * @return int
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * 求和
     *
     * @param string $column 列名
     * @return mixed
     */
    public function sum(string $column): mixed
    {
        return $this->aggregate('SUM', $column);
    }

    /**
     * 平均值
     *
     * @param string $column 列名
     * @return mixed
     */
    public function avg(string $column): mixed
    {
        return $this->aggregate('AVG', $column);
    }

    /**
     * 最大值
     *
     * @param string $column 列名
     * @return mixed
     */
    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * 最小值
     *
     * @param string $column 列名
     * @return mixed
     */
    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * 聚合函数
     *
     * @param string $function 函数名
     * @param string $column 列名
     * @return mixed
     */
    protected function aggregate(string $function, string $column): mixed
    {
        $this->columns = ["{$function}({$column}) AS aggregate"];
        $result = $this->first();
        return $result ? ($result['aggregate'] ?? null) : null;
    }

    /**
     * 检查是否存在
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * 检查是否不存在
     *
     * @return bool
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * 分块处理
     *
     * @param int $size 块大小
     * @param callable $callback 回调函数
     * @return bool
     */
    public function chunk(int $size, callable $callback): bool
    {
        $page = 1;

        do {
            $results = $this->forPage($page, $size)->get();

            if (empty($results)) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            $page++;
        } while (count($results) === $size);

        return true;
    }

    /**
     * 水合模型
     *
     * @param array $data 数据
     * @return object
     */
    protected function hydrateModel(array $data): object
    {
        $model = new $this->modelClass();
        foreach ($data as $key => $value) {
            $model->$key = $value;
        }
        $model->setExists(true);
        $model->setOriginal($data);
        return $model;
    }

    /**
     * 插入数据
     *
     * @param array $data 数据
     * @return int|string 插入ID
     */
    public function insert(array $data): int|string
    {
        $table = $this->getFullTableName();
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(', ', array_map([$this, 'quoteIdentifier'], $columns)),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * 批量插入数据
     *
     * @param array $data 数据数组
     * @return bool
     */
    public function insertBatch(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $table = $this->getFullTableName();
        $columns = array_keys($data[0]);
        $rows = [];

        foreach ($data as $index => $row) {
            $placeholders = [];
            foreach ($columns as $column) {
                $placeholder = ':' . $column . '_' . $index;
                $placeholders[] = $placeholder;
                $this->params[$placeholder] = $row[$column] ?? null;
            }
            $rows[] = '(' . implode(', ', $placeholders) . ')';
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->quoteIdentifier($table),
            implode(', ', array_map([$this, 'quoteIdentifier'], $columns)),
            implode(', ', $rows)
        );

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->params);
    }

    /**
     * 更新数据
     *
     * @param array $data 更新数据
     * @return int 影响行数
     */
    public function update(array $data): int
    {
        $table = $this->getFullTableName();
        $sets = [];
        $params = [];

        foreach ($data as $column => $value) {
            $placeholder = ':set_' . $column;
            $sets[] = $this->quoteIdentifier($column) . ' = ' . $placeholder;
            $params[$placeholder] = $value;
        }

        $sql = 'UPDATE ' . $this->quoteIdentifier($table) . ' SET ' . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        $params = array_merge($params, $this->params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 删除数据
     *
     * @return int 影响行数
     */
    public function delete(): int
    {
        $table = $this->getFullTableName();
        $sql = 'DELETE FROM ' . $this->quoteIdentifier($table);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);

        return $stmt->rowCount();
    }

    /**
     * 增加字段值
     *
     * @param string $column 列名
     * @param int|float $amount 增量
     * @return int 影响行数
     */
    public function increment(string $column, int|float $amount = 1): int
    {
        $table = $this->getFullTableName();
        $sql = 'UPDATE ' . $this->quoteIdentifier($table) . 
               ' SET ' . $this->quoteIdentifier($column) . ' = ' . 
               $this->quoteIdentifier($column) . ' + ' . $amount;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);

        return $stmt->rowCount();
    }

    /**
     * 减少字段值
     *
     * @param string $column 列名
     * @param int|float $amount 减量
     * @return int 影响行数
     */
    public function decrement(string $column, int|float $amount = 1): int
    {
        return $this->increment($column, -$amount);
    }

    /**
     * 获取SQL语句
     *
     * @return string
     */
    public function toSql(): string
    {
        return $this->buildSelectSql();
    }

    /**
     * 调试：打印SQL和参数
     *
     * @return array
     */
    public function debug(): array
    {
        return [
            'sql' => $this->toSql(),
            'params' => $this->params,
        ];
    }

    /**
     * 克隆查询构建器
     *
     * @return static
     */
    public function clone(): static
    {
        return clone $this;
    }

    /**
     * 重置查询构建器
     *
     * @return static
     */
    public function reset(): static
    {
        $this->type = 'select';
        $this->columns = ['*'];
        $this->wheres = [];
        $this->params = [];
        $this->joins = [];
        $this->orders = [];
        $this->groups = [];
        $this->havings = [];
        $this->limitValue = null;
        $this->offsetValue = null;
        $this->distinct = false;

        return $this;
    }
}
