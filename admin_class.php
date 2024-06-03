<?php
include ("config.php");
include("firebaseRDB.php");
$rdb = new firebaseRDB($databaseURL);
session_start();


ini_set('display_errors', 1);
Class Action {
	private $db;

	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn;
	}
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}

	function login(){
		extract($_POST);
			$qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM users where email = '".$email."' and password = '".md5($password)."'  ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
				return 1;
		}else{
			return 2;
		}
	}
	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	function login2(){
		extract($_POST);
			$qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM students where student_code = '".$student_code."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['rs_'.$key] = $value;
			}
				return 1;
		}else{
			return 3;
		}
	}
	function save_user(){
		global $rdb; // Assuming $rdb is the instance of firebaseRDB
		
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(!empty($password)){
			$data .= ", password=md5('$password') ";
		}
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
			$id = $this->db->insert_id; // Get the last inserted ID from MySQL
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}
	
		if($save){
			// Insert data to Firebase using the same ID as MySQL
			$firebase_data = [
				"firstname" => $firstname,
				"lastname" => $lastname,
				"email" => $email,
				"password" => $password, // Assuming you want to store the password as well (not recommended for security reasons)
				"type" => $type,
				"id" => $id // Use the same ID as MySQL
			];
			$insert = $rdb->insert("/user", $firebase_data);
	
			return 1;
		}
	}
	
	
	function signup(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
				if($k =='password'){
					if(empty($v))
						continue;
					$v = md5($v);

				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}

		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");

		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			
			if(empty($id))
			
				$id = $this->db->insert_id;
			foreach ($_POST as $key => $value) {
				if(!in_array($key, array('id','cpass','password')) && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
					$_SESSION['login_id'] = $id;
				if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}

	function update_user(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','table','password')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(!empty($password))
			$data .= " ,password=md5('$password') ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			foreach ($_POST as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}
	function delete_user(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM users where id = ".$id);
		if($delete)
			return 1;
	}
	function save_system_settings(){
		extract($_POST);
		$data = '';
		foreach($_POST as $k => $v){
			if(!is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if($_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", cover_img = '$fname' ";

		}
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']);
		}else{
			$save = $this->db->query("INSERT INTO system_settings set $data");
		}
		if($save){
			foreach($_POST as $k => $v){
				if(!is_numeric($k)){
					$_SESSION['system'][$k] = $v;
				}
			}
			if($_FILES['cover']['tmp_name'] != ''){
				$_SESSION['system']['cover_img'] = $fname;
			}
			return 1;
		}
	}
	function save_image(){
		extract($_FILES['file']);
		if(!empty($tmp_name)){
			$fname = strtotime(date("Y-m-d H:i"))."_".(str_replace(" ","-",$name));
			$move = move_uploaded_file($tmp_name,'assets/uploads/'. $fname);
			$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
			$hostName = $_SERVER['HTTP_HOST'];
			$path =explode('/',$_SERVER['PHP_SELF']);
			$currentPath = '/'.$path[1]; 
			if($move){
				return $protocol.'://'.$hostName.$currentPath.'/assets/uploads/'.$fname;
			}
		}
	}
	function save_project() {
		global $rdb; // Access the $rdb instance from the global scope
		extract($_POST);
	
		// Define data array for MySQL insertion
		$mysql_data = "";
		foreach($_POST as $k => $v) {
			if(!in_array($k, array('id','user_ids')) && !is_numeric($k)) {
				if($k == 'description') {
					$v = htmlentities(str_replace("'", "&#x2019;", $v));
				}
				if(empty($mysql_data)) {
					$mysql_data .= " $k='$v' ";
				} else {
					$mysql_data .= ", $k='$v' ";
				}
			}
		}
	
		// Check if user_ids is set and add it to the MySQL data
		if(isset($user_ids)) {
			$mysql_data .= ", user_ids='".implode(',', $user_ids)."' ";
		}
	
		// Perform MySQL insertion
		if(empty($id)) {
			$save_mysql = $this->db->query("INSERT INTO project_list SET $mysql_data");
			// Get the last inserted ID from MySQL
			$project_id = $this->db->insert_id;
		} else {
			$save_mysql = $this->db->query("UPDATE project_list SET $mysql_data WHERE id = $id");
			// Set project_id as the same value as the MySQL ID
			$project_id = $id;
		}
	
		// Retrieve manager_id (assuming it's the same as the login_id)
		$manager_id = $_SESSION['login_id']; // Assuming the login_id is stored in the session
	
		// Define data array to store in Firebase
		$firebase_data = [
			"project_id" => $project_id, // Set project_id as the value for Firebase
			"name" => $name,
			"description" => htmlentities(str_replace("'", "&#x2019;", $description)),
			"manager_id" => $manager_id,
			"start_date" => $start_date,
			"end_date" => $end_date,
			"status" => $status
			// Add more fields as needed
		];
	
		// Check if user_ids is set and add it to the Firebase data
		if(isset($user_ids)) {
			$firebase_data["user_ids"] = implode(',', $user_ids);
		}
	
		// Define the path in the Firebase database where you want to store the project data
		$firebase_path = "/projects";
	
		// Insert the project data into the Firebase database
		$insert_result = $rdb->insert($firebase_path, $firebase_data);
	
		// Check if both MySQL and Firebase insertions were successful
		if($save_mysql && $insert_result) {
			return 1; // Return 1 if successful
		} else {
			return 0; // Return 0 if unsuccessful
		}
	}
	
	function delete_project(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM project_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_task(){
		global $rdb; // Access the $rdb instance from the global scope
		extract($_POST);
	
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id', 'project_id')) && !is_numeric($k)){
				if($k == 'description')
					$v = htmlentities(str_replace("'", "&#x2019;", $v)); // Sanitize description field
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
	
		// Insert or update task in MySQL database
		if(empty($id)){
			$save = $this->db->query("INSERT INTO task_list SET $data, project_id='$project_id'");
		}else{
			$save = $this->db->query("UPDATE task_list SET $data WHERE id = $id");
		}
	
		// If MySQL operation is successful
		if($save){
			// Define task data to be stored in Firebase
			$firebase_data = [
				"project_id" => $project_id,
				"title" => $task, 
				"description" => $description,
				"status" => $status,
			];
	
			// Define the path in the Firebase database where you want to store the task data
			$firebase_path = "/tasklist"; // Modify this path according to your Firebase structure
	
			// Insert the task data into the Firebase database under the project ID node
			$insert_result = $rdb->insert($firebase_path, $firebase_data);
	
			// Check if Firebase insertion was successful
			if($insert_result) {
				return 1; // Return 1 if both MySQL and Firebase operations were successful
			} else {
				// Rollback MySQL operation if Firebase insertion fails
				if(empty($id)){
					// Delete the inserted task if it was new
					$this->db->query("DELETE FROM task_list WHERE id = LAST_INSERT_ID()");
				}
				return 0; // Return 0 if Firebase operation fails
			}
		} else {
			return 0; // Return 0 if MySQL operation fails
		}
	}
	
	
	
	
	
	
	function delete_task(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM task_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_progress(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if($k == 'comment')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$dur = abs(strtotime("2020-01-01 ".$end_time)) - abs(strtotime("2020-01-01 ".$start_time));
		$dur = $dur / (60 * 60);
		$data .= ", time_rendered='$dur' ";
		// echo "INSERT INTO user_productivity set $data"; exit;
		if(empty($id)){
			$data .= ", user_id={$_SESSION['login_id']} ";
			
			$save = $this->db->query("INSERT INTO user_productivity set $data");
		}else{
			$save = $this->db->query("UPDATE user_productivity set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_progress(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM user_productivity where id = $id");
		if($delete){
			return 1;
		}
	}
	function get_report(){
		extract($_POST);
		$data = array();
		$get = $this->db->query("SELECT t.*,p.name as ticket_for FROM ticket_list t inner join pricing p on p.id = t.pricing_id where date(t.date_created) between '$date_from' and '$date_to' order by unix_timestamp(t.date_created) desc ");
		while($row= $get->fetch_assoc()){
			$row['date_created'] = date("M d, Y",strtotime($row['date_created']));
			$row['name'] = ucwords($row['name']);
			$row['adult_price'] = number_format($row['adult_price'],2);
			$row['child_price'] = number_format($row['child_price'],2);
			$row['amount'] = number_format($row['amount'],2);
			$data[]=$row;
		}
		return json_encode($data);

	}
}