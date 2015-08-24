var canvas  = el("canvas");
var context = canvas.getContext("2d");
var pixelSize = 1;
var img;
var scaled_x = 0;
var scaled_y = 0;
var scaled_width;
var scaled_height;

function el(id) {
    return document.getElementById(id);
}

function drawImageScaled() {
    var hRatio = canvas.width  / img.width;
    var vRatio =  canvas.height / img.height;
    var ratio  = Math.min(hRatio, vRatio);
    scaled_width = Math.floor(img.width * ratio) - (Math.floor(img.width * ratio) % pixelSize);
    scaled_height = Math.floor(img.height * ratio) - (Math.floor(img.height * ratio) % pixelSize);
    context.clearRect(0, 0, canvas.width, canvas.height);
    context.drawImage(img, 0, 0, img.width, img.height, 0, 0, scaled_width, scaled_height);
}

function drawPixelImage() {
    pixelSize = parseInt(document.getElementById('pixelSize').value, 10);
    drawImageScaled();

    var width = scaled_width;
    var height = scaled_height;
    var px = Math.floor(width / pixelSize);
    var py = Math.floor(height / pixelSize);
    var pixels = [];
    var imageData = context.getImageData(0, 0, scaled_width, scaled_height);
    var data = imageData.data;

    // loop through each "pixel" which is really a square of real pixels
    for (var i = 0; i < py; i++) {
    for (var j = 0; j < px; j++) {
    
        var r = 0;
        var g = 0;
        var b = 0;

        // calculate the min and max real pixel coordinates for this "pixel"
        var minx = pixelSize * j;
        var miny = pixelSize * i;
        var maxx = minx + pixelSize;
        var maxy = miny + pixelSize;
        
        // loop through the real pixels in this "pixel" block
        for (var x = minx; x < maxx; x++) {
        for (var y = miny; y < maxy; y++) {
            r += data[((scaled_width * y) + x) * 4];
            g += data[((scaled_width * y) + x) * 4 + 1];
            b += data[((scaled_width * y) + x) * 4 + 2];
        }
        }
    
        // create an object to store the output "pixel" details
        var pixel = {};
        // store the coordinates for the top-left corner of this "pixel"
        pixel.x = minx;
        pixel.y = miny;
        // calculate the average R, G and B values based on the individual real pixels
        pixel.r = Math.floor(r / (pixelSize * pixelSize));
        pixel.g = Math.floor(g / (pixelSize * pixelSize));
        pixel.b = Math.floor(b / (pixelSize * pixelSize));
        pixels.push(pixel);
    
    }
    }

    // context.clearRect(0, 0, canvas.width, canvas.height);
    for(var p = 0; p < pixels.length; p++) {
        var pixel = pixels[p];
        (function(pixel) {
            var time = 1000 * Math.random();
            setTimeout(function() {drawPixel(pixel);}, time);
        })(pixel);
        
    }
    
}

function drawPixel(pixel) {
    context.beginPath();
    context.rect(pixel.x, pixel.y, pixelSize, pixelSize);
    context.fillStyle = 'rgb('+pixel.r+', '+pixel.g+', '+pixel.b+')';
    context.fill();
}

function readImage() {
    if (this.files && this.files[0]) {
        var FR = new FileReader();
        FR.onload = function(e) {
            img = new Image();
            img.onload = function() {
                drawImageScaled();
            };
            img.src = e.target.result;
        };
        FR.readAsDataURL(this.files[0]);
    }
}

el("file").addEventListener("change", readImage, false);
el("pixelize").addEventListener("click", drawPixelImage, false);