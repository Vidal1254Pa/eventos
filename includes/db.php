<?php
declare(strict_types=1);

final class DbException extends RuntimeException
{
    public function __construct(string $message, private readonly string $sqlState = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getSqlState(): string
    {
        return $this->sqlState;
    }
}

final class DbResult
{
    private int $cursor = 0;

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(private readonly array $rows)
    {
    }

    public function fetch_assoc(): ?array
    {
        if (!isset($this->rows[$this->cursor])) {
            return null;
        }

        return $this->rows[$this->cursor++];
    }
}

final class DbStatement
{
    /**
     * @var array<int, mixed>
     */
    private array $boundParams = [];

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $rows = null;

    public int $num_rows = 0;

    public function __construct(private readonly PDOStatement $statement)
    {
    }

    public function bind_param(string $types, &...$vars): bool
    {
        $this->boundParams = [];

        foreach ($vars as $index => &$value) {
            $this->boundParams[$index + 1] = &$value;
        }

        return true;
    }

    public function execute(): bool
    {
        try {
            $params = [];
            foreach ($this->boundParams as $position => &$value) {
                $params[$position] = $value;
            }

            ksort($params);
            $this->statement->execute(array_values($params));
            $this->rows = null;
            $this->num_rows = 0;

            return true;
        } catch (PDOException $e) {
            throw db_exception_from_pdo($e);
        }
    }

    public function get_result(): DbResult
    {
        if ($this->rows === null) {
            $rows = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            $this->rows = is_array($rows) ? $rows : [];
            $this->num_rows = count($this->rows);
        }

        return new DbResult($this->rows);
    }

    public function store_result(): bool
    {
        if ($this->rows === null) {
            $rows = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            $this->rows = is_array($rows) ? $rows : [];
        }

        $this->num_rows = count($this->rows);
        return true;
    }
}

final class DbConnection
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function prepare(string $sql): DbStatement
    {
        try {
            $statement = $this->pdo->prepare(transform_placeholders($sql));
            if (!$statement instanceof PDOStatement) {
                throw new RuntimeException('No se pudo preparar la consulta.');
            }

            return new DbStatement($statement);
        } catch (PDOException $e) {
            throw db_exception_from_pdo($e);
        }
    }

    public function query(string $sql): DbResult
    {
        try {
            $statement = $this->pdo->query(transform_placeholders($sql));
            if (!$statement instanceof PDOStatement) {
                return new DbResult([]);
            }

            if ($statement->columnCount() === 0) {
                return new DbResult([]);
            }

            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            return new DbResult(is_array($rows) ? $rows : []);
        } catch (PDOException $e) {
            throw db_exception_from_pdo($e);
        }
    }

    public function begin_transaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        if (!$this->pdo->inTransaction()) {
            return true;
        }

        return $this->pdo->rollBack();
    }
}

try {
    $dsn = build_dsn();
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $conexion = new DbConnection($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo APP_DEBUG
        ? 'Error de conexion a BD: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'No se pudo establecer la conexion a la base de datos.';
    exit;
}

function build_dsn(): string
{
    if (DB_DRIVER !== 'pgsql') {
        throw new RuntimeException('Este proyecto esta configurado para PostgreSQL. Ajusta DB_DRIVER=pgsql en el archivo .env.');
    }

    return sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_SSLMODE
    );
}

function transform_placeholders(string $sql): string
{
    $result = '';
    $index = 1;
    $inSingle = false;
    $inDouble = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];

        if ($char === "'" && !$inDouble) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inSingle = !$inSingle;
            }
        } elseif ($char === '"' && !$inSingle) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inDouble = !$inDouble;
            }
        }

        if ($char === '?' && !$inSingle && !$inDouble) {
            $result .= '$' . $index++;
            continue;
        }

        $result .= $char;
    }

    return $result;
}

function db_exception_from_pdo(PDOException $e): DbException
{
    $sqlState = is_string($e->getCode()) ? $e->getCode() : '';
    $code = is_int($e->errorInfo[1] ?? null) ? (int) $e->errorInfo[1] : 0;

    return new DbException($e->getMessage(), $sqlState, $code, $e);
}

function db_is_unique_violation(Throwable $e): bool
{
    return $e instanceof DbException && $e->getSqlState() === '23505';
}
