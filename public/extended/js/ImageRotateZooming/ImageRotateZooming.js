(function($)
{
	$.fn.ImageRotateZooming = function(options)
	{
		if (!isCanvasSupported())
		{
			alert("Your browser doesn't support \"canvas\"");
			return this;
		}
		
		var defaults = {
			"zoom": true,
			"rotate": true
		};
		
		function isCanvasSupported(){
			var elem = document.createElement('canvas');
			return !!(elem.getContext && elem.getContext('2d'));
		}
		
		options = $.extend(defaults, options);
		
		if(options.zoom)
		{
			$('body').on({
				'mousewheel': function(e) {
					if (e.target.id == 'canvas') 
					{
						e.preventDefault();
						e.stopPropagation();
					}
				}
			});
		}
		
		this.each(function()
		{
			var rotates_value = [0, 90, 180, 270];

			var canvas_el = '<canvas id="canvas" width="300" height="300"></canvas>';
			$(this).after(canvas_el);
			
			// Create Image
			var img = new Image();
			img.src = $(this).attr("src");
			img.width = $(this).attr("width");
			img.height = $(this).attr("height");
			img.id = "img_zoomed";
			
			// Initialize variables
			var rotate = 0;
			var canvas = $("#canvas")[0];
			var cContext = canvas.getContext('2d');
			var cw = img.width, ch = img.height, cx = 0, cy = 0;
			var degree_select = 0;
			var startDragOffset = {};
			var translatePos = {
				x: 0,
				y: 0
			};
			var scale = 1;
			var mouseDown = false;
			
			canvas.setAttribute('width', cw);
			canvas.setAttribute('height', ch);
			
			img.onload = function() {
				cContext.drawImage(img, cx, cy, cw, ch);
			}

			$(this).hide();
			
			// Create link to rotate image
			var div_rc = $("<div></div>").attr("id", "rotate_container");
			$(this).after(div_rc);
			
			if(options.rotate)
			{
				for	(i = 0; i < rotates_value.length; i++) {
					var a_rc = $("<span class='degree'><a>" + rotates_value[i] + "\u00b0</a></span>").attr("href", "javascript:;");
					$(div_rc).append(a_rc);
					$(a_rc).data("degree", rotates_value[i]);
					$(a_rc).click(function() { rotateImage($(this).data("degree")); });
				}
			}

			canvas.addEventListener('mousedown', function(evt) {
				mouseDown = true;
				startDragOffset.x = evt.clientX - translatePos.x;
				startDragOffset.y = evt.clientY - translatePos.y;
			});

			canvas.addEventListener('mouseup', function(evt) {
				mouseDown = false;
			});

			canvas.addEventListener('mouseover', function(evt) {
				mouseDown = false;
			});

			canvas.addEventListener('mouseout', function(evt) {
				mouseDown = false;
			});

			canvas.addEventListener("mousemove", function (e) {
				if(mouseDown) {
					if(degree_select == 0 || degree_select == 180)
					{
						var margeY = (img.height * scale) - canvas.height;
						var margeX = (img.width * scale) - canvas.width;
					}
					else
					{
						var margeY = (img.width * scale) - canvas.height;
						var margeX = (img.height * scale) - canvas.width;
					}
				
					var posY = translatePos.y;
					translatePos.x = e.clientX - startDragOffset.x;
					translatePos.y = e.clientY - startDragOffset.y;

					if(translatePos.y <= - margeY)
						translatePos.y = - margeY;
						
					if(translatePos.y > 0)
						translatePos.y = 0;
					
					if(translatePos.x <= - margeX)
						translatePos.x = - margeX;
						
					if(translatePos.x > 0)
						translatePos.x = 0;

					translateImage(translatePos);
				}
			});

			canvas.addEventListener("mousewheel", function (e) {
				if(options.zoom)
				{
					if(e.wheelDelta > 0)
						state = "more";
					else
						state = "less";

					zoomImage(state, translatePos);
					rotateImage(degree_select);

					e.preventDefault();
				}
			});

			function rotateImage(degree)
			{
				degree_select = degree;
				cw = img.width;
				ch = img.height;
				cx = 0;
				cy = 0;

				switch(degree)
				{
					case 0:
						cw = img.width;
						ch = img.height;

						canvas.setAttribute('width', img.width);
						canvas.setAttribute('height', img.height);
						cContext.rotate(degree * Math.PI / 180);
						cContext.translate(translatePos.x, translatePos.y);
						cContext.scale(scale, scale);
						cContext.drawImage(img, cx, cy, cw, ch);
						break;
					case 90:
						cw = img.height;
						ch = img.width;
						cy = img.height * (-1);
						canvas.setAttribute('width', img.height);
						canvas.setAttribute('height', img.width);
						cContext.translate(translatePos.x, translatePos.y);
						cContext.rotate(degree * Math.PI / 180);
						cContext.scale(scale, scale);
						cContext.drawImage(img, cx, cy, ch, cw);
						break;
					case 180:
						cw = img.height;
						ch = img.width;
						cx = img.width * (-1);
						cy = img.height * (-1);
						canvas.setAttribute('width', img.width);
						canvas.setAttribute('height', img.height);
						cContext.translate(translatePos.x, translatePos.y);
						cContext.scale(scale, scale);
						cContext.rotate(degree * Math.PI / 180);
						cContext.drawImage(img, cx, cy, ch, cw);
						break;
					case 270:
						cw = img.height;
						ch = img.width;
						cx = img.width * (-1);
						canvas.setAttribute('width', img.height);
						canvas.setAttribute('height', img.width);
						cContext.translate(translatePos.x, translatePos.y);
						cContext.rotate(degree * Math.PI / 180);
						cContext.scale(scale, scale);
						cContext.drawImage(img, cx, cy, ch, cw);
						break;
				}
			}

			function zoomImage(state, translatePos) {
				var step = 0.1;

				if(degree_select == 0 || degree_select == 180)
					var canvasHeight = canvas.height;
				else
					var canvasHeight = canvas.width
				
				if(state == "more" || ((state == "less") && ((img.height * scale) > canvasHeight)))
				{
					if(state == "more")
						scale = scale + step;
					else
						scale = scale - step;

					if(degree_select == 0 || degree_select == 180)
					{
						var margeX = ((img.width * scale) + translatePos.x) - canvas.width;
						var margeY = ((img.height * scale) + translatePos.y) - canvas.height;
					}
					else
					{
						var margeX = ((img.height * scale) + translatePos.x) - canvas.width;
						var margeY = ((img.width * scale) + translatePos.y) - canvas.height;
					}
				
					if(margeX < 0)
						translatePos.x = translatePos.x - margeX;
					if(margeY < 0)
						translatePos.y = translatePos.y - margeY;
					
					canvas.setAttribute('width', img.height);
					canvas.setAttribute('height', img.width);
					cContext.translate(translatePos.x, translatePos.y);
					cContext.scale(scale, scale);
				}

				cContext.drawImage(img, cx, cy, cw, ch);
			}
			
			function translateImage(translatePos) {
				rotateImage(degree_select);
			}
		});

		return this;
	};
})(jQuery)