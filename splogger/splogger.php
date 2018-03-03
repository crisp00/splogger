<?php
class Splogger{

    private $instance_id = "splog_";
    private $config;
    private $db;
    public $encrypt_function;
    /*
     *  It's required that any Splogger object be instanciated before sending the body 
     *
     */
    function __construct(SploggerConfig $config, $instance_id){
        $this->encrypt_function = function($password){
            $salt = "aaoisgsgsufgb807adbga8sdng79s80ag9d7fav";
            return crypt($password, $salt);
        };
        
        $this->instance_id = $instance_id;
        $this->config = $config;
        session_start();
        $this->db = new mysqli(
            $config->db_host,
            $config->db_user,
            $config->db_pass,
            $config->db_database);
        if($this->db->connect_errno){
            throw new ErrorException("Splogger: Database Connection Error <br>" . $mysqli->connect_errno . " " . $mysqli->connect_error);
        }
    }

    public function login($user, $pass){
        $requested_user = $this->getUser($user);
        $encrypted_pass = $this->encrypt_pass($pass);
        if($encrypted_pass == $requested_user->getEncryptedPassword()){
            $_SESSION[$this->instance_id . "logged_in"] = 1;
            $_SESSION[$this->instance_id . "user"] = $requested_user;
        }
	return $requested_user; 
    }

    public function logout(){
        if($this->isLoggedIn()){
            $_SESSION[$this->instance_id . "logged_in"] = 0;
        }
    }

    public function register($user, $pass, $group = 1){
        $encrypted_pass = $this->encrypt_pass($pass);
        $query = "INSERT INTO ".$this->config->db_prefix."_users (username, password, registration_time, last_access_time, ID_group) 
        VALUES (\"".$user."\", \"".$encrypted_pass."\", NOW(), NOW(), 1);";
        $result = $this->db->query($query);
        if(!$result){
            throw new ErrorException("Splogger Registration Error: " . $this->db->error);
        }
    }

    public function isLoggedIn(){
	$loggedin = isset($_SESSION[$this->instance_id . "logged_in"]) && ($_SESSION[$this->instance_id . "logged_in"] == 1);
        if(!$loggedin)
	    return false;
	$user = $_SESSION[$this->instance_id . "user"];
	return $user;
    }

    public function createGroup($display_name){
        $this->db->query("INSERT INTO ". $this->config->db_prefix ."_groups (display_name) VALUES ('".$display_name."')");
        if($this->db->error){
            throw new ErrorException("Splogger: Error Creating Group " . $this->db->error);
        }
    }

	

    public function getUser($username){
        $res = $this->db->query("
        SELECT * FROM ".$this->config->db_prefix."_users
        INNER JOIN ". $this->config->db_prefix ."_groups
        ON ". $this->config->db_prefix ."_groups.ID_group = ". $this->config->db_prefix ."_users.ID_group 
        WHERE username = \"" . $username . "\";");
        if(!$res)
            throw new ErrorException("Splogger Database Error: " . $this->db->error);
        return SploggerUser::fromDBRow($res->fetch_assoc());
    }

    private function encrypt_pass($pass){
        $encrypt = $this->encrypt_function;
        return $encrypt($pass);
    }


}

class SploggerUser{

    public $user;

    function __construct($user){
        $this->user = $user;
    }

    public static function fromDBRow($db_result)
    {
        $user = (object) [];
        $user->id = $db_result["ID_user"];
        $user->username = $db_result["username"];
        $user->password_hash = $db_result["password"];
        $user->registration_time = $db_result["registration_time"];
        $user->last_access_time = $db_result["last_access_time"];
        $user->group_id = $db_result["ID_group"];
        $user->group_name = $db_result["display_name"];
        return new SploggerUser($user);
    }


    public function getEncryptedPassword(){
        return $this->user->password_hash;
    }

    public function toString(){
        return json_encode($this->user);
    }

}

class SploggerConfig{
    public $db_host;
    public $db_user;
    public $db_pass;
    public $db_database;
    public $db_prefix;
    public $pass_salt;
    
    function __construct(){
        
    }
}

class SploggerDatabaseInitializer{
    private $config;
    private $db;

    function __construct(SploggerConfig $config){
        $this->config = $config;
        $this->db = new mysqli(
            $config->db_host,
            $config->db_user,
            $config->db_pass);
        if($this->db->connect_errno){
            throw new ErrorException("Splogger: Database Connection Error <br>" . $mysqli->connect_errno . " " . $mysqli->connect_error);
        }
    }

    function init(){
        $this->db->query("CREATE DATABASE IF NOT EXISTS " . $this->config->db_database);
        $this->db->select_db($this->config->db_database);
        
        $groups_table_query = "CREATE TABLE `".$this->config->db_prefix."_groups` (
            `ID_group` int(10) NOT NULL AUTO_INCREMENT,
            `display_name` varchar(40) NOT NULL UNIQUE,
            PRIMARY KEY (`ID_group`));";
        $this->db->query($groups_table_query);
        if($this->db->error){
            throw new ErrorException("Splogger: Failed to create groups table in database: " . $this->db->error);
        }
        $users_table_query = "CREATE TABLE `". $this->config->db_prefix ."_users` (
            `ID_user` int(10) NOT NULL AUTO_INCREMENT,
            `username` varchar(20) NOT NULL UNIQUE,
            `password` varchar(1024) NOT NULL,
            `registration_time` datetime NOT NULL,
            `last_access_time` datetime NOT NULL,
            `ID_group` int(10) NOT NULL,
            PRIMARY KEY (`ID_user`),
            FOREIGN KEY (`ID_group`) REFERENCES `". $this->config->db_prefix ."_groups` (`ID_group`));";
        $this->db->query($users_table_query);
        if($this->db->error){
            throw new ErrorException("Splogger: Failed to create users table in database: " . $this->db->error);
        }
    }
}
?>
