(function () {
    function isCanvasSupported() {
        var elem = document.createElement('canvas');
        return !!(elem.getContext && elem.getContext('2d'));
    }

    function rotateImage(degree) {
		degree_select = degree;
		cw = img.naturalWidth;
		ch = img.naturalHeight;
		cx = 0;
		cy = 0;

        switch (degree) {
            case 0:
                canvas.setAttribute('width', img.naturalWidth);
                canvas.setAttribute('height', img.naturalHeight);
                cContext.rotate(degree * Math.PI / 180);
                cContext.translate(translatePos.x, translatePos.y);
                cContext.scale(scale, scale);
                cContext.drawImage(img, cx, cy, cw, ch);
                break;
            case 90:
                cy = img.naturalHeight * (-1);
                cw = img.naturalHeight;
                ch = img.naturalWidth;
                canvas.setAttribute('width', img.naturalHeight);
                canvas.setAttribute('height', img.naturalWidth);
                cContext.translate(translatePos.x, translatePos.y);
                cContext.rotate(degree * Math.PI / 180);
                cContext.scale(scale, scale);
                cContext.drawImage(img, cx, cy, ch, cw);
                break;
            case 180:
                cx = img.naturalWidth * (-1);
                cy = img.naturalHeight * (-1);
                cw = img.naturalHeight;
                ch = img.naturalWidth;
                canvas.setAttribute('width', img.naturalWidth);
                canvas.setAttribute('height', img.naturalHeight);
                cContext.translate(translatePos.x, translatePos.y);
                cContext.scale(scale, scale);
                cContext.rotate(degree * Math.PI / 180);
                cContext.drawImage(img, cx, cy, ch, cw);
                break;
            case 270:
                cx = img.naturalWidth * (-1);
                cw = img.naturalHeight;
                ch = img.naturalWidth;
                canvas.setAttribute('width', img.naturalHeight);
                canvas.setAttribute('height', img.naturalWidth);
                cContext.translate(translatePos.x, translatePos.y);
                cContext.rotate(degree * Math.PI / 180);
                cContext.scale(scale, scale);
                cContext.drawImage(img, cx, cy, ch, cw);
                break;
        }
    }

    function zoomImage(state, translatePos) {
        var step = 0.1;

        if (degree_select === 0 || degree_select === 180) {
            var canvasHeight = canvas.height;
        } else {
            var canvasHeight = canvas.width;
        }

        if (state === 'more' || (state === 'less' && (img.naturalHeight * scale) > canvasHeight)) {
            if (state === 'more') {
                scale = scale + step;
            } else {
                scale = scale - step;
            }
            if (degree_select === 0 || degree_select === 180) {
                var margeX = (img.naturalWidth * scale + translatePos.x) - canvas.width;
                var margeY = (img.naturalHeight * scale + translatePos.y) - canvas.height;
            } else {
                var margeX = (img.naturalHeight * scale + translatePos.x) - canvas.width;
                var margeY = (img.naturalWidth * scale + translatePos.y) - canvas.height;
            }

            if (margeX < 0) translatePos.x = translatePos.x - margeX;
            if (margeY < 0) translatePos.y = translatePos.y - margeY;

            canvas.setAttribute('width', img.naturalHeight);
            canvas.setAttribute('height', img.naturalWidth);
            cContext.translate(translatePos.x, translatePos.y);
            cContext.scale(scale, scale);
        }

        cContext.drawImage(img, cx, cy, cw, ch);
    }

    function translateImage(translatePos) {
        rotateImage(degree_select);
    }

    function initImageRotateZooming(element, options) {
		if(!NodeList.prototype.isPrototypeOf(element))
			element = new Array(element);

        if (!isCanvasSupported()) {
            alert("Your browser doesn't support \"canvas\"");
            return;
        }

        var defaults = {
            "zoom": true,
            "rotate": true
        };

        options = Object.assign(defaults, options);

        if (options.zoom) {
            document.body.addEventListener('mousewheel', function (e) {
                if (e.target.id === 'canvas') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }

        element.forEach(function (el) {
            var rotates_value = [0, 90, 180, 270];

            canvas = document.createElement('canvas');
            canvas.id = 'canvas';
            canvas.width = 300;
            canvas.height = 300;
            el.parentNode.insertBefore(canvas, el.nextSibling);

            img = new Image();
            img.src = el.src;
            img.naturalWidth = el.width;
            img.naturalHeight = el.height;

            rotate = 0;
            cContext = canvas.getContext('2d');
            cw = img.naturalWidth;
            ch = img.naturalHeight;
            cx = 0;
            cy = 0;
            degree_select = 0;
            startDragOffset = {};
            translatePos = { x: 0, y: 0 };
            scale = 1;
            var mouseDown = false;

            canvas.setAttribute('width', cw);
            canvas.setAttribute('height', ch);

            img.onload = function () {
                cContext.drawImage(img, cx, cy, cw, ch);
            };

            el.style.display = 'none';

            var div_rc = document.createElement('div');
            div_rc.id = 'rotate_container';
            el.parentNode.insertBefore(div_rc, el.nextSibling);

            if (options.rotate) {
                rotates_value.forEach(function (value) {
                    var a_rc = document.createElement('span');
                    a_rc.className = 'degree';
                    a_rc.innerHTML = '<a>' + value + '\u00b0</a>';
                    a_rc.href = 'javascript:;';
                    div_rc.appendChild(a_rc);
                    a_rc.dataset.degree = value;
                    a_rc.addEventListener('click', function () {
                        rotateImage(parseInt(this.dataset.degree));
                    });
                });
            }

            canvas.addEventListener('mousedown', function (evt) {
                mouseDown = true;
                startDragOffset.x = evt.clientX - translatePos.x;
                startDragOffset.y = evt.clientY - translatePos.y;
            });

            canvas.addEventListener('mouseup', function () {
                mouseDown = false;
            });

            canvas.addEventListener('mouseover', function () {
                mouseDown = false;
            });

            canvas.addEventListener('mouseout', function () {
                mouseDown = false;
            });

            canvas.addEventListener('mousemove', function (e) {
                if (mouseDown) {
                    if (degree_select === 0 || degree_select === 180) {
                        var margeY = img.naturalHeight * scale - canvas.height;
                        var margeX = img.naturalWidth * scale - canvas.width;
                    } else {
                        var margeY = img.naturalWidth * scale - canvas.height;
                        var margeX = img.naturalHeight * scale - canvas.width;
                    }

                    var posY = translatePos.y;
                    translatePos.x = e.clientX - startDragOffset.x;
                    translatePos.y = e.clientY - startDragOffset.y;

                    if (translatePos.y <= -margeY) translatePos.y = -margeY;
                    if (translatePos.y > 0) translatePos.y = 0;
                    if (translatePos.x <= -margeX) translatePos.x = -margeX;
                    if (translatePos.x > 0) translatePos.x = 0;

                    translateImage(translatePos);
                }
            });

            canvas.addEventListener('mousewheel', function (e) {
                if (options.zoom) {
                    var state = e.wheelDelta > 0 ? 'more' : 'less';
                    zoomImage(state, translatePos);
					rotateImage(degree_select);
                    e.preventDefault();
                }
            });
        });
    }

    window.ImageRotateZooming = initImageRotateZooming;
})();