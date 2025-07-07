<?php

use flight\Engine;
use flight\net\Router;
use app\services\typePretService;

/**
 * @var Router $router
 * @var Engine $app
 */

// Route pour afficher la page
Flight::route('GET /nav/type-prets', function () {
    Flight::render("type-pret");
});

// Routes API pour les types de prêt
Flight::route('GET /api/type-prets', function () {
    $db = Flight::db();
    $service = new typePretService($db);
    $result = $service->getAllTypePrets();
    Flight::json($result);
});

Flight::route('GET /api/type-prets/@id', function ($id) {
    $db = Flight::db();
    $service = new typePretService($db);
    $result = $service->getTypePretById($id);
    if ($result) {
        Flight::json($result);
    }
});

Flight::route('POST /api/type-prets', function () {
    $data = Flight::request()->data->getData();
    $db = Flight::db();
    $service = new typePretService($db);
    $result = $service->createTypePret($data);
    if ($result) {
        Flight::json($result, 201);
    }
});

Flight::route('PUT /api/type-prets/@id', function ($id) {
    $data = Flight::request()->data->getData();
    $db = Flight::db();
    $service = new typePretService($db);
    $result = $service->updateTypePret($id, $data);
    if ($result) {
        Flight::json($result);
    }
});

Flight::route('DELETE /api/type-prets/@id', function ($id) {
    $db = Flight::db();
    $service = new typePretService($db);
    $result = $service->deleteTypePret($id);
    if ($result) {
        Flight::json($result);
    }
});

