# ImageRotateZooming

**ImageRotateZooming** is a smart JQuery plugin for rotation and zooming an Image.

## How it works
First step, insert following code in a HTML page:

```
<link rel="stylesheet" href="ImageRotateZooming.css" />
<script type="text/javascript" src="ImageRotateZooming.js"></script>

<script>
	$(function() {
		$("#img_zoomed").ImageRotateZooming({
			"rotate": true,
			"zoom": true
		});
	});
</script>
```
...
```
<img id="img_zoomed" src="my_image.png" width="400" height="400" />
```

Now, by clicking on "0°", "90°", "180°" or "270°", image rotate. If you use mouse wheel, you can zoom on image.

To try it, download plugin and launch demo!

## Version
v1.0.0 - 2015/5/11

## Author
Wakonda.GURU
