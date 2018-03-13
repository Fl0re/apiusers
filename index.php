<?php
use \Firebase\JWT\JWT;

date_default_timezone_set('Europe/Paris');


header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");


if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
    }

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }

    exit(0);
}

/*
 * Méthode pour récupérer le tableau JSON envoyé côté client
 */

//$post = file_get_contents('php://input');
//$post = json_decode($post, true);

require "flight/Flight.php";
require "autoload.php";

$cfg = [
    'key'   =>  'azertyuiop',
    'algo'  =>  ['HS256']
];

//Enregistrer en global dans Flight le BddManager
Flight::set('cfg', $cfg);
Flight::set("BddManager", new BddManager());
Flight::set('JWTAuth', new JWTAuth());


Flight::route("POST /auth/connexion", function() {

    $cfg = Flight::get("cfg");


    $inputs = [
        'email'     =>  Flight::request()->data->email,
        'password'  =>  Flight::request()->data->password,
    ];

    if( isset($inputs['email']) && isset($inputs['password']) ) {
        $bddManager = Flight::get("BddManager");
        $repo = $bddManager->getUserRepository();

        $user = $repo->login($inputs['email'], $inputs['password']);

        if( $user ) {

            $JWTAuth = Flight::get("JWTAuth");
            $token = $JWTAuth->createToken($user, $cfg);

            echo json_encode([
                'success'   =>  true,
                'token'     =>  $token,
                'user'      =>  $user,
            ]);

        } else {
            echo json_encode([
                'success'   =>  false,
                'error'     =>  'Aucun utilisateur ne correspond à ces identifiants'
            ]);
        }
    } else {
        echo json_encode([
            'success'   =>  false,
            'error'     =>  'Les champs ne sont pas valides',
        ]);
    }
});

/**
 * Traitement de la requête d'inscription
 */
Flight::route("POST /auth/inscription", function() {
    $inputs = [
        'email'         =>  Flight::request()->data->email,
        'password'      =>  Flight::request()->data->password,
        'lastname'      =>  Flight::request()->data->lastname,
        'firstname'     =>  Flight::request()->data->firstname,
    ];

    $error = false;
    foreach( $inputs as $key => $value ) {
        if( !isset($inputs[$key]) || strlen($value) < 1 ) {
            $error = true;
        }
    }

    if( !$error ) {
        $bddManager = Flight::get("BddManager");
        $repo = $bddManager->getUserRepository();

        $user = new User();
        $user->setEmail($inputs['email']);
        $user->setFirstName($inputs['firstname']);
        $user->setLastName($inputs['lastname']);
        $user->setPassword($inputs['password']);

        $checkEmail = $repo->getByEmail($user);
        if( $checkEmail ) {
            echo json_encode([
                'success'   =>  false,
                'error'     =>  'Cette adresse email est déjà enregistrée'
            ]);
            exit;
        }

        $rowCount = $repo->save($user);
        if( $rowCount ) {
            $cfg = Flight::get('cfg');
            $JWTAuth = Flight::get("JWTAuth");

            $user = new User();
            $user->setId($rowCount);

            $user = $repo->getById($user);
            $token = $JWTAuth->createToken($user, $cfg);

            echo json_encode([
                'success'   =>  true,
                'user'      =>  $user,
                'token'     =>  $token
            ]);
            exit;
        } else {
            echo json_encode([
                'success'   =>  false,
                'error'     =>  'Une erreur est survenue durant l\'inscription',
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success'   =>  false,
            'error'     =>  'Tous les champs sont obligatoires'
        ]);
        exit;
    }
});



Flight::start();