<?php

namespace flight\services;

use app\models\GenericModel;
use flight\models\Tresorerie;

class TresorerieService
{
    private $db;
    private Tresorerie $tresorerieModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->tresorerieModel = new Tresorerie($db);
    }

    public function ajouterFond($montant, $dateAjout): array
    {
        try {
            // Récupérer le dernier solde
            $last = $this->tresorerieModel->getLast();
            $solde = $last ? $last['solde'] : 0;
            $nouveauSolde = $solde + $montant;

            // Insertion
            $success = $this->tresorerieModel->insert($dateAjout, $nouveauSolde);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Le fond a été ajouté avec succès.',
                    'solde' => $nouveauSolde
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'ajout du fond.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception : ' . $e->getMessage()
            ];
        }
    }
}