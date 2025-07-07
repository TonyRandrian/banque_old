<?php

namespace app\services;

use Exception;
use Flight;
use app\models\TypePret;

class typePretService
{
    private $bd;
    private $typePretModel;

    public function __construct($bdd)
    {
        $this->bd = $bdd;
        $this->typePretModel = new TypePret($bdd);
    }

    public function getAllTypePrets()
    {
        try {
            return $this->typePretModel->getAll();
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTypePretById($id)
    {
        try {
            $typePret = $this->typePretModel->getById($id);
            if (!$typePret) {
                Flight::json(['error' => 'Type de prêt non trouvé'], 404);
                return;
            }
            return $typePret;
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function createTypePret($data)
    {
        try {
            // Validation des données
            if (!isset($data['libelle']) || !isset($data['taux']) || !isset($data['duree'])) {
                Flight::json(['error' => 'Données manquantes'], 400);
                return;
            }

            // Valeur par défaut pour modalite_id si non fournie
            if (!isset($data['modalite_id'])) {
                $data['modalite_id'] = 1; // Valeur par défaut
            }

            $id = $this->typePretModel->create($data);
            return $this->typePretModel->getById($id);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateTypePret($id, $data)
    {
        try {
            if (!$this->typePretModel->exists($id)) {
                Flight::json(['error' => 'Type de prêt non trouvé'], 404);
                return;
            }

            // Validation des données
            if (!isset($data['libelle']) || !isset($data['taux']) || !isset($data['duree'])) {
                Flight::json(['error' => 'Données manquantes'], 400);
                return;
            }

            // Valeur par défaut pour modalite_id si non fournie
            if (!isset($data['modalite_id'])) {
                $data['modalite_id'] = 1; // Valeur par défaut
            }

            $this->typePretModel->update($id, $data);
            return $this->typePretModel->getById($id);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteTypePret($id)
    {
        try {
            if (!$this->typePretModel->exists($id)) {
                Flight::json(['error' => 'Type de prêt non trouvé'], 404);
                return;
            }

            $this->typePretModel->delete($id);
            return ['message' => 'Type de prêt supprimé avec succès'];
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 500);
        }
    }
}