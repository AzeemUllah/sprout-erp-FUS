<?php
//Utils:
function generateRandomString($length = 10, $target_dir) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
	$temp = $target_dir;
	$target_dir .= $randomString;
	if (file_exists($target_dir)) {
		$randomString = generateRandomString(25, $temp);
	} else {
		return $randomString;
	}
}
//Utils ends here.





//config variables
$target_dir = "uploads/";				// main directory
$allowed_ext = array("docx", "png");  // extentions without . (dot)
$allowed_filesize_limit = 10000000;	 //in Bytes
//config variables ends here.


//database config variables
$servername = "46.101.37.156";
$username = "sprout";
$password = "sprout12345";
$dbname = "sprout_users";

//database config variables ends here


//return variables
$status = true;
$error = '';
$logs = '';
$results = '';
$toReturn = '';
$url = '';
//return variables ends here.


//input variables
$company_name = $_POST['company_name'];
//input variables ends here.
$logs .= "company name: " . $company_name . "\n";




//TO DO: 
//CHECK	COMPANY NAME ACROSS DATABASE
//PUT SECURITY MEASURES


if($_POST['password'] != 'abcdef'){
    $toReturn = array("status" => "error", "error" => "Access denied.");
    echo json_encode($toReturn);
    return;
}




if (file_exists($target_dir)) {
    $logs .= "Main directory exists\n";
	$target_dir .= $company_name . "/";
	if (file_exists($target_dir)) {
		$logs .= "Provided company directory exists\n";
	} else {
		mkdir($target_dir);
		$logs .= "Provided company directory does not exist. CREATED ONE.\n";
	}
} else {
	mkdir($target_dir);
    $logs .= "Main directory does not exist. CREATED ONE.\n";
}


//calculated variables
$original_filename = basename($_FILES["fileToUpload"]["name"]);
$original_filename_ext = pathinfo($original_filename, PATHINFO_EXTENSION);
$oriinal_file_size = $_FILES['fileToUpload']['size'];
$target_file = $target_dir . generateRandomString(25,$target_dir) . date('YmdHisn') ."." . $original_filename_ext;
//calculated variables ends here.




if(in_array($original_filename_ext,$allowed_ext)){
	$logs .= "allowed extention\n";
}
else{
	$status = false;
	$logs .= "extention not allowed\n";
	$error .= "extention not allowed\n";
}


$logs .= "File size: " . $oriinal_file_size . "\n";


if($oriinal_file_size > $allowed_filesize_limit){
	$status = false;
	$logs .= "File size limit exceded.\n";
	$error .= "File size limit exceded.\n";
}
else{
	$logs .= "File is well withen size limit.\n";
}

$logs .= "Target file: " . $target_file . "\n";





// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    $status = false;
    die("Connection failed: " . $conn->connect_error);
    $error .= $conn->connect_error;
} else {
    $logs .= "Connected successfully";
    $sql = "SELECT * from companies_data where company_name = '".$company_name."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        // output data of each row
        $logs .= "Company exists in database.";
        $allowed_data = 0;
        $consumed_data = 0;
        while($row = $result->fetch_assoc()){
            $logs .= "allowed data: " . $row["data_allowed"] . "Consumed data: " . $row["data_consumed"];
            $allowed_data = $row["data_allowed"];
            $consumed_data = $row["data_consumed"];
        }

        if($consumed_data+$oriinal_file_size > $allowed_data){
            $status = false;
            $logs .= "Data limit exceeds contact admin of your company to request more.";
            $error .= "Data limit exceeds contact admin of your company to request more.";
        }
        else{
            $logs .= "Data limit on server checks out.";

            if($status == true){
                if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                    $logs .= "The file ". $target_file . " has been uploaded.";
                    $results .= "The file ". $target_file . " has been uploaded.";
                    $url = $target_file;
                    $status = true;
                } else {
                    $logs .= "Sorry, there was an error uploading your file.\n";
                    $results .= "Sorry, there was an error uploading your file.\n";
                    $status = false;
                }
            }

            $temp = $consumed_data+$oriinal_file_size;
            $sql = "UPDATE  companies_data SET data_consumed = ".$temp." WHERE  company_name =  'sprout'";
            if ($conn->query($sql) === TRUE) {
                $logs .= "Updated file size audit in database to new value.";

            } else {
                $logs .= "Error updating file size audit in database: " . $conn->error;
                $error .= "Error updating file size audit in database: " . $conn->error;
                $status .= false;
                echo $conn->error;
            }

            if($status == true){
                $toReturn = array("status" => "ok", "result" => $results, "logs" => $logs, "url" => $url);
                echo json_encode($toReturn);
            }
            else{
                $toReturn = array("status" => "error", "error" => $error, "logs" => $logs);
                echo json_encode($toReturn);
            }

        }
    } else {
        $logs .= "Company doesn't exists in database.";
        $status = false;
    }
    $conn->close();
}
return;



//marcoreus






//echo "status: " . $status;





/****

$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
echo $target_file . "\n";
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
}
// Check if file already exists
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}
// Check file size
if ($_FILES["fileToUpload"]["size"] > 500000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
}
// Allow certain file formats
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
&& $imageFileType != "gif" ) {
    echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    $uploadOk = 0;
}
// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
****/
?>