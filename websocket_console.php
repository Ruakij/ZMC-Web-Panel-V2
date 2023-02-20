<?php
// Include the Autoloader
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class MyWebSocket implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Authenticate the client
        if ($this->authenticateClient($conn)) {
            $this->clients->attach($conn);
            echo "New connection! ({$conn->resourceId})\n";
        } else {
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Execute a command and pass the output to the client
        $output = shell_exec($msg);
        $from->send($output);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Authenticate the client
    private function authenticateClient(ConnectionInterface $conn) {
        // Get the session ID from the cookie
        $cookies = $conn->httpRequest->getHeader('Cookie');
        $sessionId = null;

        if (!empty($cookies)) {
            $cookie = array_shift($cookies);
            if (preg_match('/PHPSESSID=([^;]+)/', $cookie, $matches)) {
                $sessionId = $matches[1];
            }
        }

        // Start the session
        session_id($sessionId);
        session_start();

        // Check if the user is authenticated
        return !empty($_SESSION['user_id']);
    }
}

// Run the WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new MyWebSocket()
        )
    ),
    8080,
    '[::]'
);

$server->run();
