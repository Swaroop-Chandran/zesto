<?php
/**
 * Zesto — User Authentication & RBAC Service
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthService {

    /**
     * Authenticate user credentials and write session.
     */
    public static function authenticate(string $email, string $password): ?User {
        $user = User::getByEmail($email);
        if ($user && password_verify($password, $user->password)) {
            // Write session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id']      = $user->id;
            $_SESSION['user_name']    = $user->name;
            $_SESSION['user_email']   = $user->email;
            $_SESSION['user_role']    = $user->role;
            $_SESSION['logged_in_at'] = time();
            return $user;
        }
        return null;
    }

    /**
     * Terminate user session.
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Register a new user profile.
     */
    public static function register(string $name, string $email, string $password, string $role = 'customer'): ?User {
        $existing = User::getByEmail($email);
        if ($existing) {
            return null;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return User::create($name, $email, $hash, $role);
    }
}
