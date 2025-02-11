<?php
//// Everything after this line (2) is Core Functionality and no changes are permitted until after line (188).
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

//Allow Higher Memory Limit for PHP
// ini_set("memory_limit","512M");

// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['Meraki'] = [ // Plugin Name
    'name' => 'Meraki', // Plugin Name
    'author' => 'TinyTechLabUK', // Who wrote the plugin
    'category' => 'Network', // One to Two Word Description
    'link' => 'https://github.com/PHP-EF/plugin-meraki', // Link to plugin info
    'version' => '1.0.1', // SemVer of plugin
    'image' => 'logo.png', // 1:1 non transparent image for plugin
    'settings' => true, // does plugin need a settings modal?
    'api' => '/api/plugin/Meraki/settings', // api route for settings page, or null if no settings page
];

class Meraki extends phpef {
    private $merakipluginConfig;

	public function __construct() {
		parent::__construct();
        $this->merakipluginConfig = $this->config->get('Plugins','Meraki') ?? [];
	}

    //Protected function to define the settings for this plugin
    public function _pluginGetSettings() {
        return array(
            'Plugin Settings' => array(
                $this->settingsOption('auth', 'ACL-ADMIN', ['label' => 'Meraki Admin ACL']),
            ),
            'Meraki Settings' => array(
                $this->settingsOption('url', 'Meraki-URL', [
                    'label' => 'Meraki URL',
                    'description' => 'The URL of your Meraki dashboard (e.g., https://api.meraki.com/). Uses port 443 for HTTPS.'
                ]),
                $this->settingsOption('password', 'Meraki-Api-Token', [
                    'label' => 'Meraki API Token',
                    'description' => 'API Token for Meraki authentication'
                ])
            )
        );
    }

    //Protected function to define the Meraki URL to build the required URI for the Meraki Plugin
    private function getMerakiUrl() {
        if (!isset($this->merakipluginConfig['Meraki-URL']) || empty($this->merakipluginConfig['Meraki-URL'])) {
            throw new Exception("Meraki URL not configured. Please set 'Meraki-URL' in config.json");
        }
        // Remove trailing slash if present
        return rtrim($this->merakipluginConfig['Meraki-URL'], '/');
    }

    //Protected function to define the api and build the required URL for api in the plugin
    private function getApiEndpoint($path, $params = []) {
        $baseUrl = $this->getMerakiUrl();
        // Ensure path starts with /v1
        if (strpos($path, '/api/v1/organizations/394294/') !== 0) {
            $path = '/api/v1/organizations/394294/' . ltrim($path, '/');
        }
        $url = $baseUrl . $path;
        
        // Add query parameters if they exist
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $this->logging->writeLog('Meraki', 'Full API URL: ' . $url, 'debug');
        return $url;
    }

