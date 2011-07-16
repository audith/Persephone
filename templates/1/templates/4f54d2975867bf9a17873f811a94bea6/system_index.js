jQuery(document).ready(function()
{
	jQuery( ".ui-accordion" )
		.accordion( "option", "header", "h2" )
		.accordion( "option", "animated", "easeslide" )
		.bind(
			"accordionchange",
			function(event,ui){
				var scrollPosition = ( ui.newHeader.offset() ) ? ui.newHeader.offset().top : null;
				if ( scrollPosition )
				{
					jQuery("html,body").animate({scrollTop : scrollPosition } , 500);
				}
			}
		);

	jQuery("#forms__settings__edit INPUT[type='button'][id^='revert_']").click(
		function(event){
			jQuery("#system_console").html("Processing setting revert request... Please wait!").removeClass("error success").addClass("notice").show();
			scrollToConsole();
			jQuery.ajax({
				type:      "POST",
				url:       window.location.href,
				dataType:  "json",
				data:      "do=revert&for=" + jQuery(this).attr("id"),
				cache:     false,
				timeout:   15000,
				error:     function( xhr, text_status, error ){
					jQuery("SAMP#system_console")
						.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.")
						.removeClass("notice success").addClass("error");
					return 0;
				},
				success:   function( data )
				{
					if ( data.responseCode == '1' )
					{
						jQuery("SAMP#system_console").html( data.responseMessage ).removeClass("notice error").addClass("success");
						setTimeout( "window.location = window.location.href", 2000 );
						return 1;
					}
					jQuery("SAMP#system_console")
						.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.")
						.removeClass("notice success").addClass("error");
					return 0;
				}
			});
		}
	);
});