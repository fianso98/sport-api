<?php 

	function shortenText($text, $chars = 15)
	{
	
		if (strlen($text) > $chars+1) // if you want...
		{
		    $text = substr($text, 0, $chars);
		    return $text." ...";
		}else{
			return $text;
		}
	}


	function DeletePic($link)
	{
		if (file_exists($link)) {
			return unlink($link);
		}
		return false;
	}

	function token($length = 20) {
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ&:,';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

	function UploadPics()
	{
		$upname = "";
		$realname = "";
		$error = "";

		// image mime to be checked 
		$imagetype = array(image_type_to_mime_type(IMAGETYPE_GIF), image_type_to_mime_type(IMAGETYPE_JPEG),
		    image_type_to_mime_type(IMAGETYPE_PNG), image_type_to_mime_type(IMAGETYPE_BMP));
		
		$FOLDER = "img/";
		$myfile = $_FILES["imgprod"];
		$keepName = false; // change this for file name.
		for ($i = 0; $i < count($myfile["name"]); $i++) {
		    if ($myfile["name"][$i] <> "" && $myfile["error"][$i] == 0) {
		        // file is ok
		        if (in_array($myfile["type"][$i], $imagetype)) {
		            //Set file name
		            if($keepName) {
		                $file_name =  $myfile["name"][$i];
		            } else {
		                // get extention and set unique name
		                $file_extention = @strtolower(@end(@explode(".", $myfile["name"][$i])));
		                $file_name = date("Ymd") . '_' . rand(10000, 990000) . '.' . $file_extention;
		            }
		            if (!move_uploaded_file($myfile["tmp_name"][$i], $FOLDER . $file_name)) {
		            	$error = "file not moved";
		            }
		        } else {
		        	$error = "invalid file type";
		        }
		    }
		    $all[] = array("filename"=> $myfile["name"][$i], "uploadedname"=> $file_name, "error"=> $error);
		}		

		return $all;
	}

	function compressImage($source, $destination, $quality = 90)
	{
		$info = getimagesize($source);
		switch ($info['mime']) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg($source);
				break;
			case 'image/png':
				$image = imagecreatefrompng($source);
				break;
			case 'image/gif':
				$image = imagecreatefromgif($source);
				break;
		}

		$exif = @exif_read_data($source);

		if (isset($exif['Orientation'])) {
			# Get orientation
			$orientation = $exif['Orientation'];

			# Manipulate image
			switch ($orientation) {
			    case 2:
			        imageflip($image, IMG_FLIP_HORIZONTAL);
			        break;
			    case 3:
			        $image = imagerotate($image, 180, 0);
			        break;
			    case 4:
			        imageflip($image, IMG_FLIP_VERTICAL);
			        break;
			    case 5:
			        $image = imagerotate($image, -90, 0);
			        imageflip($image, IMG_FLIP_HORIZONTAL);
			        break;
			    case 6:
			        $image = imagerotate($image, -90, 0);
			        break;
			    case 7:
			        $image = imagerotate($image, 90, 0);
			        imageflip($image, IMG_FLIP_HORIZONTAL);
			        break;
			    case 8:
			        $image = imagerotate($image, 90, 0); 
			        break;
			}
		}

	    imagejpeg($image, $destination, $quality);

	    imagedestroy($image);
	}

	function UploadPic($file, $prefix = "", $dir = "img/")
	{
		$imagetype = array(image_type_to_mime_type(IMAGETYPE_GIF), image_type_to_mime_type(IMAGETYPE_JPEG),
		    image_type_to_mime_type(IMAGETYPE_PNG), image_type_to_mime_type(IMAGETYPE_BMP));

		if ($file['name'] !== "" && $file['error'] == 0) {
			// file uploaded
			if (in_array($file["type"], $imagetype)) {
				// accepted file type
				$file_extention = @strtolower(@end(@explode(".", $file["name"])));
				$file_name = $prefix."_". date("YmdHis") . rand(10000, 9999999) . ".";

				if ($file['size'] > (8 * 1000 * 1000)) {
					return array('status' => 'error', 'data' => ['msg' => 'file too big! max 8 MB']);
				}

				if ($prefix !== "local") {
					// move file no need to compress or make preview
					if (move_uploaded_file($file["tmp_name"], $dir . $file_name . $file_extention)) {
						return array('status' => 'success', 'data' => ['filename' => $file_name . $file_extention]);
					}else{
						return array('status' => 'error', 'data' => ['msg' => 'file could not be moved']);
					}
				}

				$sub_quality = 20;
				$quality = 90;


				if ($file['size'] > (2 * 1000 * 1000)) {
					$quality = 80;
				}

				if ($file['size'] > (5 * 1000 * 1000)) {
					$quality = 50;
					$sub_quality = 10;
				}

				// normal img
				compressImage($file["tmp_name"], $dir . $file_name . "jpeg", $quality);

				// preview img
				resizeImage($dir . $file_name . "jpeg", $dir . "preview/" . $file_name . "jpeg");

				if (file_exists($dir . $file_name . "jpeg") && file_exists($dir ."preview/". $file_name . "jpeg")) {
					// file been compressed
					return array('status' => 'success', 'data' => ['filename' => $file_name . "jpeg"]);
				}else{
					// file wasnt compressed
					return array('status' => 'error', 'data' => ['msg' => 'file could not be compressed']);
				}		

			}else{
				// file type not accepted
				return array('status' => 'error', 'data' => ['msg' => 'file type not accepted']);
			}
		}else{
			// file didnt upload
			return array('status' => 'error', 'data' => ['msg' => 'file could not be uploaded']);
		}
	}

	function resizeImage($filename, $destination, $max_width = 300, $max_height = 300)
	{
	    list($orig_width, $orig_height) = getimagesize($filename);

	    $width = $orig_width;
	    $height = $orig_height;

	    # taller
	    if ($height > $max_height) {
	        $width = ($max_height / $height) * $width;
	        $height = $max_height;
	    }

	    # wider
	    if ($width > $max_width) {
	        $height = ($max_width / $width) * $height;
	        $width = $max_width;
	    }

	    $image_p = imagecreatetruecolor($width, $height);

	    $image = imagecreatefromjpeg($filename);

	    imagecopyresized($image_p, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);

	    imagejpeg($image_p, $destination, 90);

	    imagedestroy($image_p);
	} 

 ?>