/*!
 * cKeyboard JavaScript Library v1.0.0
 * https://github.com/c42759/ckeyboard/
 *
 *
 * Copyright c42759 (Carlos Santos) and other contributors
 * Released under the MIT license
 *
 * Date: 2019-07-03
 */

var cKeyboard_config = {
	input_target : 'input',
	interation_mode : 'click', // touchstart
	target : '#keyboard',
	capslock_state : false,
	layout : [
		{
			'q' : {name : 'q', text : 'q', class : 'cKKey'},
			'w' : {name : 'w', text : 'w', class : 'cKKey'},
			'e' : {name : 'e', text : 'e', class : 'cKKey'},
			'r' : {name : 'r', text : 'r', class : 'cKKey'},
			't' : {name : 't', text : 't', class : 'cKKey'},
			'y' : {name : 'y', text : 'y', class : 'cKKey'},
			'u' : {name : 'u', text : 'u', class : 'cKKey'},
			'i' : {name : 'i', text : 'i', class : 'cKKey'},
			'o' : {name : 'o', text : 'o', class : 'cKKey'},
			'p' : {name : 'p', text : 'p', class : 'cKKey'}
		},
		{
			'a' : {name : 'a', text : 'a', class : 'cKKey'},
			's' : {name : 's', text : 's', class : 'cKKey'},
			'd' : {name : 'd', text : 'd', class : 'cKKey'},
			'f' : {name : 'f', text : 'f', class : 'cKKey'},
			'g' : {name : 'g', text : 'g', class : 'cKKey'},
			'h' : {name : 'h', text : 'h', class : 'cKKey'},
			'j' : {name : 'j', text : 'j', class : 'cKKey'},
			'k' : {name : 'k', text : 'k', class : 'cKKey'},
			'l' : {name : 'l', text : 'l', class : 'cKKey'}
		},
		{
			'shift' : {name : 'shift', text : '', class : 'cKFunction'},
			'z' : {name : 'z', text : 'z', class : 'cKKey'},
			'x' : {name : 'x', text : 'x', class : 'cKKey'},
			'c' : {name : 'c', text : 'c', class : 'cKKey'},
			'v' : {name : 'v', text : 'v', class : 'cKKey'},
			'b' : {name : 'b', text : 'b', class : 'cKKey'},
			'n' : {name : 'n', text : 'n', class : 'cKKey'},
			'm' : {name : 'm', text : 'm', class : 'cKKey'},
			'backspace' : {name : 'backspace', text : '', class : 'cKFunction'}
		},
		{
			'numeric_switch' : {name : 'numeric-switch', text : '123', class : 'cKFunction'},
			'@' : {name : '@', text : '@', class : 'cKKey'},
			'.' : {name : '.', text : '.', class : 'cKKey'},
			'space' : {name : 'space', text : ' ', class : 'cKKey'},
			'return' : {name : 'return', text : 'Return', class : 'cKFunction'}
		}
	],

	target_numeric : '#keyboard_numeric',
	layout_numeric : [
		{
			'1' : {name : '1', text : '1', class : 'cKKey'},
			'2' : {name : '2', text : '2', class : 'cKKey'},
			'3' : {name : '3', text : '3', class : 'cKKey'},

			' 0 ' : {name : '0', text : '0', class : 'cKKey'},
			'.' : {name : '.', text : '.', class : 'cKKey'},
			',' : {name : ',', text : ',', class : 'cKKey'},
			'-' : {name : '-', text : '-', class : 'cKKey'},
			'@' : {name : '@', text : '@', class : 'cKKey'}
		},
		{
			'4' : {name : '4', text : '4', class : 'cKKey'},
			'5' : {name : '5', text : '5', class : 'cKKey'},
			'6' : {name : '6', text : '6', class : 'cKKey'},

			'/' : {name : '/', text : '/', class : 'cKKey'},
			':' : {name : ':', text : ':', class : 'cKKey'},
			'_' : {name : '_', text : '_', class : 'cKKey'},
			'*' : {name : '*', text : '*', class : 'cKKey'},
			'#' : {name : '#', text : '#', class : 'cKKey'}
		},
		{
			'7' : {name : '7', text : '7', class : 'cKKey'},
			'8' : {name : '8', text : '8', class : 'cKKey'},
			'9' : {name : '9', text : '9', class : 'cKKey'},

			'(' : {name : '(', text : '(', class : 'cKKey'},
			')' : {name : ')', text : ')', class : 'cKKey'},
			'$' : {name : '$', text : '$', class : 'cKKey'},
			'?' : {name : '?', text : '?', class : 'cKKey'},
			'!' : {name : '!', text : '!', class : 'cKKey'}
		},
		{
			'abc_switch' : {name : 'abc-switch', text : 'abc', class : 'cKFunction'},
			'space' : {name : 'space', text : ' ', class : 'cKKey'},
			'backspace' : {name : 'backspace', text : '', class : 'cKFunction'}
		}
	]
};

