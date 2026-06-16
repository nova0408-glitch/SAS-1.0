<?php
/**
 * Rate Limiter - Prevents brute-force attacks
 */

class RateLimiter {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Check if an IP has exceeded rate limit for login attempts
     */
    public function isLimitExceeded($ip, $action = 'login', $max_attempts = 5, $window = 900) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as attempt_count
            FROM rate_limits
            WHERE ip_address = ? AND action = ? AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        if (!$stmt) {
            return false; // Fail open
        }
        
        $stmt->bind_param('ssi', $ip, $action, $window);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['attempt_count'] >= $max_attempts;
    }
    
    /**
     * Record an attempt
     */
    public function recordAttempt($ip, $action = 'login', $status = 'failed') {
        $stmt = $this->conn->prepare("
            INSERT INTO rate_limits (ip_address, action, status, timestamp)
            VALUES (?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('sss', $ip, $action, $status);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Clear old rate limit records
     */
    public function cleanup() {
        $this->conn->query("
            DELETE FROM rate_limits
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
    }
}
?>
