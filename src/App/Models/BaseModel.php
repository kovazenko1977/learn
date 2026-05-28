<?php
namespace App\Models;
use App\Database\Database;
use PDO;
class BaseModel {
    protected PDO $db;
    public function __construct() {
        $this->db = (new Database())->getConnection();
    }
}
