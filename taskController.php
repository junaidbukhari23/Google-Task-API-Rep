
<?php

require_once __DIR__ . '/vendor/autoload.php';

define('APPLICATION_NAME', 'Google Tasks API PHP Quickstart');
define('CREDENTIALS_PATH', '~/.credentials/tasks-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
define('SCOPES', implode(' ', array(Google_Service_Tasks::TASKS)));


if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */

function getClient() {

    $client = new Google_Client();
    $client->addScope(Google_Service_Tasks::TASKS);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setScopes(SCOPES);
    $client->setApplicationName(APPLICATION_NAME);
    $client->setAccessType('offline');

    $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
    $client->setHttpClient($guzzleClient);

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);

    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        print 'Enter verification code: ';
        // Store the credentials to disk.
        if(!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Tasks($client);

// Print the first 10 task lists.
$optParams = array(
    'maxResults' => 10,
);
$results = $service->tasklists->listTasklists($optParams);

if (count($results->getItems()) == 0) {
    print "No task lists found.\n";
} else {
    print "Task lists:\n";
    foreach ($results->getItems() as $tasklist) {
        printf("%s (%s)\n", $tasklist->getTitle(), $tasklist->getId());
    }
}
