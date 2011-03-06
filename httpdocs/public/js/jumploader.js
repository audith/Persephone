// JumpLoader 
(function($){
	$.fn.jumpLoader = function(newOptions){
		if ( this.length != 1 )
		{
			debug("Multiple applet instances were passed! Pass only one...");
			return false;
		}
		var options = $.extend({}, $.fn.jumpLoader.defaults, newOptions);
		return this.each(
			function ()
			{
				$this = $(this);
				if ( ($this == null) || ($this == undefined) )
				{
					return false;
				}
				// if metadata is present, extend main_opts, otherwise just use main_opts
				options = $.meta ? $.extend({}, options, $this.data()) : options;

				// Property that holds jumpLoader JScript interface objects
				appletJavascriptInterface = {
					"uploader":        null,
					"uploaderConfig":  null,
					"viewConfig":      null
				};

				if ( options.startButton != null )
				{
					$(options.startButton).click(function(e){
						init( appletJavascriptInterface );
						error = appletJavascriptInterface['uploader'].startUpload();
						if ( error != null )
						{
							debug( error );
						}
					});
				}

				if ( options.stopButton != null )
				{
					$(options.stopButton).click(function(e){
						init( appletJavascriptInterface );
						error = appletJavascriptInterface['uploader'].stopUpload();
						if ( error != null )
						{
							debug( error );
						}
					});
				}
			}
		);
	};

	$.fn.jumpLoader.defaults = {
		startButton: null,
		stopButton:  null
	};

	function init ( appletJavascriptInterface )
	{
		appletJavascriptInterface['uploader'] = $this[0].getUploader();
		appletJavascriptInterface['uploaderConfig'] = $this[0].getUploaderConfig();
		appletJavascriptInterface['viewConfig'] = $this[0].getViewConfig();
	}

	function debug ( message )
	{
		if ( window.console && window.console.log )
		{
			window.console.log( "jumpLoader Debug: " + message );
		}
	}
})(jQuery);