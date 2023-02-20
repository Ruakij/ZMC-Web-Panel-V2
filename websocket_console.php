<?php
// Include the Autoloader
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class MyWebSocket implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "[{$conn->resourceId}] New connection!\n";

        // Authenticate the client
        if ($this->authenticateClient($conn)) {
            $this->clients->attach($conn);
            echo "[{$conn->resourceId}] Authenticated!\n";
            $conn->send("Authenticated!");

            // Open a process to run the shell command
            $conn->process = new React\ChildProcess\Process('docker logs -f --tail=100 mc');
            $conn->process->start();

            $conn->process->stdout->on('data', function ($chunk) use ($conn) {
                $conn->send($chunk);
            });

            $conn->process->on('exit', function ($exitCode, $termSignal) use ($conn) {
                $conn->send("process exited with {$exitCode}");
                $conn->close();
            });

        } else {
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $cmdRaw)
    {
        $cmdSanitized = escapeshellarg($cmdRaw);
        // Execute a command and pass the output to the client
        shell_exec("docker exec mc mc-send-to-console {$cmdSanitized}");
        $from->send("> {$cmdSanitized}");
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $conn->process->terminate();
        echo "[{$conn->resourceId}] Disconnected!\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Authenticate the client
    private function authenticateClient(ConnectionInterface $conn)
    {
        // Get the session ID from the cookie
        $cookies = $conn->httpRequest->getHeader('Cookie');
        $sessionId = null;
        if (!empty($cookies)) {
            $cookie = array_shift($cookies);
            if (preg_match('/PHPSESSID=([^;]+)/', $cookie, $matches)) {
                $sessionId = $matches[1];
            }
        }
        if (!$sessionId) {
            // No session ID found, so the client is not authenticated
            return false;
        }

        // Read the session data from the session file and parse it
        $sessionPath = session_save_path();
        $sessionFile = $sessionPath . '/sess_' . $sessionId;
        if (!file_exists($sessionFile)) {
            // The session file does not exist, so the session is not valid
            return false;
        }

        $sessionData = file_get_contents($sessionFile);
        $sessionVariables = explode("\n", $sessionData);

        // Deserialize each session variable and add it to the session variables array
        foreach ($sessionVariables as $sessionVariable) {
            // Split the session variable into the session name and value
            $parts = explode('|', $sessionVariable, 2);
            $sessionName = $parts[0];
            $sessionValue = $parts[1];

            // Deserialize the session value and add it to the session variables array
            $sessionVars[$sessionName] = unserialize($sessionValue);
        }

        if (!isset($sessionVars['UserData']['Username'])) {
            // The user is not logged in
            return false;
        }

        // Store the session data in the connection object
        $conn->sessionData = $sessionVars[$sessionName];
        return true;
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
