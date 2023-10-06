/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import '../css/app_v2.scss';

// Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
// import $ from 'jquery';

// jQuery
global.$ = global.jQuery = window.$ = window.jQuery = require('jquery');

window.bootstrap = require('bootstrap/dist/js/bootstrap.bundle.js');

require('@fortawesome/fontawesome-free/css/all.min.css');

require('./BackToTop/BackToTop')
require('./BackToTop/arrow-up.png');
require('./BackToTop/BackToTop.css');

document.addEventListener('DOMContentLoaded', function () {
	BackToTop();
});