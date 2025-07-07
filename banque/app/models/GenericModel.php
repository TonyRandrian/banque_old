<?php

namespace app\models;

use Exception;
use Flight;

class GenericModel
{
    private $bd;


    public function __construct($bdd)
    {
        $this->bd = $bdd;
    }

    public function getSumOfColumn($table, $column)
    {
        try {
            if (empty($table) || empty($column)) {
                return ["message" => "Le nom de la table et de la colonne sont obligatoires"];
            }
            $query = "SELECT SUM(`$column`) FROM `$table`";
            $stmt = $this->bd->prepare($query);
            $stmt->execute();
            $sum = $stmt->fetchColumn();
            return (float)($sum ?? 0.0);
        } catch (Exception $e) {
            return ["message" => "Erreur lors du calcul de la somme : " . $e->getMessage()];
        }

    }

    public function getAverageOfColumn($table, $column)
    {
        try {
            if (empty($table) || empty($column)) {
                return ["message" => "Le nom de la table et de la colonne sont obligatoires"];
            }
            $query = "SELECT AVG(`$column`) FROM `$table`";
            $stmt = $this->bd->prepare($query);
            $stmt->execute();
            $average = $stmt->fetchColumn();
            return (float)($average ?? 0.0);
        } catch (Exception $e) {
            return ["message" => "Erreur lors du calcul de la moyenne : " . $e->getMessage()];
        }
    }

