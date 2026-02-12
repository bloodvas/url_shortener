<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Url
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function create(string $originalUrl, string $shortCode): ?int
    {
        $sql = "INSERT INTO urls (original_url, short_code, created_at) VALUES (:original_url, :short_code, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':original_url' => $originalUrl,
            ':short_code' => $shortCode
        ]);
        
        return $this->db->lastInsertId() ? (int)$this->db->lastInsertId() : null;
    }

    public function findByShortCode(string $shortCode): ?array
    {
        $sql = "SELECT * FROM urls WHERE short_code = :short_code LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':short_code' => $shortCode]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function incrementClicks(int $id): void
    {
        $sql = "UPDATE urls SET clicks = clicks + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function findByOriginalUrl(string $originalUrl): ?array
    {
        $sql = "SELECT * FROM urls WHERE original_url = :original_url LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':original_url' => $originalUrl]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
