CREATE DATABASE model_ws CHARACTER SET utf8mb4;

USE model_ws;

CREATE TABLE etudiant
(
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nom    VARCHAR(100),
    prenom VARCHAR(100),
    email  VARCHAR(100),
    age    INT
);