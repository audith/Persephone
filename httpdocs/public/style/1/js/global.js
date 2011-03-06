jQuery( document ).ready(
	function() {
		/*
		jQuery("FORM TABLE TR").click(
			function(event){
				jQuery(this).filter("TD INPUT[type='radio']").click();
				jQuery(this).filter("TD INPUT[type='checkbox']").click();
			}
		);
		*/

		jQuery(window).bind("resize",function(){
			if ( jQuery("DIV#side_pane_control_button").is(":visible") )
			{
				_left = jQuery("DIV#midbar").offset().left + jQuery("DIV#midbar").width() - jQuery("DIV#side_pane_control_button").width() - 50;
				jQuery("DIV#side_pane_control_button").css( "left" , _left );
			}
		});

		jQuery("UL#side_pane").hover(
			function(event){
				if ( jQuery("DIV#side_pane_control_button").is(":hidden") )
				{
					_top  = jQuery("DIV#midbar").offset().top + jQuery("DIV#midbar").height() + 2;
					_left = jQuery("DIV#midbar").offset().left + jQuery("DIV#midbar").width() - jQuery("DIV#side_pane_control_button").width() - 50;
					jQuery("DIV#side_pane_control_button").css( "top" , _top ).css( "left" , _left ).addClass("forward");
				}
				jQuery(this).animate({top:16},500,"swing",function(){
					jQuery("DIV#side_pane_control_button").fadeIn(1000);
				});
				return 1;
			}
		);

		/*
		jQuery("UL#side_pane").mouseout(
			function(event){
				jQuery(this).animate({top:0},500,"swing");
				return 1;
			}
		);
		*/

		jQuery("DIV#side_pane_control_button").toggle(
			function(event){
				jQuery("DIV#side_pane_control_button").removeClass("forward").addClass("backward").html("show side pane");
				jQuery("DIV#content UL#side_pane").animate({left:350,opacity:-0.5},500,function(){
					jQuery("DIV#side_pane_control_button").animate({top:jQuery("DIV#side_pane_control_button").offset().top + 10,left:jQuery("DIV#side_pane_control_button").offset().left - 10},500);
					jQuery("DIV#content UL.data_container").removeClass("half_size").addClass("full_size");
					jQuery("DIV#content SAMP#system_console").removeClass("half_size").addClass("full_size");
					jQuery("DIV#content").height(jQuery("DIV#content UL.data_container").height({padding:true,margin:true}) );
					jQuery("DIV#content UL#side_pane").hide();
				});
			},
			function(event){
				jQuery("DIV#side_pane_control_button").removeClass("backward").addClass("forward").html("hide side pane");
				jQuery("DIV#content UL.data_container").removeClass("full_size").addClass("half_size");
				jQuery("DIV#content SAMP#system_console").removeClass("full_size").addClass("half_size");
				jQuery("DIV#side_pane_control_button").animate({top:jQuery("DIV#side_pane_control_button").offset().top - 10,left:jQuery("DIV#side_pane_control_button").offset().left + 10},500);
				jQuery("DIV#content UL#side_pane").animate({left:0,opacity:1},500);
				jQuery("DIV#content").height("auto");
				jQuery("DIV#content UL#side_pane").show();
			}
		);

		jQuery("SPAN.system_console").click(
				function(event){
					jQuery(this).hide();
				}
		);
	}
);