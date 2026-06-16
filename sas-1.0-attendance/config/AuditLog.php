<?php
/**
 * Audit Logger - Logs all admin actions for security and compliance
 */

class AuditLog {
    private $conn;
    private $user_id;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Log an action
     */
    public function log($action, $details = '', $status = 'success') {
        $stmt = $this->conn->prepare("
            INSERT INTO audit_logs (user_id, action, details, status, ip_address, user_agent, timestamp)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            error_log("Audit Log Error: " . $this->conn->error);
            return false;
        }
        
        $ip = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $user_agent = substr($user_agent, 0, 255); // Limit length
        
        $stmt->bind_param(
            'isssss',
            $this->user_id,
            $action,
            $details,
            $status,
            $ip,
            $user_agent
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Get audit logs
     */
    public function getLogs($limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT al.*, u.full_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.timestamp DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
