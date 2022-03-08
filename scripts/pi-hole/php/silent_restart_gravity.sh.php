<?php

require "password.php";
if(!$auth) die("Not authorized");

$proc = popen("sudo pihole -g", 'r');
while (!feof($proc)) {
    fread($proc, 4096);
}

sleep(7);
