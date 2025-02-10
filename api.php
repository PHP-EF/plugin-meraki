<?php
// **
// USED TO DEFINE API ENDPOINTS
// **

// Get Meraki Plugin Settings
$app->get('/plugin/Meraki/settings', function ($request, $response, $args) {
    $Meraki = new Meraki();
    if ($Meraki->auth->checkAccess('ADMIN-CONFIG')) {
        $Meraki->api->setAPIResponseData($Meraki->_pluginGetSettings());
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Test Meraki URL  
$app->get('/plugin/Meraki/test-url', function ($request, $response, $args) {
    $Meraki = new Meraki();
    $Meraki->getFullApiUrl();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Meraki Device Availabilities
$app->get('/plugin/Meraki/getdevicesavailabilities', function ($request, $response, $args) {
    $Meraki = new Meraki();
    $Meraki->GetDevicesAvailabilities();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Meraki Networks Traffic
$app->get('/plugin/Meraki/getnetworkstraffic', function ($request, $response, $args) {
    $Meraki = new Meraki();
    $Meraki->GetNetworksTraffic();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});