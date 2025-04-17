<?php
session_start();

require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('1043957684282-e3oc7don9hun53bmg23bqvk8ie6lr1tu.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://alona-klymiuk.rf.gd/google-callback.php');
$client->addScope('email');
$client->addScope('profile');

$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