        //Protected function to decrypt the password and build out a valid token for Meraki Plugin
    private function getAccessToken($force = false) {
        try {
            if (!isset($this->merakipluginConfig['Meraki-Api-Token'])) {
                throw new Exception("Meraki API Token not configured. Please set 'Meraki-Api-Token' in config.json");
            }

            try {
                $apiToken = decrypt($this->merakipluginConfig['Meraki-Api-Token'], $this->config->get('Security','salt'));
            } catch (Exception $e) {
                $this->api->setAPIResponse('Error', 'Unable to decrypt Meraki API Token');
                $this->logging->writeLog('Meraki-Api-Token', 'Unable to decrypt Meraki API Token', 'error');
                return false;
            }

            return $apiToken;

        } catch (Exception $e) {
            $this->logging->writeLog('Meraki', 'Error getting access token: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    

        //Protected function to for making API Request to Meraki for Get/Post/Put/Delete
    public function makeApiRequest($Method, $Uri, $Data = "") {
        try {
            if (!isset($this->merakipluginConfig['Meraki-URL']) || empty($this->merakipluginConfig['Meraki-URL'])) {
                throw new Exception("Meraki URL not configured");
            }

            $apiToken = $this->getAccessToken();
            // print_r($apiToken);
            if (!$apiToken) {
                throw new Exception("Failed to get Meraki API Token");
            }

            $headers = array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiToken
            );

            $url = $this->getApiEndpoint($Uri);
            // print_r($url);
  
            if (in_array($Method, ["GET", "get"])) {
                $Result = $this->api->query->$Method($url, $headers);
            } else {
                $Result = $this->api->query->$Method($url, $Data, $headers);
            }

            // $this->logging->writeLog('Meraki', 'API Response: ' . json_encode($Result), 'debug');

            if (isset($Result->status_code) && $Result->status_code >= 400) {
                throw new Exception("API request failed with status code: " . $Result->status_code . ", Response: " . json_encode($Result));
            }
            
            return $Result;

        } catch (Exception $e) {
            $this->logging->writeLog('Meraki', 'Meraki API Error: ' . $e->getMessage(), 'error');
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    //// Everything after this line (221) is features and is permitted to be edited to build out the plugin features

    public function getFullApiUrl() {
        try {
            $url = $this->getApiEndpoint("");
            // print_r($url);
            $this->logging->writeLog('Meraki', 'Full API URL: ' . $url, 'debug');
            $this->api->setAPIResponse('Success', 'API URL Retrieved');
            $this->api->setAPIResponseData(['url' => $url]);
            return true;
        } catch (Exception $e) {
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function GetDevicesAvailabilities() {
        try {
            $this->logging->writeLog('Meraki', 'Starting GetDevicesAvailabilities request...', 'info');
            $result = $this->makeApiRequest("GET", "devices/availabilities");  
            $this->logging->writeLog('Meraki', 'Device availabilities API Result Type: ' . gettype($result), 'debug');
            $this->logging->writeLog('Meraki', 'Device availabilities API Raw Result: ' . print_r($result, true), 'debug');
            
            if ($result === false) {
                throw new Exception("API call returned false");
            }
            
            $this->api->setAPIResponse('Success', 'Device availabilities report retrieved');
            $this->api->setAPIResponseData($result);
            return true;
        } catch (Exception $e) {
            $this->logging->writeLog('Meraki', 'Device availabilities API Error: ' . $e->getMessage(), 'error');
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }
   
    public function GetNetworksTraffic() {
        try {
            // First get all devices to get network IDs
            $devicesResult = $this->makeApiRequest("GET", "devices/availabilities");
            if ($devicesResult === false) {
                throw new Exception("Failed to get devices");
            }

            // Extract unique network IDs
            $networkIds = [];
            foreach ($devicesResult as $device) {
                if (isset($device['network']['id'])) {
                    $networkIds[] = $device['network']['id'];
                }
            }
            $networkIds = array_values(array_unique($networkIds));

            if (empty($networkIds)) {
                throw new Exception("No network IDs found");
            }

            // Get traffic data for each network
            $trafficData = [];
            foreach ($networkIds as $index => $networkId) {
                $url = str_replace('{networkId}', trim($networkId), 'networks/{networkId}/traffic');
                $this->logging->writeLog('Meraki', 'Making traffic request for URL: ' . $url, 'debug');
                
                $result = $this->makeApiRequest("GET", $url);
                $this->logging->writeLog('Meraki', 'Traffic API result: ' . print_r($result, true), 'debug');
                
                if ($result === false) {
                    $this->logging->writeLog('Meraki', 'Traffic request failed for network: ' . $networkId, 'warning');
                    continue;
                }
                
                if (!empty($result)) {
                    $trafficData[$networkId] = $result;
                } else {
                    $this->logging->writeLog('Meraki', 'Empty traffic result for network: ' . $networkId, 'warning');
                }
            }
            
            if (empty($trafficData)) {
                $this->logging->writeLog('Meraki', 'No traffic data found for any networks', 'warning');
                $this->api->setAPIResponse('Warning', 'No traffic data available');
                $this->api->setAPIResponseData([]);
            } else {
                $this->api->setAPIResponse('Success', 'Networks traffic report retrieved');
                $this->api->setAPIResponseData($trafficData);
            }
            return true;
        } catch (Exception $e) {
            $this->logging->writeLog('Meraki', 'NetworksTraffic API Error: ' . $e->getMessage(), 'error');
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }
}