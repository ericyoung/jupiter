<?php
// Development email storage system
// In a real application, you would use a database table
// For development, we'll use a simple JSON file

class DevEmailTracker {
    private $emailFile;
    
    public function __construct() {
        $this->emailFile = __DIR__ . '/dev_emails.json';
        $this->ensureEmailFileExists();
    }
    
    private function ensureEmailFileExists() {
        if (!file_exists($this->emailFile)) {
            file_put_contents($this->emailFile, json_encode([]));
        }
    }
    
    public function logEmail($to, $subject, $body, $additionalData = []) {
        $emailEntry = [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'additional_data' => $additionalData
        ];
        
        $emails = $this->getEmails();
        $emails[] = $emailEntry;
        
        // Keep only the last 100 emails to prevent the file from growing too large
        if (count($emails) > 100) {
            $emails = array_slice($emails, -100);
        }
        
        file_put_contents($this->emailFile, json_encode($emails, JSON_PRETTY_PRINT));
        
        return $emailEntry['id'];
    }
    
    public function getEmails($limit = 50) {
        $allEmails = $this->getAllEmails();
        return array_slice($allEmails, -$limit);
    }
    
    public function getEmailById($id) {
        $allEmails = $this->getAllEmails();
        foreach ($allEmails as $email) {
            if ($email['id'] === $id) {
                return $email;
            }
        }
        return null;
    }
    
    public function clearAllEmails() {
        file_put_contents($this->emailFile, json_encode([]));
    }
    
    private function getAllEmails() {
        if (!file_exists($this->emailFile)) {
            return [];
        }
        
        $content = file_get_contents($this->emailFile);
        $emails = json_decode($content, true);
        
        return is_array($emails) ? $emails : [];
    }
    
    public function getLastEmailByType($type) {
        $emails = $this->getEmails();
        // Look for the most recent email with a specific type in additional_data
        for ($i = count($emails) - 1; $i >= 0; $i--) {
            if (isset($emails[$i]['additional_data']['type']) && $emails[$i]['additional_data']['type'] === $type) {
                return $emails[$i];
            }
        }
        return null;
    }
}

// Create a global instance
if (!isset($emailTracker)) {
    $emailTracker = new DevEmailTracker();
}