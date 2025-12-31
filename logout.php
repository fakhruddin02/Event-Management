<?php
require_once __DIR__ . '/config.php';

logoutUser();
redirect('login.php');
