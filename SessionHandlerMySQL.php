<?php
// SessionHandlerMySQL.php

class SessionHandlerMySQL implements SessionHandlerInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $savePath, string $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        // Select session data if it's not expired
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = :id AND expires > :expires");
        $stmt->execute([':id' => $id, ':expires' => time()]);
        $data = $stmt->fetchColumn();
        return $data !== false ? $data : ''; // Return empty string if no data, as per interface expectation
    }

    public function write(string $id, string $data): bool
    {
        // Calculate expiration time based on PHP's session.gc_maxlifetime
        $expires = time() + (int)ini_get('session.gc_maxlifetime');
        
        // Use UPSERT (INSERT ... ON DUPLICATE KEY UPDATE) for efficiency
        $sql = "INSERT INTO sessions (id, data, expires) VALUES (:id, :data, :expires)
                ON DUPLICATE KEY UPDATE data = :data_update, expires = :expires_update";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':data' => $data,
            ':expires' => $expires,
            ':data_update' => $data,
            ':expires_update' => $expires
        ]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function gc(int $maxlifetime): int|false
    {
        // Delete expired sessions
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE expires < :expires_threshold");
        $stmt->execute([':expires_threshold' => time() - $maxlifetime]);
        return $stmt->rowCount(); // Return number of deleted rows
    }
}
?>