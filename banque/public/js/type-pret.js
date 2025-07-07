// CRUD Type Pret JS
const apiBase = "/banque/api/";

function chargerTypePrets() {
    ajax("GET", apiBase, "type-prets", null, (data) => {
        const tbody = document.querySelector("#table-type-prets tbody");
        tbody.innerHTML = "";
        
        if (data && Array.isArray(data)) {
            data.forEach(e => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${e.type_pret_id}</td>
                    <td>${e.libelle}</td>
                    <td>${e.taux}%</td>
                    <td>${e.duree} ans</td>
                    <td>
                      <button class="btn-edit" onclick='remplirFormulaire(${JSON.stringify(e)})'>✏️ Modifier</button>
                      <button class="btn-delete" onclick='supprimerTypePret(${e.type_pret_id})'>🗑️ Supprimer</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5">Aucun type de prêt trouvé</td></tr>';
        }
    });
}

function ajouterOuModifier() {
    const id = document.getElementById("id").value;
    const libelle = document.getElementById("libelle").value;
    const taux = document.getElementById("taux").value;
    const duree = document.getElementById("duree").value;

    // Validation des données
    if (!libelle || !taux || !duree) {
        alert("Veuillez remplir tous les champs");
        return;
    }

    const data = `libelle=${encodeURIComponent(libelle)}&taux=${taux}&duree=${duree}&modalite_id=1`;

    if (id) {
        ajax("PUT", apiBase, `type-prets/${id}`, data, (response) => {
            if (response.error) {
                alert("Erreur lors de la modification : " + response.error);
            } else {
                resetForm();
                chargerTypePrets();
                alert("Type de prêt modifié avec succès");
            }
        });
    } else {
        ajax("POST", apiBase, "type-prets", data, (response) => {
            if (response.error) {
                alert("Erreur lors de l'ajout : " + response.error);
            } else {
                resetForm();
                chargerTypePrets();
                alert("Type de prêt ajouté avec succès");
            }
        });
    }
}

function remplirFormulaire(e) {
    document.getElementById("id").value = e.type_pret_id;
    document.getElementById("libelle").value = e.libelle;
    document.getElementById("taux").value = e.taux;
    document.getElementById("duree").value = e.duree;
    
    // Changer le texte du bouton
    const button = document.querySelector('button[onclick="ajouterOuModifier()"]');
    button.textContent = "Modifier";
}

function supprimerTypePret(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce type de prêt ?")) {
        ajax("DELETE", apiBase, `type-prets/${id}`, null, (response) => {
            if (response.error) {
                alert("Erreur lors de la suppression : " + response.error);
            } else {
                chargerTypePrets();
                alert("Type de prêt supprimé avec succès");
            }
        });
    }
}

function resetForm() {
    document.getElementById("id").value = "";
    document.getElementById("libelle").value = "";
    document.getElementById("taux").value = "";
    document.getElementById("duree").value = "";
    
    // Remettre le texte du bouton
    const button = document.querySelector('button[onclick="ajouterOuModifier()"]');
    button.textContent = "Ajouter / Modifier";
}

document.addEventListener("DOMContentLoaded", chargerTypePrets);