    public function getExtremumRow($table, $column, $extremum = 'max')
    {
        try {
            if (empty($table) || empty($column)) {
                return ["message" => "Table et colonne obligatoires"];
            }
            $extremum = strtolower($extremum);
            if (!in_array($extremum, ['min', 'max'])) {
                return ["message" => "Choix invalide - utiliser 'min' ou 'max'"];
            }
            $query = "SELECT * FROM `$table` 
                    WHERE `$column` = (SELECT $extremum(`$column`) FROM `$table`) 
                    LIMIT 1";

            $stmt = $this->bd->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (Exception $e) {
            return ["message" => "Erreur lors de la récupération de l'extremum : " . $e->getMessage()];
        }
    }

    public function getFormData($table, $omitColumns = [], $method = 'POST'): array
    {
        $query = "DESCRIBE $table";
        $stmt = $this->bd->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll();
        $formData = [];
        $dataSource = ($method == 'POST') ? Flight::request()->data : Flight::request()->query;

        foreach ($columns as $column) {
            $columnName = $column['Field'];

            if (in_array($columnName, $omitColumns))
                continue;

            if (isset($dataSource[$columnName]))
                $formData[$columnName] = $dataSource[$columnName];
            else
                $formData[$columnName] = null;
        }

        return $formData;
    }

    public function insertData($table, $omitColumns = [], $method = 'POST', $nullable = []): array
    {
        try {
            $formData = $this->getFormData($table, $omitColumns, $method);
            foreach ($formData as $key => $value) {
                if ($value === null && !in_array($key, $nullable)) {
                    $formDataStr = print_r($formData, true);
                    return [
                        'success' => false,
                        'message' => "Le champ `$key` est obligatoire mais n'a pas été fourni. Contenu complet de \$formData : " . $formDataStr
                    ];
                }
            }

            $columns = array_keys($formData);
            $values = array_values($formData);
            $columnNames = implode(", ", $columns);
            $placeholders = implode(", ", array_fill(0, count($columns), '?'));
            $query = "INSERT INTO $table ($columnNames) VALUES ($placeholders)";
            $stmt = $this->bd->prepare($query);

            if ($stmt->execute($values)) {
                return [
                    'success' => true,
                    'message' => "Les données ont été insérées avec succès dans la table `$table`."
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Échec de l'insertion des données dans la table `$table`."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de l'insertion : " . $e->getMessage()
            ];
        }
    }

    public function checkLogin($table, $omitColumns = [], $method = 'POST', $return = []): array
    {
        try {
            $formData = $this->getFormData($table, $omitColumns, $method);
            $requiredColumns = array_diff(array_keys($formData), $omitColumns);

            foreach ($requiredColumns as $column) {
                if (!isset($formData[$column]) || $formData[$column] === null) {
                    return [
                        'success' => false,
                        'message' => "Le champ `$column` est obligatoire mais n'a pas été fourni. Contenu complet : " . json_encode($formData)
                    ];
                }
            }
            $conditions = [];
            $values = [];
            foreach ($formData as $key => $value) {
                if (!in_array($key, $omitColumns)) {
                    $conditions[] = "$key = ?";
                    $values[] = $value;
                }
            }

            $whereClause = implode(' AND ', $conditions);
            $query = "SELECT * FROM $table WHERE $whereClause";
            $stmt = $this->bd->prepare($query);
            $stmt->execute($values);

            if ($stmt->rowCount() > 0) {
                $data = $stmt->fetch();
                if (!empty($return)) {
                    $filteredData = [];
                    foreach ($return as $column) {
                        if (array_key_exists($column, $data)) {
                            $filteredData[$column] = $data[$column];
                        }
                    }
                    return [
                        'success' => true,
                        'message' => "Connexion réussie.",
                        'data' => $filteredData
                    ];
                }
                return [
                    'success' => true,
                    'message' => "Connexion réussie.",
                    'data' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Nom d'utilisateur ou mot de passe incorrect.",
                    'data' => $formData
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la vérification des identifiants : " . $e->getMessage()
            ];
        }
    }

    public function insererDonnee($nomTable, $donnee): array
    {
        try {
            if (empty($donnee)) {
                return ["message" => "Les données sont vides.", "status" => "error"];
            }
            $colonnes = array_keys($donnee);
            $colonnesListe = implode(", ", $colonnes);
            $placeholders = implode(", ", array_map(function ($col) {
                return ":$col";
            }, $colonnes));
            $query = "INSERT INTO $nomTable ($colonnesListe) VALUES ($placeholders)";
            $stmt = $this->bd->prepare($query);
            $stmt->execute($donnee);
            return ["message" => "Insertion avec succès", "status" => "success"];
        } catch (Exception $e) {
            return ["message" => "Erreur lors de l'insertion: " . $e->getMessage(), "status" => "error"];
        }
    }

    public function insererDonnees($nomTable, $donnees)
    {
        try {
            if (empty($donnees)) {
                return ["message" => "Le tableau de données est vide.", "status" => "error"];
            }

            $colonnes = array_keys($donnees[0]);
            $colonnesListe = implode(", ", $colonnes);

            $placeholders = implode(", ", array_map(function ($col) {
                return ":$col";
            }, $colonnes));

            $query = "INSERT INTO $nomTable ($colonnesListe) VALUES ($placeholders)";
            $stmt = $this->bd->prepare($query);

            foreach ($donnees as $ligne) {
                $stmt->execute($ligne);
            }

            return ["message" => "Insertion avec succès", "status" => "success"];
        } catch (Exception $e) {
            return ["message" => "Erreur lors de l'insertion: " . $e->getMessage(), "status" => "error"];
        }
    }

    function getTableData($tableName, $conditions = [], $omitColumns = [], $join = null, $column = "*", $joinType = "INNER")
    {
        if (empty($tableName)) {
            return ["message" => 'Le nom de la table ne peut pas être vide.'];
        }

        $sql = "SELECT " . $column . " FROM $tableName";

        if ($join !== null && is_array($join)) {
            foreach ($join as $joinInfo) {
                if (isset($joinInfo[0], $joinInfo[1]) && is_array($joinInfo[1])) {
                    $table2 = $joinInfo[0];
                    $joinColumns = $joinInfo[1];
                    $onClauses = [];

                    foreach ($joinColumns as $columnPair) {
                        if (count($columnPair) === 2) {
                            $onClauses[] = "$columnPair[0] = $columnPair[1]";
                        }
                    }

                    if (!empty($onClauses)) {
                        $sql .= " $joinType JOIN $table2 ON " . implode(' AND ', $onClauses);
                    }
                } else {
                    return ["message" => 'Les informations de jointure sont incorrectes.'];
                }
            }
        }

        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $escapedValue = $this->bd->quote($value);
                $whereClauses[] = "$column = $escapedValue";
            }

            if (!empty($whereClauses)) {
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
        }

        $data = $this->bd->query($sql)->fetchAll();

        if (!empty($omitColumns)) {
            foreach ($data as &$row) {
                foreach ($omitColumns as $omit) {
                    if (array_key_exists($omit, $row)) {
                        unset($row[$omit]);
                    }
                }
            }
        }

        return $data;
    }

    function isIdUsedInTable($cell, $tableName)
    {
        if (empty($tableName) || empty($cell)) {
            return ["message" => 'Le nom de la table et la cellule ne peuvent pas être vides.'];
        }
        if (!is_array($cell) || count($cell) !== 1) {
            return ["message" => 'Format de cellule invalide. Utilisez ["nom_colonne" => valeur].'];
        }
        $column = key($cell);
        $value = current($cell);
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return ["message" => 'Caractères non autorisés dans le nom de table ou colonne.'];
        }
        try {
            $sql = "SELECT EXISTS(SELECT 1 FROM `$tableName` WHERE `$column` = :value) AS is_used";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([':value' => $value]);
            $result = $stmt->fetch();
            return ["used" => $result['is_used']];
        } catch (Exception $e) {
            return ["message" => "Erreur de base de données : " . $e->getMessage()];
        }
    }

    public function getLastInsertedId($table, $idColumn): array
    {
        try {
            $query = "SELECT MAX($idColumn) AS last_id FROM $table";
            $stmt = $this->bd->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result && isset($result['last_id'])) {
                return [
                    'success' => true,
                    'last_id' => $result['last_id'],
                    'message' => "Dernier ID récupéré avec succès."
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Aucun ID trouvé dans la table `$table`."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la récupération du dernier ID : " . $e->getMessage()
            ];
        }
    }


    public function updateData($table, $omitColumns = [], $method = 'POST', $conditions = []): array
    {
        try {
            $formData = $this->getFormData($table, $omitColumns, $method);
            foreach ($formData as $key => $value) {
                if ($value === null) {
                    $formDataStr = print_r($formData, true);
                    return [
                        'success' => false,
                        'message' => "Le champ `$key` est obligatoire mais n'a pas été fourni. Contenu complet de \$formData : " . $formDataStr
                    ];
                }
            }
            $setClauses = [];
            $values = [];
            foreach ($formData as $column => $value) {
                $setClauses[] = "$column = ?";
                $values[] = $value;
            }
            $setClause = implode(", ", $setClauses);
            if (empty($conditions)) {
                return [
                    'success' => false,
                    'message' => "Aucune condition fournie pour la mise à jour. Cela empêcherait une mise à jour accidentelle de toutes les lignes."
                ];
            }
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "$column = ?";
                $values[] = $value;
            }
            $whereClause = implode(" AND ", $whereClauses);
            $query = "UPDATE $table SET $setClause WHERE $whereClause";
            $stmt = $this->bd->prepare($query);
            if ($stmt->execute($values)) {
                return [
                    'success' => true,
                    'message' => "Les données ont été mises à jour avec succès dans la table `$table`."
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Échec de la mise à jour des données dans la table `$table`."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la mise à jour : " . $e->getMessage()
            ];
        }
    }

    function updateTableData($tableName, $data, $conditions = [])
    {
        if (empty($tableName)) {
            return 'Le nom de la table ne peut pas être vide.';
        }
        if (empty($data)) {
            return 'Les données à mettre à jour ne peuvent pas être vides.';
        }
        $setClauses = [];
        foreach ($data as $column => $value) {
            $escapedValue = $this->bd->quote($value);
            $setClauses[] = "$column = $escapedValue";
        }
        $sql = "UPDATE $tableName SET " . implode(', ', $setClauses);
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $escapedValue = $this->bd->quote($value);
                $whereClauses[] = "$column = $escapedValue";
            }
            if (!empty($whereClauses)) {
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
        }
        $result = $this->bd->exec($sql);
        return ['status' => 'success'];
    }

