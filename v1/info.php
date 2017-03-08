<?php
$localIP = getHostByName(getHostName());
echo $localIP;
echo $_SERVER['REMOTE_ADDR'];
phpinfo();
?>