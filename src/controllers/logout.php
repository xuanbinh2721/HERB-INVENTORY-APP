<?php
require_once __DIR__ . '/../lib/auth.php';
logout();
redirect('/?page=login');
