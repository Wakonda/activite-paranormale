/*!
 * Star Rating Spanish Translations
 *
 * This file must be loaded after 'star-rating.js'. Patterns in braces '{}', or
 * any HTML markup tags in the messages must not be converted or translated.
 *
 * NOTE: this file must be saved in UTF-8 encoding.
 *
 * bootstrap-star-rating v4.1.3
 * http://plugins.krajee.com/star-rating
 *
 * Copyright: 2013 - 2021, Kartik Visweswaran, Krajee.com
 *
 * Licensed under the BSD 3-Clause
 * https://github.com/kartik-v/bootstrap-star-rating/blob/master/LICENSE.md
 */
(function (factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else if (typeof module === 'object' && typeof module.exports === 'object') { 
        factory(require('jquery'));
    } else { 
        factory(window.jQuery);
    }
}(function ($) {
    "use strict";
    $.fn.ratingLocales.pt = {
        defaultCaption: '{rating} Estrelas',
        starCaptions: {
			0.5: 'Meia estrela',
			1: 'Uma estrela',
			1.5: 'Uma estrela e meia',
			2: 'Duas estrelas',
			2.5: 'Duas estrelas e meia',
			3: 'Três estrelas',
			3.5: 'Três estrelas e meia',
			4: 'Quatro estrelas',
			4.5: 'Quatro estrelas e meia',
			5: 'Cinco estrelas'
		},
		clearButtonTitle: 'Limpar',
		clearCaption: 'Sem avaliação'
    };
}));