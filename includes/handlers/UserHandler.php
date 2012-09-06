<?php
if ( !class_exists('BaseHandler') ) {
    include ABS_PATH . 'includes/handlers/BaseHandler.php';
}

class UserHandler extends BaseHandler {
    private $user = NULL;
    
    function __construct() {
        parent::__construct();
        
        if ( isset($_SESSION['user']) ) {
            $this->user = $_SESSION['user'];
        }
    }
    
    public function register($username, $password, $email, $first_name, $last_name, $timezone_id) {
        include ABS_PATH . 'libs/PasswordLib/PasswordLib.php';
        $password_hasher = new PasswordLib\PasswordLib;
        
        $this->db->exec('
        INSERT INTO users
        SET
            email = ' . $this->db->quote(trim($username)) . ',
            password = ' . $this->db->quote($password_hasher->createPasswordHash(trim($password))) . ',
            email = ' . $this->db->quote(trim($email)) . ',
            first_name = ' . $this->db->quote(trim($first_name)) . ',
            last_name = ' . $this->db->quote(trim($last_name)) . ',
            registered = ' . $_SERVER['REQUEST_TIME'] . '
        ');
        
        return $this->db->lastInsertId();
    }
    
    public function edit($user_id, array $data) {
        $first_name = trim($data['first_name']);
        $last_name = trim($data['last_name']);
        
        $this->db->exec('
        UPDATE users
        SET
            first_name = ' . $this->db->quote(trim($first_name)) . ',
            last_name = ' . $this->db->quote(trim($last_name)) . '
        WHERE id = ' . $this->db->quote($user_id)
        );
        
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['last_name'] = $last_name;
        
        $this->user = $_SESSION['user'];
    }
    
    public function login($username, $password) {
        $username = trim($username);
        
        $result_user = $this->db->query('
        SELECT id, username, password, email, first_name, last_name, admin
        FROM users
        WHERE username = ' . $this->db->quote($username) . '
        LIMIT 1
        ');
        
        if ( !($user = $result_user->fetch(PDO::FETCH_ASSOC)) ) {
            return false; // No user with that email
        }
        
        include ABS_PATH . 'libs/PasswordLib/PasswordLib.php';
        $password_hasher = new PasswordLib\PasswordLib;
        
        if ( !$password_hasher->verifyPasswordHash($password, $user['password']) ) {
            return false; // Wrong password
        }
        
        $this->db->exec('
        UPDATE users
        SET
            last_login = ' . $_SERVER['REQUEST_TIME'] . ',
            num_logins = num_logins + 1
        ');
        
        $_SESSION['user'] = array(
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'admin' => ($user['admin'] === 'yes')
        );
        
        return true;
    }
    
    public function logout() {
        unset($_SESSION['user']);
    }
    
    public function isLoggedIn() {
        return $this->user ? $this->user : false;
    }
    
    public function isAdmin() {
        return $this->user && $this->user['admin'];
    }
    
    public function getAll() {
        return $this->db->query('
        SELECT *
        FROM users
        ');
    }
    
    public function getFirstName($user_id) {
        return $this->db->query('
        SELECT first_name
        FROM users
        WHERE id = ' . $this->db->quote($user_id) . '
        LIMIT 1
        ')->fetchColumn();
    }
    
    public function getLastName($user_id) {
        return $this->db->query('
        SELECT last_name
        FROM users
        WHERE id = ' . $this->db->quote($user_id) . '
        LIMIT 1
        ')->fetchColumn();
    }
}
?>