/*
Author       : theme_crazy
Template Name: Amella - Spa and Beauty Salon Template
Version      : 1.0
*/

(function($)
{
	"use strict";
	
	// Preloader
	jQuery(window).on('load', function() {
		preloader();
	});
	
	// Animation section
	 if(jQuery('.wow').length){
		var wow = new WOW(
		  {
			boxClass:     'wow',      // animated element css class (default is wow)
			animateClass: 'animated', // animation css class (default is animated)
			offset:       0,          // distance to the element when triggering the animation (default is 0)
			mobile:       true,       // trigger animations on mobile devices (default is true)
			live:         true       // act on asynchronously loaded content (default is true)
		  }
		);
		wow.init();
	}
	
	
	// Testimonials Carousel
	jQuery(".testimonial-carousel").owlCarousel({
		loop:true,
		margin: 30,
		nav: false,
		dots: true,
		autoplay: true,
		responsiveClass: true,
		responsive:{
			0:{
				items:1,
			},
			640:{
				items:2,
			},
			768:{
				items:2,
			},
			992:{
				items:2,
			}
		}
	});
	
	// CounterUp 
	jQuery('.counter').counterUp({
		delay: 10,
		time: 2000
	});
	
	// Popup
	jQuery('.venobox').venobox();
	
	//Custom select box
	jQuery('.select-option').chosen({disable_search_threshold:10});
	
	//Datepicker
	jQuery( ".date-pic" ).datepicker({
		todayBtn: "linked",
		keyboardNavigation: false,
		forceParse: false,
		calendarWeeks: true,
		autoclose: true,
		format: 'mm/dd/yyyy'
	});
	
	//Timepicker
	jQuery(".timepicker").timepicker({
		showInputs: false,
		showMeridian: false
	});
	
	// Back to top 		
	jQuery('.back-top a').on('click', function(event) {
		jQuery('body,html').animate({scrollTop:0},800);
		return false;
	});
	
	jQuery(window).on('scroll', function() {
		
		// Back to top 
		if(jQuery(this).scrollTop()>150){
			jQuery('.back-top').fadeIn();
		}
		else{
			jQuery('.back-top').fadeOut();
		}
	});
	
	// Preload
	function preloader(){
		jQuery(".preloaderimg").fadeOut();
		jQuery(".preloader").delay(200).fadeOut("slow").delay(200, function(){
			jQuery(this).remove();
		});
	}
	
	// Slider Caption Animation 
	function doAnimations( elems ) {
		//Cache the animationend event in a variable
		var animEndEv = 'webkitAnimationEnd animationend';
		
		elems.each(function () {
			var $this = $(this),
				$animationType = $this.data('animation');
			$this.addClass($animationType).one(animEndEv, function () {
				$this.removeClass($animationType);
			});
		});
	}
	
	//Variables on page load 
	var $myCarousel = $('#banner'),
		$firstAnimatingElems = $myCarousel.find('.item:first').find("[data-animation ^= 'animated']");
		
	//Initialize carousel 
	$myCarousel.carousel();
	
	//Animate captions in first slide on page load 
	doAnimations($firstAnimatingElems);
	
	//Pause carousel  
	$myCarousel.carousel('pause');
	
	
	//Other slides to be animated on carousel slide event 
	$myCarousel.on('slide.bs.carousel', function (e) {
		var $animatingElems = $(e.relatedTarget).find("[data-animation ^= 'animated']");
		doAnimations($animatingElems);
	});
	
	// Vertical Center Modal
	function centerModals($element) {
		var $modals;
		if ($element.length) {
			$modals = $element;
		} else {
			$modals = jQuery('.modal-vcenter:visible');
		}
		$modals.each( function(i) {
			var $clone = jQuery(this).clone().css('display', 'block').appendTo('body');
			var top = Math.round(($clone.height() - $clone.find('.modal-content').height()) / 2);
			top = top > 0 ? top : 0;
			$clone.remove();
			jQuery(this).find('.modal-content').css("margin-top", top);
		});
	}
		
	jQuery('.modal-vcenter').on('show.bs.modal', function(e) {
	  centerModals($(this));
	});
	jQuery(window).on('resize', centerModals);
	
	
	jQuery('.tilt-img').tilt({
		maxTilt:7					
	});
	
})(jQuery);	
	
