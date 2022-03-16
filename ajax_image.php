<?php
	require "config.php";
	$valid_formats = array("jpg", "png", "gif", "bmp","jpeg");
	$files_formats = array("xls", "xlsx", "csv");
	// print_r($_POST);
	$post=serialize($_POST);
	// file_put_contents('ajax_image.txt',"$post\n", FILE_APPEND | LOCK_EX);
	$path='uploads/';
	$query_avoid=array('INSERT','DELETE','FILE','CREATE','ALTER','INDEX','DROP','CREATETEMPORARYTABLES','SHOWVIEW','CREATEROUTINE','ALTERROUTINE','EXECUTE','CREATEVIEW','EVENT','TRIGGER','GRANT','SUPER','PROCESS','RELOAD','SHUTDOWN','SHOWDATABASES','LOCKTABLES','REFERENCES','REPLICATIONCLIENT','REPLICATIONSLAVE','CREATEUSER');
	if(isset($_POST['path']))$path=$_POST['path'];
	if(!file_exists($path))mkdir($path, 0777, true);
	$max=3;
	if(isset($_POST['maxsize']))$max=$_POST['maxsize'];
	if(!isset($_POST['title']))$_POST['title']='';
	if(isset($_FILES)){
		$name = $_FILES['file']['name'];
		$size = $_FILES['file']['size'];
		if(strlen($name)>0){
			$ext = strtolower(getExtension($name));
			if(in_array($ext,$valid_formats)){
				if($size<($max*1024*1024)){
					if(isset($_POST['name']) AND $_POST['name']<>'' AND $_POST['name']<>'undefined')$actual_image_name = $_POST['name'].".".$ext;
					else $actual_image_name = time().".".$ext;
					$tmp = $_FILES['file']['tmp_name'];
					if(move_uploaded_file($tmp, $path.$actual_image_name)){
						if(isset($_POST['query'])){
							$query=str_replace('[image]',$actual_image_name,$_POST['query']);
							if(0==count(array_intersect(array_map('strtoupper', explode(' ',$_POST['query'])),$query_avoid))){
								if(strpos($query, 'UPDATE settings SET description=')!==false){
									if($_POST['title']<>'')$_SESSION['soft']['settings'][$_POST['title']]=$actual_image_name;
									mysqli_query($con,"SELECT * FROM settings WHERE title='logo' AND branch='$_SESSION[branch]'") or die(mysqli_error($con));
									if(mysqli_affected_rows($con)==0)mysqli_query($con,"INSERT INTO settings(title,branch,description) VALUES('logo','$_SESSION[branch]','$actual_image_name')") or die(mysqli_error($con));
									else mysqli_query($con,$query) or file_put_contents('ajax_image.txt',mysqli_error($con)."\n", FILE_APPEND | LOCK_EX);
								}
								else mysqli_query($con,$query) or file_put_contents('ajax_image.txt',mysqli_error($con)."\n", FILE_APPEND | LOCK_EX);
							}
						}
						$return=array();
						$return['file']=$actual_image_name;
						if(isset($_POST['id']))$return['id']=$_POST['id'];
						echo json_encode($return);
					}
					else echo "Fail upload folder with read access.";
				}
				else echo "Image file size max $max MB";					
			}
			else if(in_array($ext,$files_formats)){
				if($size<($max*1024*1024)){
					$date=date('Y-m-d',time());
					$actual_name = time().((isset($_POST['name']) AND $_POST['name']<>'')?'_'.$_POST['name']:'').".".$ext;
					$tmp = $_FILES['file']['tmp_name'];
					if(move_uploaded_file($tmp, $path.$actual_name)){
						error_reporting(E_ALL);
						set_time_limit(3600);
						ini_set('display_errors', TRUE);
						ini_set('display_startup_errors', TRUE);
						define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
						include 'excel_reader.php';
						$excel = new PhpExcelReader;
						$excel->setOutputEncoding('UTF-8');
						$excel->read($path.$actual_name);
						$nr_sheets = count($excel->sheets);
						$count_row=0;
						for($i=0; $i<$nr_sheets; $i++){
							$sheet=$excel->sheets[$i];
							$x = 1;
							while($x <= $sheet['numRows']){
								$y = 1;
								$rows=array();
								while($y <= $sheet['numCols']){
									$cell = isset($sheet['cells'][$x][$y]) ? $sheet['cells'][$x][$y] : '';
									$rows[make_alpha_from_numbers($y-1)]=$cell;
									$y++;
								}//print_r($rows);
								if($_POST['name']=='attendance' AND $rows['B']<>'' AND $rows['B']<>'User ID'){
									$time=date("Y-m-d H:i:s",strtotime("$rows[G] $rows[H]"));
									mysqli_query($con,"INSERT INTO users_log(user,fid,logged) VALUES('$rows[B]','$rows[D]','$time')");
									$count_row++;
								}
								$x++;
							}
						}
						echo $count_row.' Data imported!';
					}
				}
			}
			else echo "Invalid file format..";	
		}
	}
	function getExtension($str){
		$i = strrpos($str,".");
		if (!$i) { return ""; } 
		$l = strlen($str) - $i;
		$ext = substr($str,$i+1,$l);
		return $ext;
	}
	function make_alpha_from_numbers($number) {
		$numeric = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if($number<strlen($numeric)) return $numeric[$number];
		else {
			$dev_by = floor($number/strlen($numeric));
			return make_alpha_from_numbers($dev_by-1) . make_alpha_from_numbers($number-($dev_by*strlen($numeric)));
		}
	}
	if(is_resource($con))mysqli_close($con);
?>