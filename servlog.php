<?php session_start(); /* Starts the session */

if(!isset($_SESSION['UserData']['Username'])){
        header("location:login.php");
        exit;
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300&display=swap" rel="stylesheet">
  <p style="color:white;"><script type="text/javascript" src="autoUpdate.js"></script> <script type="text/javascript">
      function moveWin() {
        window.scroll(0,1000000000); setTimeout('moveWin();',0);

      }
      
      </script>
      <body onLoad="moveWin();">
<script>
  
function refresh(){
  let xhr = new XMLHttpRequest();

  xhr.open('GET', 'log.php');

  xhr.responseType = 'text';

  let timeStart = new Date();

  xhr.send();
  xhr.onload = function() {
    let timeDiff = new Date() - timeStart;

    let responseObj = xhr.responseText;
    document.getElementById("pger").innerHTML = responseObj;

    let nexTimeout = Math.max(timeDiff, 10*1000)
    setTimeout(refresh, nexTimeout);
  };
};
refresh();
</script>
<?php // echo str_replace("\n","<br>", shell_exec("docker logs "."mc")); ?>
</p>

<p style="color:white; font-family: 'Roboto Mono', monospace;" id="pger">
Loading...
</p>
