<?php
	function set_branch($id){
		global $con,$_SESSION;
		$result=mysqli_query($con,"SELECT * FROM branches WHERE id='$id'");
		$row=mysqli_fetch_assoc($result);
		$_SESSION['branch']=$row['id'];
		$_SESSION['branch_data']=$row;
	}
	function permission($per,$val=null){
        $per_arr = array();
        if($per>=8){
          array_push($per_arr, 'delete');
          $per-=8;
        }
        if($per>=4){
          array_push($per_arr, 'edit');
          $per-=4;
        }
        if($per>=2){
          array_push($per_arr, 'add');
          $per-=2;
        }
        if($per>=1){
          array_push($per_arr, 'view');
          $per-=1;
        }
        if($val==null){
            return array_reverse($per_arr);
        }else{
            return in_array($val, $per_arr);
        }
    }
	function create_table_button($plugin,$table,$title,$file,$type='pop',$vars=''){
        // $plugin=plugin_basename($file);
        $id=str_replace(' ', '_', strtolower($title));
		if($file=='')$file=$id;
		echo "<script>
		$.add_table_button('$plugin','$table','$title','$file', '$id','$type', '$vars');
		</script>";
	}
	function aasort(&$array, $key){
		$sorter=array();
		$ret=array();
		reset($array);
		foreach ($array as $ii => $va)$sorter[$ii]=$va[$key];
		asort($sorter);
		foreach ($sorter as $ii => $va)$ret[$ii]=$array[$ii];
		$array=$ret;
	}
	function language($text){
		global $language;
		$text1=strtolower($text);
		if(isset($_SESSION['language'][$text1]))return $_SESSION['language'][$text1];
		else return $text;
	}
	function tamil($text){
		global $con;
		$result=mysqli_query($con,"SELECT ta FROM language WHERE en='$text'");
		if(mysqli_affected_rows($con)>0){
			$row=mysqli_fetch_array($result);
			return $row['ta'];
		}
		else return $text;
	}
	function sinhala($text){
		global $con;
		$result=mysqli_query($con,"SELECT si FROM language WHERE en='$text'");
		if(mysqli_affected_rows($con)>0){
			$row=mysqli_fetch_array($result);
			return $row['si'];
		}
		else return $text;
	}
	function dob($nic){//863381479v,196063200795
		$dob=array('day'=>0,'month'=>1,'year'=>0,'gender'=>1);
		$dob['year']=substr($nic, 0, 2);
		$dob['day']=substr($nic, 2, 3);
		if($dob['year']<=20){
			$dob['year']=substr($nic, 0, 4);
			$dob['day']=substr($nic, 4, 3);
		}
		else $dob['year']=1900+$dob['year'];
		
		if($dob['day']>500){
			$dob['day']=$dob['day']-500;
			$dob['gender']=0;
		}
		if($dob['day']<=366){
			$months=array(1=>'31',2=>'29',3=>'31',4=>'30',5=>'31',6=>'30',7=>'31',8=>'31',9=>'30',10=>'31',11=>'30',12=>'31');
			$dob['month']=1;
			while($dob['day']>=$months[$dob['month']]){
				$dob['day']=$dob['day']-$months[$dob['month']];
				$dob['month']++;
			}
			$dob['day']=sprintf("%02d",$dob['day']);
			$dob['month']=sprintf("%02d",$dob['month']);
		}
		return $dob;
	}
	function resize_image($source,$target, $w=180, $h=180, $crop=FALSE) {
		$target_path=dirname($target);
		if(!file_exists($target_path.'/'))mkdir($target_path.'/', 0777, true);
		list($width, $height) = getimagesize($source);
		$r = $width / $height;
		if($w/$h > $r){
			$newwidth = $h*$r;
			$newheight = $h;
		}
		else {
			$newheight = $w/$r;
			$newwidth = $w;
		}
		$magicianObj = new imageLib($source);
		$magicianObj -> resizeImage($newwidth, $newheight);
		$magicianObj -> saveImage($target, 100);
		return $target;
	}
	
	function thumbnail($url,$image,$new_width,$new_height){
		$ext=strtolower(pathinfo($image, PATHINFO_EXTENSION));
		if(!file_exists($url.'thumbnail/'))mkdir($url.'thumbnail/', 0777, true);
		if(!file_exists($url.'thumbnail/'.$image)){
			list($width,$height)=getimagesize($url.$image);
			if($new_width=='' AND $new_height==''){
				$new_height=$height;
				$new_width=$width;
			}
			else if($new_width<>'' AND $new_height==''){
				if($new_width<$width)$new_height=($height/$width)*$new_width;
				else {
					$new_width=$width;
					$new_height=$height;
				}
			}
			else if($new_width=='' AND $new_height<>''){
				if($new_height<$height)$new_width=($width/$height)*$new_height;
				else {
					$new_width=$width;
					$new_height=$height;
				}
			}
			$image_p=imagecreatetruecolor($new_width, $new_height);
			$temp_image='';
			if($ext=='png')$temp_image=imagecreatefrompng($url.$image);
			else $temp_image=imagecreatefromjpeg($url.$image);
			if($temp_image<>''){
				imagecopyresampled($image_p, $temp_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				imagejpeg($image_p,$url.'thumbnail/'.$image,100);
			}
		}
		return $url.'thumbnail/'.$image;
		// return $url.$image;
	}
	function phone_number_format($no){
		$new_no=filter_var($no, FILTER_SANITIZE_NUMBER_INT);
		$new_no=str_replace('+94','',$new_no);
		// $new_no=str_replace('94','',$new_no);
		$new_no=str_replace('-','',$new_no);
		$new_no=sprintf("%010d",$new_no);
		if($new_no=='0000000000')$new_no=$no;
		return $new_no;
	}
	function createRandomPassword(){
		$chars = "abcdefghijkmnopqrstuvwxyz023456789"; 
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '' ;
		while ($i <= 7){
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $pass;
	}
	function convertNumber($number){
		list($integer, $fraction) = explode(".", (string) $number);
		$output = "";
		if ($integer{0} == "-")
		{
			$output = "negative ";
			$integer    = ltrim($integer, "-");
		}
		else if ($integer{0} == "+")
		{
			$output = "positive ";
			$integer    = ltrim($integer, "+");
		}

		if ($integer{0} == "0")
		{
			$output .= "zero";
		}
		else
		{
			$integer = str_pad($integer, 36, "0", STR_PAD_LEFT);
			$group   = rtrim(chunk_split($integer, 3, " "), " ");
			$groups  = explode(" ", $group);

			$groups2 = array();
			foreach ($groups as $g)
			{
				$groups2[] = convertThreeDigit($g{0}, $g{1}, $g{2});
			}

			for ($z = 0; $z < count($groups2); $z++)
			{
				if ($groups2[$z] != "")
				{
					$output .= $groups2[$z] . convertGroup(11 - $z) . (
							$z < 11
							&& !array_search('', array_slice($groups2, $z + 1, -1))
							&& $groups2[11] != ''
							&& $groups[11]{0} == '0'
								? " and "
								: ", "
						);
				}
			}

			$output = rtrim($output, ", ");
		}

		if ($fraction > 0)
		{
			$output .= " and cents";
			for ($i = 0; $i < strlen($fraction); $i++)
			{
				$output .= " " . convertDigit($fraction{$i});
			}
		}

		return $output;
	}

	function convertGroup($index)
	{
		switch ($index)
		{
			case 11:
				return " decillion";
			case 10:
				return " nonillion";
			case 9:
				return " octillion";
			case 8:
				return " septillion";
			case 7:
				return " sextillion";
			case 6:
				return " quintrillion";
			case 5:
				return " quadrillion";
			case 4:
				return " trillion";
			case 3:
				return " billion";
			case 2:
				return " million";
			case 1:
				return " thousand";
			case 0:
				return "";
		}
	}

	function convertThreeDigit($digit1, $digit2, $digit3)
	{
		$buffer = "";

		if ($digit1 == "0" && $digit2 == "0" && $digit3 == "0")
		{
			return "";
		}

		if ($digit1 != "0")
		{
			$buffer .= convertDigit($digit1) . " hundred";
			if ($digit2 != "0" || $digit3 != "0")
			{
				$buffer .= " and ";
			}
		}

		if ($digit2 != "0")
		{
			$buffer .= convertTwoDigit($digit2, $digit3);
		}
		else if ($digit3 != "0")
		{
			$buffer .= convertDigit($digit3);
		}

		return $buffer;
	}

	function convertTwoDigit($digit1, $digit2)
	{
		if ($digit2 == "0")
		{
			switch ($digit1)
			{
				case "1":
					return "ten";
				case "2":
					return "twenty";
				case "3":
					return "thirty";
				case "4":
					return "forty";
				case "5":
					return "fifty";
				case "6":
					return "sixty";
				case "7":
					return "seventy";
				case "8":
					return "eighty";
				case "9":
					return "ninety";
			}
		} else if ($digit1 == "1")
		{
			switch ($digit2)
			{
				case "1":
					return "eleven";
				case "2":
					return "twelve";
				case "3":
					return "thirteen";
				case "4":
					return "fourteen";
				case "5":
					return "fifteen";
				case "6":
					return "sixteen";
				case "7":
					return "seventeen";
				case "8":
					return "eighteen";
				case "9":
					return "nineteen";
			}
		} else
		{
			$temp = convertDigit($digit2);
			switch ($digit1)
			{
				case "2":
					return "twenty $temp";
				case "3":
					return "thirty $temp";
				case "4":
					return "forty $temp";
				case "5":
					return "fifty $temp";
				case "6":
					return "sixty $temp";
				case "7":
					return "seventy $temp";
				case "8":
					return "eighty $temp";
				case "9":
					return "ninety $temp";
			}
		}
	}

	function convertDigit($digit)
	{
		switch ($digit)
		{
			case "0":
				return "zero";
			case "1":
				return "one";
			case "2":
				return "two";
			case "3":
				return "three";
			case "4":
				return "four";
			case "5":
				return "five";
			case "6":
				return "six";
			case "7":
				return "seven";
			case "8":
				return "eight";
			case "9":
				return "nine";
		}
	}
    function telerivet_add_contacts($no,$name){
		$api_key = '5Wl7o_jlrR0hlryaBp0ZVcOTBIPz6GhqSlPg';
		$project_id = 'PJa30e602a2d41af75';
		$phone_id = 'PNf163d1cabc80daf2';
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,"https://api.telerivet.com/v1/projects/$project_id/contacts");
		curl_setopt($curl, CURLOPT_USERPWD, "{$api_key}:");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array('name' =>$name,'phone_number' =>$no,), '', '&'));        
		echo $json = curl_exec($curl);echo '<br/>';
		echo $network_error = curl_error($curl);
		curl_close($curl);
	}
	function dialog_gettaken(){
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://extmife.dialog.lk/extapi/api_admin_00001',
		  CURLOPT_RETURNTRANSFER => true,CURLOPT_ENCODING => '',CURLOPT_MAXREDIRS => 10,CURLOPT_TIMEOUT => 0,CURLOPT_FOLLOWLOCATION => true,CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
		  CURLOPT_HTTPHEADER => array('Authorization: Basic YUJvQ2M4eUc5RlVxZTZEa0ZLcFF1MTR4MGJFYTp5dXZfTVZ6bHdhTEhFcDhYZjFKbGhUZmZRbUlh','Content-Type: application/x-www-form-urlencoded'),
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}
	function time_format($sec){
		$min=round($sec/60);
		$time=sprintf('%02d',floor($min/60)).':'.sprintf('%02d',($min%60));
		return $time;
	}
						
?>