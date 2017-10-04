class StatusBar {
    indicator = 'Base_StatusBar';
    indicator_text = 'statusbar_text';

    fadeOut = () => {
        if (document.getElementById('nano-bar') !== null) NProgress.configure({parent: '#nano-bar'});

        NProgress.start();
        let statbar = document.getElementById(this.indicator);
        jQuery(statbar).fadeOut();
        NProgress.done();
	};

	fadeIn = () => {
        document.getElementById('dismiss').style.display = 'none';
        let statbar = document.getElementById(this.indicator);
		jQuery(statbar).fadeIn();
    };

	showMessage = (message) => {
        document.getElementById('dismiss').style.display = '';
        document.getElementById(this.indicator_text).innerHTML = message;
	}
}

export default StatusBar