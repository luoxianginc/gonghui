//------------------- alert----------------------
function showAlert(options) {
	var defaults = {
		type				: 'warning',
		msg					: 'default alert',
		width				: 300,
		animateType			: 'slideInDown',
		closeAnimateType	: 'slideOutUp',
		animateDuration		: '0.3s',
		autoClose			: true,
		closeTime			: 3000,
	};

	options = $.extend(defaults, options);

	var alertObj = createAlertObj(options);
	setPositionCenter($('body'), alertObj);
	$('body').append(alertObj);

	animateShow(alertObj, options.animateType, options.animateDuration);
	alertObj.click(function() {
		animateRemove($(this), options.closeAnimateType, options.animateDuration);
	});

	if (options.autoClose) {
		setTimeout(function() {
			animateRemove(alertObj, options.closeAnimateType, options.animateDuration);
		}, options.closeTime);
	}
}

function createAlertObj(options) {
	var alertObj = $('<div></div>')
		.addClass('alert alert-' + options.type + ' alert-dismissable')
		.attr({
		   role				: 'alert',
		})
		.css({
			'width'			: options.width + 'px',
			'position'		: 'fixed',
			'top'			: '30px',
			'z-index'		: 9999,
			'display'		: 'none',
			'cursor'		: 'pointer',
			'box-shadow'	: '0 5px 7px rgba(0, 0, 0, 0.4)',
		})
		.append(options.msg);
	return alertObj;
}

function setPositionCenter(parent, obj) {
	var pwd = parent.width();
	var wd = obj.width();
	obj.css('left', ((pwd - wd) / 2) + 'px');
}

function animateShow(obj, type, duration) {
	obj.addClass('animated ' + type)
		.css('animation-duration', duration)
		.show();
}

function animateRemove(obj, type, duration) {
	obj.addClass('animated ' + type).css('animation-duration', duration)
	.one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function() {
		$(this).remove();
	});
}

function animateHide(obj, type, duration, callBack) {
	obj.addClass('animated ' + type).css('animation-duration', duration)
	.one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function() {
		$(this).hide();
		if (callBack != undefined) {
			callBack();
		}
	});
}
