<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des types de prêt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-container input {
            margin: 5px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-container button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        
        .form-container button:hover {
            background-color: #0056b3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-edit {
            background-color: #28a745;
            color: white;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #218838;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <h1>Gestion des types de prêt</h1>
    <div class="form-container">
        <input type="hidden" id="id">
        <input type="text" id="libelle" placeholder="Libellé (ex: Normal)">
        <input type="number" id="taux" placeholder="Taux (ex: 2.5)" step="0.01">
        <input type="number" id="duree" placeholder="Durée en années (ex: 5)">
        <button onclick="ajouterOuModifier()">Ajouter / Modifier</button>
        <button onclick="resetForm()" style="background-color: #6c757d;">Annuler</button>
    </div>
    <table id="table-type-prets">
        <thead>
            <tr>
                <th>ID</th>
                <th>Libellé</th>
                <th>Taux</th>
                <th>Durée</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <script src="/banque/public/js/general.js"></script>
    <script src="/banque/public/js/type-pret.js"></script>
</body>
</html>