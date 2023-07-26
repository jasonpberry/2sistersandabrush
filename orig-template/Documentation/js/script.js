(function($)
{
	"use strict";
	
	
	//========================= preloader ================
	jQuery(window).on('load', function() {
		preloader();
	});
	
	var headerHeight = jQuery('.navbar').outerHeight();
	jQuery('.navbar-nav li a').on('click', function(event) {
		jQuery('.navbar-nav li a').removeClass('active');
		jQuery(this).addClass('active');
		var $anchor = jQuery(this);
		jQuery('html, body').stop().animate({
			scrollTop: jQuery($anchor.attr('href')).offset().top-headerHeight
		}, 1500, 'easeInOutExpo');
		event.preventDefault();
	});
	
	jQuery(".navbar-nav li a").on("click",function(event){
		jQuery(".navbar-collapse").removeClass('show');
		jQuery('.navbar-toggler').addClass('collapsed');
	});
	
	/*============================== Back to top =========================*/
	jQuery(".back-top").hide();
	
	jQuery('.back-top a').on('click', function(event) {
		jQuery('body,html').animate({scrollTop:0},800);
		return false;
	});
	
	jQuery(window).on('scroll', function() {
		if(jQuery(this).scrollTop()>150){
			jQuery('.back-top').fadeIn();
		}
		else{
			jQuery('.back-top').fadeOut();
		}
		
		
	});
	
	//============= Preload ============ 
	function preloader(){
		jQuery(".preloaderimg").fadeOut();
		jQuery(".preloader").delay(200).fadeOut("slow").delay(200, function(){
			jQuery(this).remove();
		});
	}
	
})(jQuery);	

	
