CREATE TABLE enum_pret
(
    id       INT AUTO_INCREMENT PRIMARY KEY,
    libelle_ VARCHAR(255) NOT NULL
);

CREATE TABLE modalite
(
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nb_mois INT NOT NULL
);

CREATE TABLE clients
(
    id     INT AUTO_INCREMENT PRIMARY KEY,
    email  VARCHAR(255) NOT NULL,
    nom    VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    mdp    VARCHAR(255) NOT NULL
);

CREATE TABLE employees
(
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nom    VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    email  VARCHAR(255) NOT NULL,
    mdp    VARCHAR(255) NOT NULL
);

CREATE TABLE config
(
    id               INT AUTO_INCREMENT PRIMARY KEY,
    libelle          VARCHAR(255) NOT NULL,
    date_application DATE         NOT NULL
);

CREATE TABLE tresorerie
(
    id             INT AUTO_INCREMENT PRIMARY KEY,
    date_mouvement DATE           NOT NULL,
    solde          DECIMAL(15, 2) NOT NULL
);

CREATE TABLE compte_client
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    numero        VARCHAR(50) NOT NULL,
    date_creation DATE,
    client_id     INT         NOT NULL,
    FOREIGN KEY (client_id) REFERENCES clients (id)
);

CREATE TABLE mouvement_solde
(
    id               INT PRIMARY KEY AUTO_INCREMENT,
    montant          DECIMAL(15, 2) NOT NULL,
    date_mouvement   DATE,
    compte_client_id INT            NOT NULL,
    FOREIGN KEY (compte_client_id) REFERENCES compte_client (id)
);

CREATE TABLE enum_status_compte
(
    id      INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(255) NOT NULL
);

CREATE TABLE type_pret
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    libelle     VARCHAR(50)    NOT NULL,
    taux        DECIMAL(15, 2) NOT NULL,
    duree       INT            NOT NULL,
    modalite_id INT            NOT NULL,
    FOREIGN KEY (modalite_id) REFERENCES modalite (id)
);

CREATE TABLE paiements
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    date_paiement DATE           NOT NULL,
    montant       DECIMAL(15, 2) NOT NULL,
    client_id     INT            NOT NULL,
    employees_id  INT,
    FOREIGN KEY (client_id) REFERENCES clients (id),
    FOREIGN KEY (employees_id) REFERENCES employees (id)
);

CREATE TABLE paiement_modalite
(
    id              INT AUTO_INCREMENT PRIMARY KEY,
    date_retour     DATE           NOT NULL,
    numero_paiement INTEGER,
    montant_a_payer DECIMAL(15, 2) NOT NULL,
    paiement_id     INT            NOT NULL,
    FOREIGN KEY (paiement_id) REFERENCES paiements (id)
);

CREATE TABLE pret
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    montant      DECIMAL(15, 2) NOT NULL,
    type_pret_id INT            NOT NULL,
    FOREIGN KEY (type_pret_id) REFERENCES type_pret (id)
);

CREATE TABLE status_pret
(
    enum_pret_id INT,
    type_pret_id INT,
    date_status  DATE NOT NULL,
    PRIMARY KEY (enum_pret_id, type_pret_id),
    FOREIGN KEY (enum_pret_id) REFERENCES enum_pret (id),
    FOREIGN KEY (type_pret_id) REFERENCES type_pret (id)
);

CREATE TABLE status_client
(
    compte_client_id      INT,
    enum_status_compte_id INT,
    date_status           DATE NOT NULL,
    PRIMARY KEY (compte_client_id, enum_status_compte_id),
    FOREIGN KEY (compte_client_id) REFERENCES compte_client (id),
    FOREIGN KEY (enum_status_compte_id) REFERENCES enum_status_compte (id)
);
