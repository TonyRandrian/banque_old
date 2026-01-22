<?php

namespace app\models;

use Exception;
use PDO;

class TypePret
{
    private $bd;
    private $tableName = "type_pret";

    public function __construct($bdd)
    {
        $this->bd = $bdd;
    }

    public function getAll()
    {
        try {
            $sql = "SELECT * FROM {$this->tableName}";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des types de prêt : " . $e->getMessage());
        }
    }

    public function getById($id)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE type_pret_id = :id";
            $stmt = $this->bd->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du type de prêt : " . $e->getMessage());
        }
    }

    public function create($data)
    {
        try {
            $sql = "INSERT INTO {$this->tableName} (libelle, taux, duree, modalite_id) VALUES (:libelle, :taux, :duree, :modalite_id)";
            $stmt = $this->bd->prepare($sql);
            $stmt->bindParam(':libelle', $data['libelle'], PDO::PARAM_STR);
            $stmt->bindParam(':taux', $data['taux'], PDO::PARAM_STR);
            $stmt->bindParam(':duree', $data['duree'], PDO::PARAM_INT);
            $stmt->bindParam(':modalite_id', $data['modalite_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            return $this->bd->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la création du type de prêt : " . $e->getMessage());
        }
    }

    public function update($id, $data)
    {
        try {
            $sql = "UPDATE {$this->tableName} SET libelle = :libelle, taux = :taux, duree = :duree, modalite_id = :modalite_id WHERE type_pret_id = :id";
            $stmt = $this->bd->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':libelle', $data['libelle'], PDO::PARAM_STR);
            $stmt->bindParam(':taux', $data['taux'], PDO::PARAM_STR);
            $stmt->bindParam(':duree', $data['duree'], PDO::PARAM_INT);
            $stmt->bindParam(':modalite_id', $data['modalite_id'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour du type de prêt : " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE type_pret_id = :id";
            $stmt = $this->bd->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la suppression du type de prêt : " . $e->getMessage());
        }
    }

    public function exists($id)
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE type_pret_id = :id";
            $stmt = $this->bd->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la vérification de l'existence du type de prêt : " . $e->getMessage());
        }
    }
}
