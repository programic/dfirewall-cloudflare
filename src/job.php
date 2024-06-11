<?php

require __DIR__ . '/vendor/autoload.php';

$old = json_decode(file_get_contents('current_ip.json'));

$cfAuthHeaders = [
    'X-Auth-Email' => getenv('AUTH_EMAIL'),
    'X-Auth-Key' => getenv('AUTH_KEY'),
];

$cfAccountId = getenv('ACCOUNT_ID');
$cfListId = getenv('LIST_ID');

$client = new GuzzleHttp\Client();

# Check current IP
$response = $client->get('checkip.amazonaws.com');
$currentIp = preg_replace('/\s+/', '', $response->getBody());

echo "Current IP is {$currentIp}." . PHP_EOL;

# First run, or when IP address is changed
if ($old->ip != $currentIp) {

    echo "This is the fist run or the IP is changed." . PHP_EOL;

    # If there is an old IP, delete it first
    if ($old->id) {
        try {
            $client->delete("https://api.cloudflare.com/client/v4/accounts/{$cfAccountId}/rules/lists/{$cfListId}/items", [
                'headers' => $cfAuthHeaders + [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'items' => [
                        ['id' => $old->id],
                    ],
                ],
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            echo $e->getMessage();
        }

        echo "Deleted old IP {$old->ip} with ID {$old->id}." . PHP_EOL;
    }

    # Create new IP item
    $client->post("https://api.cloudflare.com/client/v4/accounts/{$cfAccountId}/rules/lists/{$cfListId}/items", [
        'headers' => $cfAuthHeaders + [
            'Content-Type' => 'application/json',
        ],
        'json' => [
            ['ip' => $currentIp],
        ],
    ]);

    echo "Created new IP item in Cloudflare." . PHP_EOL;

    # Get ID of newly created IP. Since this is an asynchronous action within Cloudflare, it may not be processed yet. So try max 10 times.
    $currentId = null;

    for ($i = 10; $i > 0; $i--) {
          
        $response = $client->get("https://api.cloudflare.com/client/v4/accounts/{$cfAccountId}/rules/lists/{$cfListId}/items", [
            'headers' => $cfAuthHeaders,
        ]);
        
        $body = json_decode($response->getBody());
        
        foreach ($body->result as $item) {
            if ($item->ip == $currentIp) {
                $currentId = $item->id;
                break;
            }
        }

        if ($currentId) {
            echo "ID {$currentId} found of newly created IP." . PHP_EOL;
            break;
        }

        echo "ID of new IP not yet found, try again in 3 seconds." . PHP_EOL;

        sleep(3);
    }

    if (!$currentId) {
        throw new Exception('After 10 tries, unable to get the ID of the newly created IP.');
    }

    # Save to file
    file_put_contents('current_ip.json', json_encode([
        'id' => $currentId,
        'ip' => $currentIp,
    ]));

    echo "Saved new IP {$currentIp} with ID {$currentId}" . PHP_EOL;

} else {
    echo "Nothing is changed, so do nothing." . PHP_EOL;
}
