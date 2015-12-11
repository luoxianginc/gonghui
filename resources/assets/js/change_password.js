window.$ = window.jQuery = require('jquery');

$(document).ready(function() {
	$('[type=submit]').click(function(e) {
		e.preventDefault();

		var account		= $('#account').val();
		var oldPassword	= $('#old_password').val();
		var password	= $('#password').val();
		var url			= $('form').attr('action');

		if (account == '' || oldPassword == '' || password == '') {
			return false;
		}

		$.post(url, {account: account, old_password: oldPassword, password: password}, function(res) {
			/*
			if (res.meta.code == 400) {
				showAlert({msg: res.meta.error_message});
			} else if (res.meta.code == 200) {
				showAlert({msg: '密码修改成功', type: 'success'});
			}
			*/

			window.ChangePasswordResult.showResultToast(res)
		});
	});
});
