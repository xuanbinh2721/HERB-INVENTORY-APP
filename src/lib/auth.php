<?php
require_once __DIR__ . '/../db.php';

if(session_status() === PHP_SESSION_NONE){ session_start(); }

function current_user(){ return $_SESSION['user'] ?? null; }

function require_login(){
    if(!current_user()){
        redirect('/?page=login');
    }
}

function attempt_login($username, $password){
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if($u && password_verify($password, $u['password_hash'])){
        $_SESSION['user'] = ['id'=>$u['id'], 'username'=>$u['username']];
        return true;
    }
    return false;
}

function logout(){
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
