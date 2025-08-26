<?php
// データベースアダプターインターフェース
interface DbAdapterInterface {
	public function prepare(string $sql): DbStatementInterface;
	public function query(string $sql): DbStatementInterface;
	public function exec(string $sql): int;
	public function beginTransaction(): bool;
	public function commit(): bool;
	public function rollBack(): bool;
	public function lastInsertId(?string $name = null): string|false;
	public function getError(): string;
}

// プリペアドステートメントインターフェース
interface DbStatementInterface {
	public function execute(?array $params = null): bool;
	public function fetch(?int $fetchMode = null): array|false;
	public function fetchAll(?int $fetchMode = null): array;
	public function bindValue(string $param, mixed $value, int $type = PDO::PARAM_STR): bool;
	public function rowCount(): int;
	public function closeCursor(): bool;
}

// 共通のPDOステートメントラッパー
class PDOStatementWrapper implements DbStatementInterface {
	private PDOStatement $stmt;

	public function __construct(PDOStatement $stmt) {
		$this->stmt = $stmt;
	}
	public function execute(?array $params = null): bool {
		return $this->stmt->execute($params);
	}
	public function fetch(?int $fetchMode = null): array|false {
		return $this->stmt->fetch($fetchMode ?? PDO::FETCH_ASSOC);
	}
	public function fetchAll(?int $fetchMode = null): array {
		return $this->stmt->fetchAll($fetchMode ?? PDO::FETCH_DEFAULT);
	}
	public function bindValue(string|int $param,mixed $value,int $type = PDO::PARAM_STR): bool {
		return $this->stmt->bindValue($param, $value, $type);
	}
	public function rowCount(): int {
		return $this->stmt->rowCount();
	}
	public function closeCursor(): bool {
		return $this->stmt->closeCursor();
	}
}

// MySQLアダプター
class MySqlAdapter implements DbAdapterInterface {
	private PDO $pdo;

	public function __construct(array $config) {
		$dsn = "mysql:host={$config["host"]};dbname={$config["name"]};charset=utf8mb4";
		try {
			$this->pdo = new PDO($dsn,$config["user"],$config["pass"]);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
			error_log("MySQL Connection Failed: ".$e->getMessage());
			throw $e;
		}
	}
	public function prepare(string $sql): DbStatementInterface {
		$stmt = $this->pdo->prepare($sql);
		return new PDOStatementWrapper($stmt);
	}
	public function query(string $sql): DbStatementInterface {
		$stmt = $this->pdo->query($sql);
		return new PDOStatementWrapper($stmt);
	}
	public function exec(string $sql): int {
		return $this->pdo->exec($sql);
	}
	public function beginTransaction(): bool {
		return $this->pdo->beginTransaction();
	}
	public function commit(): bool {
		return $this->pdo->commit();
	}
	public function rollBack(): bool {
		return $this->pdo->rollBack();
	}
	public function lastInsertId(?string $name = null): string|false {
		return $this->pdo->lastInsertId($name);
	}
	public function getError(): string {
		$errorInfo = $this->pdo->errorInfo();
		return $errorInfo[2] ?? "";
	}
}

// SQLiteアダプター
class SqliteAdapter implements DbAdapterInterface {
	private PDO $pdo;

	public function __construct(array $config) {
		$dsn = "sqlite:{$config["path"]}";
		try {
			$this->pdo = new PDO($dsn);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
			error_log("SQLite Connection Failed: ".$e->getMessage());
			throw $e;
		}
	}
	public function prepare(string $sql): DbStatementInterface {
		$stmt = $this->pdo->prepare($sql);
		return new PDOStatementWrapper($stmt);
	}
	public function query(string $sql): DbStatementInterface {
		$stmt = $this->pdo->query($sql);
		return new PDOStatementWrapper($stmt);
	}
	public function exec(string $sql): int {
		return $this->pdo->exec($sql);
	}
	public function beginTransaction(): bool {
		return $this->pdo->beginTransaction();
	}
	public function commit(): bool {
		return $this->pdo->commit();
	}
	public function rollBack(): bool {
		return $this->pdo->rollBack();
	}
	public function lastInsertId(?string $name = null): string|false {
		return $this->pdo->lastInsertId($name);
	}
	public function getError(): string {
		$errorInfo = $this->pdo->errorInfo();
		return $errorInfo[2] ?? "";
	}
}

// データベースファクトリー
class DbFactory {
	public static function createAdapter(array $config): DbAdapterInterface {
		if(!isset($config["type"])) {
			throw new InvalidArgumentException("Database type not specified in config.");
		}
		switch($config["type"]) {
			case "mysql":
				return new MySqlAdapter([
					"host" => $config["host"] ?? "",
					"name" => $config["name"] ?? "",
					"user" => $config["user"] ?? "",
					"pass" => $config["pass"] ?? "",
				]);
			case "sqlite":
				return new SqliteAdapter([
					"path" => $config["path"] ?? "",
				]);
			default:
				throw new Exception("Unsupported database type: ".$config["type"]);
		}
	}
}
