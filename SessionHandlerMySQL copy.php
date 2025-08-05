<?php
class SessionHandlerMySQL implements SessionHandlerInterface {
    private $pdo;
    private $table = 'sesiones';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($id) {
        $stmt = $this->pdo->prepare("SELECT data FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['data'];
        }
        return '';
    }

    public function write($id, $data) {
        $timestamp = time();
        $stmt = $this->pdo->prepare("REPLACE INTO {$this->table} (id, data, timestamp) VALUES (:id, :data, :timestamp)");
        return $stmt->execute([
            'id' => $id,
            'data' => $data,
            'timestamp' => $timestamp
        ]);
    }

    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function gc($maxlifetime) {
        $old = time() - $maxlifetime;
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE timestamp < :old");
        return $stmt->execute(['old' => $old]);
    }
}
?>