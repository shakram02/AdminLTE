<?php

require "password.php";
if (!$auth) die("Not authorized");

exec("sudo pihole -g");