function cKeyboard () {
	// KEYBOARD CREATOR
	$.each(cKeyboard_config.layout, function (i, e) {
		$(cKeyboard_config.target).append('<ul class="cK cKLine"></ul>');  // CREATE LINE

		var line_target = $(cKeyboard_config.target + ' ul')[i];

		$.each( e, function (ia, ea) {
			$(line_target).append('<li class="cK ' + ea.class + ' cKey-' + ea.name + '">' + ea.text + '</li>');
		});
	});

	// KEYBOARD NUMERIC CREATOR
	$(cKeyboard_config.target_numeric).hide();
	$.each(cKeyboard_config.layout_numeric, function (i, e) {
		$(cKeyboard_config.target_numeric).append('<ul class="cK cKLine"></ul>');  // CREATE LINE

		var line_target = $(cKeyboard_config.target_numeric + ' ul')[i];

		$.each( e, function (ia, ea) {
			$(line_target).append('<li class="cK ' + ea.class + ' cKey-' + ea.name + '">' + ea.text + '</li>');
		});
	});

	// KEY CLICK
	$('body').on(cKeyboard_config.interation_mode, '.cK.cKKey', function () {
		if (cKeyboard_config.capslock_state) {
			$(cKeyboard_config.input_target).val(
				$(cKeyboard_config.input_target).val() + $(this).html().toUpperCase()
			);
		} else {
			$(cKeyboard_config.input_target).val(
				$(cKeyboard_config.input_target).val() + $(this).html()
			);
		}
	});

	// UPPERCASE SHIFT CLICK
	$('body').on(cKeyboard_config.interation_mode, '.cK.cKFunction.cKey-shift', function () {
		cKeyboard_config.capslock_state = !cKeyboard_config.capslock_state;
		if (cKeyboard_config.capslock_state) {
			$('.cK.cKKey').addClass('uppercase');
		} else {
			$('.cK.cKKey').removeClass('uppercase');
		}
	});

	// UPPERCASE BACKSPACE CLICK
	$('body').on(cKeyboard_config.interation_mode, '.cK.cKFunction.cKey-backspace', function () {
		$(cKeyboard_config.input_target).val($(cKeyboard_config.input_target).val().slice(0, -1));
	});

	// UPPERCASE SHIFT CLICK
	$('body').on(cKeyboard_config.interation_mode, '.cK.cKFunction.cKey-numeric-switch', function () {
		$.when($(cKeyboard_config.target).fadeOut()).done(function () {
			$(cKeyboard_config.target_numeric).fadeIn();
		});
	});

	// UPPERCASE SHIFT CLICK
	$('body').on(cKeyboard_config.interation_mode, '.cK.cKFunction.cKey-abc-switch', function () {
		$.when($(cKeyboard_config.target_numeric).fadeOut()).done(function () {
			$(cKeyboard_config.target).fadeIn();
		});
	});
}
