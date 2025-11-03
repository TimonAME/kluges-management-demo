<?php
use Workerman\Worker;
use Workerman\Connection\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

$ws_worker = new Worker("websocket://0.0.0.0:2346");
$ws_worker->count = 4;

// Store user connections
$userConnections = [];
// Store user-to-chat mappings
$userChats = [];

$ws_worker->onConnect = function(ConnectionInterface $connection) {
    // When a connection is established, we'll wait for authentication
    $connection->send(json_encode([
        'type' => 'authentication_required'
    ]));
};

$ws_worker->onMessage = function(ConnectionInterface $connection, $data) use (&$userConnections, &$userChats) {
    $message = json_decode($data, true);

    switch ($message['type']) {
        case 'authenticate':
            // Store the connection with user ID
            $userId = $message['userId'];
            $userConnections[$userId] = $connection;

            // Mark the connection with the user ID
            $connection->userId = $userId;

            $connection->send(json_encode([
                'type' => 'authentication_success'
            ]));
            break;

        case 'join_chat':
            $userId = $connection->userId;
            $chatId = $message['chatId'];

            // Track which chats the user is in
            $userChats[$userId][$chatId] = true;
            break;

        case 'chat_message':
            $chatId = $message['conversation'];
            $receiverId = $message['receiverId'];

            // Check if the receiver is connected and in the chat
            if (isset($userConnections[$receiverId]) &&
                isset($userChats[$receiverId][$chatId])) {

                $receiverConnection = $userConnections[$receiverId];
                $receiverConnection->send($data);
            }
            break;
    }
};

$ws_worker->onClose = function(ConnectionInterface $connection) use (&$userConnections, &$userChats) {
    // Remove the user's connection when they disconnect
    if (isset($connection->userId)) {
        unset($userConnections[$connection->userId]);
        unset($userChats[$connection->userId]);
    }
};

Worker::runAll();