const { WebSocketServer } = require('ws');

const PHP_SESSION_DIR = "/var/lib/php/sessions";


const server = new WebSocketServer({ port: 8080 });
console.log("Listening on port 8080..")

server.on('connection', (conn, request) => {
    log(conn, "New Connection!");

    // Authenticate the client
    conn.sessionData = authenticateClient(request);
    if (!conn.sessionData) {
        conn.send("Unauthenticated");
        conn.close();
        return;
    }

    log(conn, "Authenticated!");

    // Open a process to run the shell command
    conn.process = exec('docker logs -f --tail=100 mc');
    conn.process.stdout.on('data', (chunk) => {
        const chunkSanitized = chunk.toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        conn.send(chunkSanitized);
    });
    conn.process.on('exit', (exitCode, termSignal) => {
        conn.send(`process exited with ${exitCode}`);
        conn.close();
    });

    // Attach handlers
    conn.on('message', (data) => {
        const cmdSanitized = data.replace(/'/g, '\\\'');
        // Execute a command and pass the output to the client
        exec(`docker exec mc mc-send-to-console '${cmdSanitized}'`);
        from.send(`> ${cmdSanitized}`);
    })

    conn.on('close', () => {
        conn.process.kill();
        log(conn, "Disconnected!");
    })

    conn.on('error', (err) => {
        log(conn, "An error has occurred:", e.message);
        conn.close();
    });
});

// Authenticate the client
function authenticateClient(request) {
    // Get the session ID from the cookie
    const cookies = request.headers.cookie;
    let sessionId = null;
    if (cookies) {
        const match = cookies.match(/PHPSESSID=([^;]+)/);
        sessionId = match && match[1];
    }
    if (!sessionId) {
        // No session ID found, so the client is not authenticated
        return false;
    }

    // Read the session data from the session file and parse it
    const sessionPath = `${PHP_SESSION_DIR}/sess_${sessionId}`;
    if (!require('fs').existsSync(sessionPath)) {
        // The session file does not exist, so the session is not valid
        return false;
    }

    const sessionData = require('fs').readFileSync(sessionPath, 'utf-8');
    const sessionVariables = sessionData.split('\n');

    // Deserialize each session variable and add it to the session variables object
    const sessionVars = {};
    for (const sessionVariable of sessionVariables) {

        console.log("sessionVariable: " + sessionVariable)

        // Split the session variable into the session name and value
        const parts = sessionVariable.split('|');
        const sessionName = parts[0];
        const sessionValue = parts[1];

        console.log(sessionValue);

        // Deserialize the session value and add it to the session variables object
        sessionVars[sessionName] = JSON.parse(sessionValue);

        console.log(sessionVars[sessionName]);
    }

    if (!(sessionVars.UserData?.Username)) {
        // The user is not logged in
        return false;
    }

    return sessionVars[sessionName];
}

function log(conn, ...msg) {
    console.log(`[${conn._socket.remoteAddress}:${conn._socket.remotePort}] ${msg.join(' ')}`);
}
