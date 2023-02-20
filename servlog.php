<?php session_start(); /* Starts the session */

if (!isset($_SESSION['UserData']['Username'])) {
  header("location:login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300&display=swap" rel="stylesheet">
</head>

<body>
  <p style="color:white; font-family: 'Roboto Mono', monospace;" id="pger">
  </p>

  <script type="text/javascript">
    const output = document.getElementById('pger');
    function outputMsg(msg) {
      if (msg.substr(-4) !== "<br>") {
        msg += "<br>";
      }
      output.innerHTML += msg;

      window.scroll(0, 1000000000);
    }

    var socket;
    function connect() {
      outputMsg("# Connecting..");

      // Create WebSocket connection.
      const protocol = (location.protocol === 'https:' ? 'wss://' : "ws://");
      socket = new WebSocket(protocol + document.location.host + ":8080");

      // Connection opened
      socket.addEventListener('open', (event) => {
        outputMsg("# Connected!");
      });

      socket.addEventListener('close', (event) => {
        outputMsg("# Disconnected!");
        setTimeout(connect, 3000);
      });

      // Error
      socket.addEventListener('error', (error) => {
        outputMsg("# Failed! " + error);
        setTimeout(connect, 3000);
      });

      // Listen for messages
      socket.addEventListener('message', (event) => {
        outputMsg(event.data.replaceAll("\n", "<br>"));
      });
    }
    connect();

    function sendCommand(cmd) {
      socket.send(cmd);
    }

  </script>
</body>

</html>
