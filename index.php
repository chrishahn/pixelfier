<?php

// whenever the script is loaded we check the legacy JPG files to see who can get deleted
if ($handle = opendir(getcwd())) {

    while (false !== ($file = readdir($handle))) { 
        $filelastmodified = filemtime($file);

        // if it's a JPG older than 5 minutes, delete it
        if(preg_match("/.*\.jpg/i", $file) && (time() - $filelastmodified) > 300)
        {
           unlink($file);
        }

    }

    closedir($handle); 
}

// for this request to get handled, you have to specify either a file or an image URL
if (($_POST['url'] != "" || !empty($_FILES['file'])) && $_POST['source'] != "") {
	$srcType = $_POST['source'];
	if ($srcType == 'url') {
		$src = $_POST['url'];
	}
	else {
		$src = $_FILES['file']['tmp_name'];
	}
    // number of real pixels for each image "pixel"
	$pixelSize = $_POST['size'];
    // background color for the rectangle behind the "pixels"
    $bgColor = $_POST['bgColor'];
    // each "pixel" can either be a square or a circle
    $pixelShape = $_POST['pixelShape'];
	$type = exif_imagetype($src);
	$invalid = false;
	switch($type) {
		case IMAGETYPE_GIF:
			$img = imagecreatefromgif($src);
			break;
		case IMAGETYPE_JPEG:
			$img = imagecreatefromjpeg($src);
			break;
		case IMAGETYPE_PNG:
			$img = imagecreatefrompng($src);
			break;
		default:
			$invalid = true;
	}
	if ($pixelSize == '' || !is_numeric($pixelSize) || $pixelSize < 2 || $pixelSize > 50) {
		$pixelSize = 2;
	}
	
	if (!$invalid) {
		$dimensions = getimagesize($src);
		$width = $dimensions[0];
		$height = $dimensions[1];
        // calculate the number of "pixels" based on the image width and height
		$px = floor($width / $pixelSize);
		$py = floor($height / $pixelSize);
		
		$pixels = array();
		
        // loop through each "pixel" which is really a square of real pixels
		for ($i=0; $i < $py; $i++) {
		for ($j=0; $j < $px; $j++) {
		
			$r = $g = $b = 0;
            // calculate the min and max real pixel coordinates for this "pixel"
			$minx = $pixelSize * $j;
			$miny = $pixelSize * $i;
			$maxx = $minx + $pixelSize;
			$maxy = $miny + $pixelSize;
			
			// loop through the real pixels in this "pixel" block
			for ($x = $minx; $x < $maxx; $x++) {
			for ($y = $miny; $y < $maxy; $y++) {
			
                // get the color for each pixel in the "pixel" square
				$index = imagecolorat($img, $x, $y);
                // get the RGB values for this pixel color
				$cols = imagecolorsforindex($img, $index);
                // add the RGB values to the running sums for this "pixel"
				$r += $cols['red'];
				$g += $cols['green'];
				$b += $cols['blue'];
			
			}
			}
		
            // create an array to store the output "pixel" details
			$pixel = array();
            // store the coordinates for the top-left corner of this "pixel"
			$pixel['x'] = $minx;
			$pixel['y'] = $miny;
            // calculate the average R, G and B values based on the individual real pixels
			$pixel['r'] = floor($r / ($pixelSize*$pixelSize));
			$pixel['g'] = floor($g / ($pixelSize*$pixelSize));
			$pixel['b'] = floor($b / ($pixelSize*$pixelSize));
			$pixels[] = $pixel;
		
		}
		}
		
		$new = imagecreatetruecolor($px*$pixelSize, $py*$pixelSize);
        // if the background should be white, then fill the black rectangle
        if ($bgColor == "white") {
            $white = imagecolorallocate($new, 255, 255, 255);
            imagefill($new, 0, 0, $white);
        }
        // reduce the pixel size by 20%, giving the final output a "sectioned" look
        $pixelSize = floor($pixelSize * 0.8);
        // the radius is used to position circular pixels at the center of the "block"
        $radius = floor($pixelSize / 2);
        // loop through creating either squares or circles
        if ($pixelShape == "square") {
            foreach ($pixels as $pixel) {
                // determine the color for this "pixel" using the previously calculated averages
        		$color = imagecolorallocate($new, $pixel['r'], $pixel['g'], $pixel['b']);
    			imagefilledrectangle($new, $pixel['x'], $pixel['y'], $pixel['x']+$pixelSize, $pixel['y']+$pixelSize, $color);
    		}
        } else if ($pixelShape == "circle") {
            foreach ($pixels as $pixel) {
                // determine the color for this "pixel" using the previously calculated averages
        		$color = imagecolorallocate($new, $pixel['r'], $pixel['g'], $pixel['b']);
                imagefilledellipse($new, $pixel['x'] + $radius, $pixel['y'] + $radius, $pixelSize, $pixelSize, $color);
    		}
        }
		
                $name = md5(rand(1, 99999)).".jpg";
		imagejpeg($new, $name, 100);
                echo "<html>
                <body>
                <img src='{$name}' />
                </body>
                </html>";
                exit;
	}
}

?>

<html>
<style>
    body {
        background-image: url('bg.png');
    }
    
    .content {
        width: 300px;
        height: 200px;
        position: absolute;
        left: 50%;
        top: 50%;
        margin: -100px 0 0 -150px;
        background: #383838;
        color: white;
        -moz-border-radius: 20px;
        border-radius: 20px;
        padding: 20px;
    }
    
    .title {
        width: 707px;
        height: 70px;
        position: absolute;
        left: 50%;
        top: 20%;
        margin: -35px 0 0 -353px;
        background: #383838;
        -moz-border-radius: 20px;
        border-radius: 20px;
    }
    
    .title img {
        padding-left: 5px;
        padding-top: 5px;
    }
</style>
<body>
    <div class="title">
        <img src="title.png" />
    </div>
    <div class="content">
        <form action="" method="post"  enctype="multipart/form-data">
            Image Link<br/>
            <input type='radio' name='source' value='url'><input type="text" size="50" name="url" /><br/>
            File Upload<br/>
            <input type='radio' CHECKED name='source' value='file'><input type="file" name="file" size="50" /><br/>
            Pixel Size (2-50, larger numbers work better for larger images)<br/>
            <input type="text" size="5" name="size" /><br/>
            Background Color:&nbsp;
            <select name="bgColor">
                <option value="black">Black</option>
                <option value="white">White</option>
            </select><br/>
            Pixel Shape:&nbsp;
            <select name="pixelShape">
                <option value="square">Square</option>
                <option value="circle">Circle</option>
            </select><br/>
            <input type="submit" value="Pixelize" /> (this can take several seconds)
        </form>
    </div>
</body>
</html>
