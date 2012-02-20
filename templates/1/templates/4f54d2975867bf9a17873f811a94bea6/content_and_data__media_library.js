$(document).ready(function() {
	// Preview - Sneakpeak
	$("TABLE#tables__media_library TBODY TR TD.w_preview_hover.image").mouseover(function(event) {
		event.stopPropagation();
		var _media_id = $(this).parent().children(":eq(0)").html();
		if ( _media_id != null )
		{
			$("DIV#media_library_sneak_peek")
				.css( "position","absolute" )
				.css( "top",event.pageY )
				.css( "left",event.pageX + 10 )
				.show()
				.html( "<img src=\"/static/stream/s-" + _media_id + "\" alt=\"\" style=\"float:left;\" />" );
		}

		$(this).mousemove(
			function(event)
			{
				if ( (event.pageY > 0) && (event.pageX > 0) && (event.pageX < $(document).width()) && (event.pageY < $(document).height()) )
				{
					$("DIV#media_library_sneak_peek")
						.css( "position","absolute" )
						.css( "top",event.pageY )
						.css( "left",event.pageX + 10 )
						.show();
				}
			}
		);

		$(this).mouseout(
			function(event)
			{
				$("DIV#media_library_sneak_peek")
					.css("background-image","url('{{$STYLE_IMAGES_URL}}/loading_3.gif')")
					.html("")
					.hide();
			}
		);
	});

	$("TABLE#tables__media_library TBODY TR TD.w_preview_hover").click(function(event) {
		event.stopPropagation();
		var _media_id = $(this).parent().children(":eq(0)").html();
		$(this).parent().next("[id='file_description__" + _media_id + "']").toggle();
		return;
	});

	$("TABLE#tables__media_library TBODY TR.file_description").click(function(event) {
		$(this).toggle();
	});

	$("FORM#forms__media_library TABLE TFOOT INPUT:eq(1)").click(function(event) {
		if ( $("#jumpLoaderApplet").is(":hidden") )
		{
			var zIndex = dimLights();
			$("#jumpLoaderApplet")
				.css({
					"position" : "absolute",
					"top"      : ($(window).height()-$("#jumpLoaderApplet").height())/2 + $(window).scrollTop(),
					"left"     : ($(window).width()-$("#jumpLoaderApplet").width())/2,
					"z-index"  : zIndex + 1
				})
				.fadeIn("medium")
			$("#jumpLoaderApplet OBJECT").jumpLoader({ startButton: $("#jumpLoaderApplet #startButton"), stopButton: $("#jumpLoaderApplet #stopButton") });
		}
		else
		{
			$("#jumpLoaderApplet")
				.fadeOut("medium",function(){
					unDimLights();
				});
		}
	});
});