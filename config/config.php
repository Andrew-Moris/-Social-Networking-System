<?php
$host = 'localhost';
$port = '5432';
$dbname = 'socialmedia';
$user = 'postgres';
$password = '20043110';

define('DB_HOST', $host);
define('DB_PORT', $port);
define('DB_NAME', $dbname);
define('DB_USER', $user);
define('DB_PASS', $password);

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $user, $password, $pdo_options);
    
    class PostgreSQLCompat {
        private $pdo;
        public $insert_id = null;
        public $connect_error = null;
        
        public function __construct($pdo) {
            $this->pdo = $pdo;
        }
        
        public function prepare($query) {
            return new PostgreSQLStmt($this->pdo->prepare($query));
        }
        
        public function query($query) {
            try {
                $stmt = $this->pdo->query($query);
                return new PostgreSQLResult($stmt);
            } catch (PDOException $e) {
                return false;
            }
        }
        
        public function real_escape_string($string) {
            return $string; 
        }
        
        public function set_charset($charset) {
            return true;
        }
    }
    
    class PostgreSQLStmt {
        private $stmt;
        
        public function __construct($stmt) {
            $this->stmt = $stmt;
        }
        
        public function bind_param($types, ...$params) {
            for ($i = 0; $i < count($params); $i++) {
                $this->stmt->bindValue($i + 1, $params[$i]);
            }
            return true;
        }
        
        public function execute() {
            return $this->stmt->execute();
        }
        
        public function get_result() {
            $this->stmt->execute();
            return new PostgreSQLResult($this->stmt);
        }
    }
    
    class PostgreSQLResult {
        private $stmt;
        private $rows;
        private $position = 0;
        
        public function __construct($stmt) {
            $this->stmt = $stmt;
            $this->rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        public function fetch_assoc() {
            if (isset($this->rows[$this->position])) {
                return $this->rows[$this->position++];
            }
            return false;
        }
        
        public function fetch_array() {
            if (isset($this->rows[$this->position])) {
                $row = $this->rows[$this->position++];
                $result = array_values($row) + $row; 
            }
            return false;
        }
        
        public function num_rows() {
            return count($this->rows);
        }
    }
    
    $conn = new PostgreSQLCompat($pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

date_default_timezone_set('UTC');

define('BASE_URL', 'http://localhost/WEP');
define('UPLOAD_PATH', __DIR__ . '/../uploads');

define('JWT_SECRET_KEY', 'WEP_2024_Secure_JWT_Key_Change_This_In_Production');

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    if (!class_exists('Firebase\JWT\JWT')) {
        class_alias('stdClass', 'Firebase\JWT\JWT');
    }
    if (!class_exists('Firebase\JWT\Key')) {
        class_alias('stdClass', 'Firebase\JWT\Key');
    }
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('jsonError')) {
    function jsonError($message, $status = 400) {
        jsonResponse(['error' => $message], $status);
    }
}

if (!function_exists('generateJWT')) {
    function generateJWT($user_id) {
        $payload = [
            'user_id' => $user_id,
            'iat' => time(),
            'exp' => time() + (30 * 24 * 60 * 60) 
        ];
        return JWT::encode($payload, JWT_SECRET_KEY, 'HS256');
    }
}

if (!function_exists('authenticateRequest')) {
    function authenticateRequest() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            jsonError('No authorization token provided', 401);
        }
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
            return $decoded->user_id;
        } catch (Exception $e) {
            jsonError('Invalid or expired token', 401);
        }
    }
}

if (file_exists(__DIR__ . '/auth_middleware.php')) {
    require_once __DIR__ . '/auth_middleware.php';
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['username'], $data['email'], $data['password'])) {
            jsonError('Missing required fields');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            jsonError('Invalid email format');
        }
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $data['username'], $data['email']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows() > 0) {
            jsonError('Username or email already exists');
        }
        $username = $data['username'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $username, $email, $password);
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            $token = generateJWT($user_id);
            jsonResponse([
                'message' => 'User registered successfully',
                'user_id' => $user_id,
                'token' => $token
            ]);
        } else {
            jsonError('Registration failed');
        }
        break;

    case 'GET':
        $user_id = authenticateRequest();
        $query = "SELECT id, username, email FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            jsonResponse($user);
        } else {
            jsonError('User not found', 404);
        }
        break;

    default:
        jsonError('Method not allowed', 405);
        break;
}
?>