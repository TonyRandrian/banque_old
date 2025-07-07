<?php

namespace flight\models;

use PDO;

class Tresorerie
{

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getLast(): ?array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM tresorerie ORDER BY date_mouvement DESC LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Log or handle error as needed
            return null;
        }
    }

    public function insert(string $date_mouvement, float $solde): bool
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO tresorerie (date_mouvement, solde) VALUES (?, ?)");
            return $stmt->execute([$date_mouvement, $solde]);
        } catch (\Exception $e) {
            // Log or handle error as needed
            return false;
        }
    }
}