CREATE TABLE enum_pret(
   enum_pret_id INT,
   libelle_ VARCHAR(255)  NOT NULL,
   PRIMARY KEY(enum_pret_id)
);

CREATE TABLE modalite(
   modalite_id INT,
   nb_mois INT NOT NULL,
   PRIMARY KEY(modalite_id)
);

CREATE TABLE clients(
   client_id INT,
   email VARCHAR(255)  NOT NULL,
   nom VARCHAR(255)  NOT NULL,
   prenom VARCHAR(255)  NOT NULL,
   mdp VARCHAR(255)  NOT NULL,
   PRIMARY KEY(client_id)
);

CREATE TABLE employees(
   employees_id INT,
   nom VARCHAR(255)  NOT NULL,
   prenom VARCHAR(255)  NOT NULL,
   email VARCHAR(255)  NOT NULL,
   mdp VARCHAR(255)  NOT NULL,
   PRIMARY KEY(employees_id)
);

CREATE TABLE config(
   config_id INT,
   libelle VARCHAR(255)  NOT NULL,
   date_application DATE NOT NULL,
   PRIMARY KEY(config_id)
);

CREATE TABLE tresorerie(
   tresorerie_id INT,
   date_mouvement DATE NOT NULL,
   solde DECIMAL(15,2)   NOT NULL,
   PRIMARY KEY(tresorerie_id)
);

CREATE TABLE compte_client(
   compte_client_id INT,
   numero VARCHAR(50)  NOT NULL,
   date_creation DATE,
   client_id INT NOT NULL,
   PRIMARY KEY(compte_client_id),
   FOREIGN KEY(client_id) REFERENCES clients(client_id)
);

CREATE TABLE mouvement_solde(
   id_mouvement_solde INT,
   montant DECIMAL(15,2)   NOT NULL,
   date_mouvement DATE,
   compte_client_id INT NOT NULL,
   PRIMARY KEY(id_mouvement_solde),
   FOREIGN KEY(compte_client_id) REFERENCES compte_client(compte_client_id)
);

CREATE TABLE enum_status_compte(
   enum_status_compte_id INT,
   libelle VARCHAR(255)  NOT NULL,
   PRIMARY KEY(enum_status_compte_id)
);

CREATE TABLE type_pret(
   type_pret_id INT,
   libelle VARCHAR(50)  NOT NULL,
   taux DECIMAL(15,2)   NOT NULL,
   duree INT NOT NULL,
   modalite_id INT NOT NULL,
   PRIMARY KEY(type_pret_id),
   FOREIGN KEY(modalite_id) REFERENCES modalite(modalite_id)
);

CREATE TABLE payements(
   payement_id INT,
   date_paiement DATE NOT NULL,
   montant DECIMAL(15,2)   NOT NULL,
   client_id INT NOT NULL,
   employees_id INT,
   PRIMARY KEY(payement_id),
   FOREIGN KEY(client_id) REFERENCES clients(client_id),
   FOREIGN KEY(employees_id) REFERENCES employees(employees_id)
);

CREATE TABLE payement_modalite(
   paiement_modalite_id INT,
   date_fin DATE NOT NULL,
   montant_a_payer DECIMAL(15,2)   NOT NULL,
   payement_id INT NOT NULL,
   PRIMARY KEY(paiement_modalite_id),
   FOREIGN KEY(payement_id) REFERENCES payements(payement_id)
);

CREATE TABLE pret(
   pret_id INT,
   montant DECIMAL(15,2)   NOT NULL,
   type_pret_id INT NOT NULL,
   PRIMARY KEY(pret_id),
   FOREIGN KEY(type_pret_id) REFERENCES type_pret(type_pret_id)
);

CREATE TABLE status_pret(
   enum_pret_id INT,
   type_pret_id INT,
   date_status DATE NOT NULL,
   PRIMARY KEY(enum_pret_id, type_pret_id),
   FOREIGN KEY(enum_pret_id) REFERENCES enum_pret(enum_pret_id),
   FOREIGN KEY(type_pret_id) REFERENCES type_pret(type_pret_id)
);

CREATE TABLE status_client(
   compte_client_id INT,
   enum_status_compte_id INT,
   date_status DATE NOT NULL,
   PRIMARY KEY(compte_client_id, enum_status_compte_id),
   FOREIGN KEY(compte_client_id) REFERENCES compte_client(compte_client_id),
   FOREIGN KEY(enum_status_compte_id) REFERENCES enum_status_compte(enum_status_compte_id)
);