    public function deleteData($table, $conditions = []): array
    {
        try {
            if (empty($conditions)) {
                return [
                    'success' => false,
                    'message' => "Aucune condition fournie pour la suppression. Cela empêcherait une suppression accidentelle de toutes les lignes."
                ];
            }
            $whereClauses = [];
            $values = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "$column = ?";
                $values[] = $value;
            }
            $whereClause = implode(" AND ", $whereClauses);
            $query = "DELETE FROM $table WHERE $whereClause";
            $stmt = $this->bd->prepare($query);
            if ($stmt->execute($values)) {
                return [
                    'success' => true,
                    'message' => "Les données ont été supprimées avec succès de la table `$table`."
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Échec de la suppression des données de la table `$table`."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la suppression : " . $e->getMessage()
            ];
        }
    }

    public function getById($table, $idColumn, $idValue): array
    {
        try {
            if (empty($table) || empty($idColumn) || empty($idValue)) {
                return [
                    'success' => false,
                    'message' => "Le nom de la table, la colonne ID et la valeur ID sont obligatoires."
                ];
            }

            $query = "SELECT * FROM `$table` WHERE `$idColumn` = :id LIMIT 1";
            $stmt = $this->bd->prepare($query);
            $stmt->execute([':id' => $idValue]);
            $result = $stmt->fetch();

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Données récupérées avec succès.",
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Aucune donnée trouvée pour l'ID spécifié."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la récupération par ID : " . $e->getMessage()
            ];
        }
    }

    /**
     * Crée un PDF avec des colonnes adaptatives et une gestion intelligente de l'espace
     *
     * @param string $titre Titre du document
     * @param string $dateExport Date d'exportation
     * @param array $headers En-têtes du tableau
     * @param array $lignes Données du tableau
     * @param string $nomFichier Nom du fichier de sortie
     */
    public function creerPDF($titre, $dateExport, $headers, $lignes, $nomFichier = 'document.pdf')
    {
        require_once __DIR__ . '/fpdf186/fpdf.php';
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 20);

        // Configuration des styles
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(44, 62, 80);
        $pdf->SetLineWidth(0.3);

        // Titre et date
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 10, $titre, 0, 1, 'C', true);
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(0);
        $pdf->Cell(0, 6, 'Généré le: ' . $dateExport, 0, 1, 'R');
        $pdf->Ln(10);

        // Calcul des largeurs de colonnes optimales
        $marge = 10;
        $largeurDisponible = 190 - $marge * 2;
        $largeursColonnes = $this->calculerLargeursColonnePDFSimple($pdf, $headers, $lignes, $largeurDisponible);

        // En-têtes du tableau
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(52, 152, 219);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(255);

        foreach ($headers as $i => $header) {
            $pdf->Cell($largeursColonnes[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Données du tableau
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetFillColor(255);
        $pdf->SetTextColor(0);
        $pdf->SetDrawColor(44, 62, 80);

        foreach ($lignes as $ligne) {
            // Vérifier si on doit passer à la page suivante
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Réafficher les en-têtes si nouvelle page
                $pdf->SetFont('Arial', 'B', 10);
                foreach ($headers as $i => $header) {
                    $pdf->Cell($largeursColonnes[$i], 8, $header, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 9);
            }

            // Afficher la ligne de données
            $hauteurLigne = $this->calculerHauteurLigne($pdf, $ligne, $largeursColonnes);

            foreach ($ligne as $i => $colonne) {
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                if ($pdf->GetStringWidth($colonne) > $largeursColonnes[$i] - 2) {
                    // Texte trop long - on utilise MultiCell
                    $pdf->MultiCell($largeursColonnes[$i], 6, $colonne, 1, 'C');
                    $pdf->SetXY($x + $largeursColonnes[$i], $y);
                } else {
                    $pdf->Cell($largeursColonnes[$i], $hauteurLigne, $colonne, 1, 0, 'C');
                }
            }
            $pdf->Ln();
        }

        $pdf->Output('D', $nomFichier);
    }

    /**
     * Calcule les largeurs optimales des colonnes
     */
    private function calculerLargeursColonnePDFSimple($pdf, $headers, $lignes, $largeurDisponible)
    {
        $largeursMin = [];
        $largeursMax = [];
        $largeursPref = [];

        // Analyser toutes les cellules pour déterminer les largeurs
        $pdf->SetFont('Arial', '', 9);

        // En-têtes
        foreach ($headers as $i => $header) {
            $largeursMin[$i] = $pdf->GetStringWidth($header) + 4;
            $largeursPref[$i] = $largeursMin[$i];
        }

        // Contenu
        foreach ($lignes as $ligne) {
            foreach ($ligne as $i => $colonne) {
                $largeurCellule = $pdf->GetStringWidth($colonne) + 4;
                if (!isset($largeursMin[$i]) || $largeurCellule > $largeursMin[$i]) {
                    $largeursMin[$i] = $largeurCellule;
                }
                // Pour les long textes, on fixe une largeur max raisonnable
                $largeursMax[$i] = max($largeursMax[$i] ?? 0, min($largeurCellule, 60));
            }
        }

        // Calcul de répartition
        $totalLargeurMin = array_sum($largeursMin);
        $largeursFinales = [];

        if ($totalLargeurMin <= $largeurDisponible) {
            // Cas idéal - on peut utiliser les largeurs préférées
            $ratio = $largeurDisponible / $totalLargeurMin;
            foreach ($largeursMin as $i => $largeur) {
                $largeursFinales[$i] = $largeur * $ratio;
            }
        } else {
            // Cas contraint - on utilise les largeurs max avec redistribution
            $largeursFinales = $this->redistribuerLargeurs($largeursMin, $largeursMax, $largeurDisponible);
        }

        return $largeursFinales;
    }

    /**
     * Redistribue les largeurs quand l'espace est insuffisant
     */
    private function redistribuerLargeurs($largeursMin, $largeursMax, $largeurDisponible)
    {
        $largeursFinales = [];
        $largeurRestante = $largeurDisponible;
        $colonnesARedistribuer = count($largeursMin);

        // Première passe - attribution des largeurs min
        foreach ($largeursMin as $i => $largeur) {
            if ($largeur <= $largeurRestante / $colonnesARedistribuer) {
                $largeursFinales[$i] = $largeur;
                $largeurRestante -= $largeur;
                $colonnesARedistribuer--;
            }
        }

        // Deuxième passe - redistribution du reste
        if ($colonnesARedistribuer > 0) {
            $largeurMoyenne = $largeurRestante / $colonnesARedistribuer;
            foreach ($largeursMin as $i => $largeur) {
                if (!isset($largeursFinales[$i])) {
                    $largeursFinales[$i] = min($largeurMoyenne, $largeursMax[$i] ?? $largeurMoyenne);
                }
            }
        }

        return $largeursFinales;
    }

    /**
     * Calcule la hauteur nécessaire pour une ligne (pour les cellules multilignes)
     */
    private function calculerHauteurLigne($pdf, $ligne, $largeursColonnes)
    {
        $nbLines = 1;

        foreach ($ligne as $i => $colonne) {
            if ($pdf->GetStringWidth($colonne) > $largeursColonnes[$i] - 2) {
                // Estimation du nombre de lignes nécessaires
                $nbLines = max($nbLines, ceil($pdf->GetStringWidth($colonne) / $largeursColonnes[$i]));
            }
        }

        return $nbLines * 6; // 6mm par ligne
    }

    /**
     * Crée un fichier CSV contenant des en-têtes et des lignes de données.
     *
     * @param array $headers En-têtes du fichier CSV.
     * @param array $rows Lignes de données du fichier CSV.
     * @param string $nomFichier Nom du fichier CSV à générer.
     */
    public function creerCSV($headers, $rows, $nomFichier = 'document.csv')
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomFichier . '"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            throw new Exception("Impossible d'ouvrir le flux de sortie.");
        }

        // Ajouter les en-têtes
        fputcsv($output, $headers);

        // Ajouter les lignes de données
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public function generateBudgetPDF($startBalance, $startDate, $nbPeriod, $tableData, $filename)
    {
        require_once __DIR__ . '/fpdf186/fpdf.php';

        $pdf = new \FPDF();
        $pdf->SetAutoPageBreak(true, 25); // Marge inférieure de 25mm
        $pdf->SetMargins(15, 15, 15);

        // Configuration des styles
        $styles = [
            'title' => [44, 62, 80],
            'header' => [255, 255, 255],
            'period_header' => [70, 130, 180],
            'category' => [100, 100, 100],
            'row_even' => [255, 255, 255],
            'row_odd' => [255, 255, 255],
            'positive' => [0, 100, 0],
            'negative' => [200, 0, 0],
            'balance' => [255, 255, 255]
        ];

        // Entête du document
        $this->addPDFTitle($pdf, $startDate, $styles['title']);
        $this->addExerciseSummary($pdf, $startBalance, $startDate, $nbPeriod);
        $pdf->Ln(10);

        // Variables pour suivre les soldes
        $currentBalancePrev = $startBalance;
        $currentBalanceReal = $startBalance;

        // Par période
        for ($period = 1; $period <= $nbPeriod; $period++) {
            // Vérifier l'espace disponible avant d'ajouter une nouvelle période
            if ($pdf->GetY() > 220) { // 220mm = limite avant bas de page
                $pdf->AddPage();
            }

            // Entête de période
            $this->addPeriodHeader($pdf, $period, $styles['period_header']);

            // Solde de début
            $this->addBalanceRow($pdf, 'Solde debut', $currentBalancePrev, $currentBalanceReal, $styles['balance']);

            // En-têtes du tableau
            $this->addDataTableHeader($pdf, $styles['header']);

            // Données
            $rowCount = 0;
            foreach ($tableData as $category => $elements) {
                if ($category === 'Total' || $category === 'Solde debut') continue;

                // Vérifier l'espace avant d'ajouter une nouvelle catégorie
                if ($pdf->GetY() > 250) {
                    $pdf->AddPage();
                    $this->addPeriodHeader($pdf, $period, $styles['period_header']);
                    $this->addDataTableHeader($pdf, $styles['header']);
                }

                // Ligne de catégorie
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor($styles['category'][0], $styles['category'][1], $styles['category'][2]);
                $pdf->SetTextColor(255);
                $pdf->Cell(0, 8, $category, 1, 1, 'L', true);

                // Éléments
                foreach ($elements as $elementName => $periodData) {
                    // Vérifier l'espace avant d'ajouter une nouvelle ligne
                    if ($pdf->GetY() > 270) {
                        $pdf->AddPage();
                        $this->addPeriodHeader($pdf, $period, $styles['period_header']);
                        $this->addDataTableHeader($pdf, $styles['header']);
                    }

                    $rowCount++;
                    $fill = $rowCount % 2 ? $styles['row_even'] : $styles['row_odd'];
                    $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                    $pdf->SetTextColor(0);
                    $pdf->SetFont('Arial', '', 9);

                    $data = $periodData["Période $period"] ?? ['Prévision' => 0, 'Réalisation' => 0, 'Différence' => 0];

                    // Nom élément
                    $pdf->Cell(60, 8, '  ' . $elementName, 'LR', 0, 'L', true);

                    // Prévision
                    $pdf->Cell(40, 8, $this->formatAmount($data['Prévision']), 'LR', 0, 'R', true);

                    // Réalisation
                    $pdf->Cell(40, 8, $this->formatAmount($data['Réalisation']), 'LR', 0, 'R', true);

                    // Différence
                    $diff = $data['Différence'] ?? 0;

                    $pdf->Cell(40, 8, $this->formatAmount($diff), 'LR', 1, 'R', true);
                    $pdf->SetTextColor(0);

                    // Mise à jour des soldes
                    $currentBalancePrev += $data['Prévision'];
                    $currentBalanceReal += $data['Réalisation'];
                }
            }

            // Solde de fin
            $this->addBalanceRow($pdf, 'Solde fin', $currentBalancePrev, $currentBalanceReal, $styles['balance']);
            $pdf->Ln(10);

            // Calcul pour la période suivante
            $currentBalancePrev = $currentBalancePrev;
            $currentBalanceReal = $currentBalanceReal;
        }

        $pdf->Output('D', $filename);
    }

// Les fonctions auxiliaires restent identiques à la version précédente

// Fonctions auxiliaires
    private function addPDFTitle($pdf, $startDate, $color)
    {
        $pdf->AddPage();
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Arial', 'B', 16);

        $year = date('Y', strtotime($startDate));
        $pdf->Cell(0, 10, "Rapport Budgétaire $year", 0, 1, 'C', true);

        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 6, 'Généré le ' . date('d/m/Y'), 0, 1, 'R');
        $pdf->Ln(15);
    }

    private function addExerciseSummary($pdf, $startBalance, $startDate, $nbPeriod)
    {
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Résumé de l\'exercice:', 0, 1);

        $pdf->SetFont('Arial', '', 10);
        $infos = [
            'Date de début' => date('d/m/Y', strtotime($startDate)),
            'Nombre de périodes' => $nbPeriod,
            'Solde initial' => $this->formatAmount($startBalance)
        ];

        foreach ($infos as $label => $value) {
            $pdf->Cell(50, 6, $label . ':', 0, 0);
            $pdf->Cell(0, 6, $value, 0, 1);
        }
        $pdf->Ln(15);
    }

    private function addPeriodHeader($pdf, $period, $color)
    {
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, "Période $period", 0, 1, 'C', true);
        $pdf->Ln(8);
    }

    private function addDataTableHeader($pdf, $color)
    {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255);

        $header = ['Élément', 'Prévision', 'Réalisation', 'Différence'];
        $widths = [60, 40, 40, 40];

        for ($i = 0; $i < count($header); $i++) {
            $pdf->Cell($widths[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
    }

    private function addBalanceRow($pdf, $label, $prevision, $realisation, $color)
    {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(0);

        $pdf->Cell(60, 8, $label, 1, 0, 'L', true);
        $pdf->Cell(40, 8, $this->formatAmount($prevision), 1, 0, 'R', true);
        $pdf->Cell(40, 8, $this->formatAmount($realisation), 1, 0, 'R', true);

        $diff = $prevision - $realisation;
        $pdf->SetTextColor($diff < 0 ? 255 : 0, 0, $diff < 0 ? 0 : 255);
        $pdf->Cell(40, 8, $this->formatAmount($diff), 1, 1, 'R', true);
        $pdf->SetTextColor(0);
        $pdf->Ln(5);
    }

    private function formatAmount($amount): string
    {
        if (!is_numeric($amount)) return '-';
        return number_format($amount, 2, ',', ' ') . ' Ar';
    }

    private function addPDFHeader($pdf, $startDate, $color)
    {
        $pdf->AddPage();
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Arial', 'B', 16);

        $year = date('Y', strtotime($startDate));
        $pdf->Cell(0, 10, "Rapport Budgétaire $year", 0, 1, 'C', true);

        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 6, 'Généré le ' . date('d/m/Y'), 0, 1, 'R');
        $pdf->Ln(15);
    }

    private function addTableHeader($pdf, $currentPeriod, $color)
    {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255);

        // En-tête des colonnes
        $pdf->Cell(40, 8, 'Catégorie/Élément', 1, 0, 'C', true);

        for ($p = 1; $p <= $currentPeriod; $p++) {
            if ($p == $currentPeriod) {
                $pdf->Cell(30, 8, 'Prévision', 1, 0, 'C', true);
                $pdf->Cell(30, 8, 'Réalisation', 1, 0, 'C', true);
                $pdf->Cell(30, 8, 'Différence', 1, 0, 'C', true);
            } else {
                $pdf->Cell(90, 8, "Période $p", 1, 0, 'C', true);
            }
        }
        $pdf->Ln();
    }


    function ajouterPageTitre($pdf, $dateDebut, $color)
    {
        $pdf->AddPage();
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Arial', 'B', 16);

        $annee = date('Y', strtotime($dateDebut));
        $pdf->Cell(0, 10, 'Rapport Budgétaire ' . $annee, 0, 1, 'C', true);

        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 6, 'Généré le ' . date('d/m/Y'), 0, 1, 'R');
        $pdf->Ln(10);
    }

    function ajouterResumeExercice($pdf, $startBalance, $dateDebut, $nbPeriod)
    {
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Résumé de l\'exercice', 0, 1);
        $pdf->SetFont('Arial', '', 10);

        $infos = [
            'Date de début' => date('d/m/Y', strtotime($dateDebut)),
            'Nombre de périodes' => $nbPeriod,
            'Solde initial' => $this->formatMontant($startBalance)
        ];

        foreach ($infos as $label => $value) {
            $pdf->Cell(50, 6, $label . ' :', 0, 0);
            $pdf->Cell(0, 6, $value, 0, 1);
        }
        $pdf->Ln(15);
    }

    function genererTableauPeriode($pdf, $periode, $tableData, $colors)
    {
        // Titre période
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor($colors['titre'][0], $colors['titre'][1], $colors['titre'][2]);
        $pdf->Cell(0, 8, 'Période ' . $periode, 0, 1);
        $pdf->SetTextColor(0);

        // Calcul largeurs colonnes
        $largeurs = $this->calculerLargeursColonnes($pdf, $tableData);

        // En-têtes
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor($colors['entete'][0], $colors['entete'][1], $colors['entete'][2]);
        $pdf->SetTextColor(255);

        $pdf->Cell($largeurs['departement'], 8, 'Département', 1, 0, 'C', true);
        $pdf->Cell($largeurs['prevision'], 8, 'Prévision', 1, 0, 'C', true);
        $pdf->Cell($largeurs['realisation'], 8, 'Réalisation', 1, 0, 'C', true);
        $pdf->Cell($largeurs['difference'], 8, 'Différence', 1, 1, 'C', true);

        // Données
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0);
        $ligne = 0;

        foreach ($tableData as $deptName => $data) {
            if ($pdf->GetY() > 270) {
                $pdf->AddPage();
                // Réafficher en-têtes
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell($largeurs['departement'], 8, 'Département', 1, 0, 'C', true);
                $pdf->Cell($largeurs['prevision'], 8, 'Prévision', 1, 0, 'C', true);
                $pdf->Cell($largeurs['realisation'], 8, 'Réalisation', 1, 0, 'C', true);
                $pdf->Cell($largeurs['difference'], 8, 'Différence', 1, 1, 'C', true);
                $pdf->SetFont('Arial', '', 9);
            }

            // Couleur ligne
            $fill = $ligne % 2 ? $colors['ligne_paire'] : $colors['ligne_impaire'];
            $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
            $ligne++;

            // Formatage spécial totaux
            $isTotal = in_array($deptName, ['Solde début', 'Total']);
            if ($isTotal) {
                $pdf->SetFont('Arial', 'B', 9);
            }

            // Correction ici pour gérer les différents formats de données
            $periodeData = [];
            if (isset($data["Période $periode"])) {
                $periodeData = $data["Période $periode"];
            } elseif (is_array($data)) {
                // Si la structure est différente (pour les détails)
                foreach ($data as $element) {
                    if (isset($element["Période $periode"])) {
                        $periodeData = $element["Période $periode"];
                        break;
                    }
                }
            }

            // Valeurs par défaut si non trouvées
            $periodeData = array_merge([
                'Prévision' => '',
                'Réalisation' => '',
                'Différence' => ''
            ], $periodeData);

            // Cellules
            $pdf->Cell($largeurs['departement'], 8, $deptName, 1, 0, 'L', true);
            $pdf->Cell($largeurs['prevision'], 8, $this->formatMontant($periodeData['Prévision']), 1, 0, 'R', true);
            $pdf->Cell($largeurs['realisation'], 8, $this->formatMontant($periodeData['Réalisation']), 1, 0, 'R', true);

            // Différence colorée
            $diff = $periodeData['Différence'];
            if (is_numeric($diff)) {
                $pdf->SetTextColor($diff < 0 ? $colors['negatif'][0] : $colors['positif'][0],
                    $diff < 0 ? $colors['negatif'][1] : $colors['positif'][1],
                    $diff < 0 ? $colors['negatif'][2] : $colors['positif'][2]);
            }
            $pdf->Cell($largeurs['difference'], 8, $this->formatMontant($diff), 1, 1, 'R', true);
            $pdf->SetTextColor(0);

            if ($isTotal) {
                $pdf->SetFont('Arial', '', 9);
            }
        }
    }

    function calculerLargeursColonnes($pdf, $tableData)
    {
        // Largeurs minimales basées sur les en-têtes
        $pdf->SetFont('Arial', 'B', 10);
        $largeurs = [
            'departement' => $pdf->GetStringWidth('Département') + 6,
            'prevision' => $pdf->GetStringWidth('Prévision') + 6,
            'realisation' => $pdf->GetStringWidth('Réalisation') + 6,
            'difference' => $pdf->GetStringWidth('Différence') + 6
        ];

        $pdf->SetFont('Arial', '', 9);
        foreach ($tableData as $deptName => $data) {
            // Vérifier si c'est une structure simple ou détaillée
            if (isset($data["Période 1"])) {
                // Structure simple
                $periodeData = $data["Période 1"];
            } else {
                // Structure détaillée (catégories)
                $periodeData = [];
                foreach ($data as $element) {
                    if (isset($element["Période 1"])) {
                        $periodeData = $element["Période 1"];
                        break;
                    }
                }
            }

            $periodeData = array_merge([
                'Prévision' => '',
                'Réalisation' => '',
                'Différence' => ''
            ], $periodeData ?? []);

            $largeurs['departement'] = max($largeurs['departement'], $pdf->GetStringWidth($deptName) + 6);

            foreach (['Prévision', 'Réalisation', 'Différence'] as $col) {
                $value = $periodeData[$col] ?? '';
                $formatted = $this->formatMontant($value);
                $largeurs[strtolower($col)] = max($largeurs[strtolower($col)] ?? 0, $pdf->GetStringWidth($formatted) + 6);
            }
        }

        // Ajustement si trop large (190mm max)
        $totalLargeur = array_sum($largeurs);
        if ($totalLargeur > 190) {
            $ratio = 190 / $totalLargeur;
            foreach ($largeurs as $key => $value) {
                $largeurs[$key] = round($value * $ratio);
            }
        }

        return $largeurs;
    }

    function formatMontant($montant)
    {
        if (!is_numeric($montant)) return $montant;
        return number_format($montant, 2, ',', ' ') . ' Ar';
    }


    public function generateGlobalBudgetPDF($startBalance, $startDate, $nbPeriod, $tableData, $filename)
    {
        require_once __DIR__ . '/fpdf186/fpdf.php';
        $pdf = new \FPDF('L'); // Format paysage
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetMargins(10, 10, 10);

        // Configuration des styles
        $styles = [
            'title' => [44, 62, 80],
            'header' => [52, 152, 219],
            'department' => [70, 130, 180],
            'row_even' => [240, 240, 240],
            'row_odd' => [255, 255, 255],
            'positive' => [0, 100, 0],
            'negative' => [200, 0, 0],
            'balance' => [220, 240, 255]
        ];

        // Largeurs des colonnes
        $colWidths = [
            'department' => 40,
            'data' => 25  // Largeur fixe pour chaque colonne de données
        ];

        // Nombre de périodes par page
        $periodsPerPage = 2;
        $totalPages = ceil($nbPeriod / $periodsPerPage);

        for ($page = 0; $page < $totalPages; $page++) {
            $pdf->AddPage();

            // Déterminer les périodes affichées sur cette page
            $startPeriod = $page * $periodsPerPage + 1;
            $endPeriod = min(($page + 1) * $periodsPerPage, $nbPeriod);

            // En-tête du document
            if ($page == 0) {
                $this->addPDFHeader($pdf, $startDate, $styles['title']);
                $this->addExerciseSummary($pdf, $startBalance, $startDate, $nbPeriod);
            }

            // En-têtes du tableau
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor($styles['header'][0], $styles['header'][1], $styles['header'][2]);
            $pdf->SetTextColor(255);
            $pdf->Cell($colWidths['department'], 10, 'Departement', 1, 0, 'C', true);

            for ($i = $startPeriod; $i <= $endPeriod; $i++) {
                $pdf->Cell($colWidths['data'] * 3, 10, "Periode $i", 1, 0, 'C', true);
            }
            $pdf->Ln();

            // Sous-en-têtes
            $pdf->Cell($colWidths['department'], 8, '', 1, 0, 'C', true);
            for ($i = $startPeriod; $i <= $endPeriod; $i++) {
                $pdf->Cell($colWidths['data'], 8, 'Prevision', 1, 0, 'C', true);
                $pdf->Cell($colWidths['data'], 8, 'Realisation', 1, 0, 'C', true);
                $pdf->Cell($colWidths['data'], 8, 'Difference', 1, 0, 'C', true);
            }
            $pdf->Ln();

            // Données
            $pdf->SetFont('Arial', '', 9);
            $rowCount = 0;
            foreach ($tableData as $deptName => $data) {
                $rowCount++;
                $fill = $rowCount % 2 ? $styles['row_even'] : $styles['row_odd'];
                $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                $pdf->SetTextColor(0);

                if ($deptName === 'Total' || $deptName === 'Solde debut') {
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->SetFillColor($styles['balance'][0], $styles['balance'][1], $styles['balance'][2]);
                }

                $pdf->Cell($colWidths['department'], 8, $deptName, 1, 0, 'L', true);

                for ($i = $startPeriod; $i <= $endPeriod; $i++) {
                    $periodData = $data["Période $i"] ?? ['Prévision' => '', 'Réalisation' => '', 'Différence' => ''];

                    $pdf->Cell($colWidths['data'], 8, $this->formatAmount($periodData['Prévision']), 1, 0, 'R', true);
                    $pdf->Cell($colWidths['data'], 8, $this->formatAmount($periodData['Réalisation']), 1, 0, 'R', true);

                    $diff = $periodData['Différence'] ?? 0;
                    $pdf->SetTextColor($diff < 0 ? $styles['negative'][0] : $styles['positive'][0],
                        $diff < 0 ? $styles['negative'][1] : $styles['positive'][1],
                        $diff < 0 ? $styles['negative'][2] : $styles['positive'][2]);
                    $pdf->Cell($colWidths['data'], 8, $this->formatAmount($diff), 1, 0, 'R', true);
                    $pdf->SetTextColor(0);
                }
                $pdf->Ln();

                if ($deptName === 'Total' || $deptName === 'Solde début') {
                    $pdf->SetFont('Arial', '', 9);
                }
            }
        }

        $pdf->Output('D', $filename);
    }


    private function addTableHeaders($pdf, $nbPeriod, $colWidths, $headerColor)
    {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $pdf->SetTextColor(255);

        $pdf->Cell($colWidths['department'], 10, 'Département', 1, 0, 'C', true);
        for ($i = 1; $i <= $nbPeriod; $i++) {
            $pdf->Cell($colWidths['data'] * 3, 10, "Période $i", 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->Cell($colWidths['department'], 8, '', 1, 0, 'C', true);
        for ($i = 1; $i <= $nbPeriod; $i++) {
            $pdf->Cell($colWidths['data'], 8, 'Prévision', 1, 0, 'C', true);
            $pdf->Cell($colWidths['data'], 8, 'Réalisation', 1, 0, 'C', true);
            $pdf->Cell($colWidths['data'], 8, 'Différence', 1, 0, 'C', true);
        }
        $pdf->Ln();
    }
}