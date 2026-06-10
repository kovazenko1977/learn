<?php
require_once __DIR__ . '/src/autoload.php';
use App\Helpers\Auth;
Auth::logout();
header('Location: login.php');
exit;
