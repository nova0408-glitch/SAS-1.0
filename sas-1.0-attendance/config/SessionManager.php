<?php
/**
 * Session Manager - Handles session timeout and security
 */

class SessionManager {
    private static $timeout = 1800; // 30 minutes in seconds
    private static $session_key = 'last_activity';
    
    public static function init() {
        session_start();
        self::checkTimeout();
    }
    
    public static function checkTimeout() {
        if (isset($_SESSION[self::$session_key])) {
            $elapsed = time() - $_SESSION[self::$session_key];
            
            if ($elapsed > self::$timeout) {
                // Session expired - destroy it
                session_destroy();
                header('Location: ../frontend/index.php?expired=1');
                exit();
            }
        }
        
        // Update last activity time
        $_SESSION[self::$session_key] = time();
    }
    
    public static function regenerateSession() {
        session_regenerate_id(true);
    }
    
    public static function logout() {
        $_SESSION = [];
        session_destroy();
    }
    
    public static function getSessionDuration() {
        return self::$timeout;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}
?>
