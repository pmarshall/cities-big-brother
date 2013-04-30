<?php
header("Cache-Control: no-cache, no-store"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

header('Content-disposition: attchment; filename=citiesbigbrother.user.js');
header('Content-type: application/x-javascript');
readfile('citiesbigbrother.user.js');
?>