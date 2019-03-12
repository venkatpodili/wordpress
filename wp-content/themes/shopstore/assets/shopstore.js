/*
    * responsiveMenu
    * responsiveMenuMega
    * searchButton
  
**/

;(function($) {

   'use strict'
        var isMobile = {
            Android: function() {
                return navigator.userAgent.match(/Android/i);
            },
            BlackBerry: function() {
                return navigator.userAgent.match(/BlackBerry/i);
            },
            iOS: function() {
                return navigator.userAgent.match(/iPhone|iPad|iPod/i);
            },
            Opera: function() {
                return navigator.userAgent.match(/Opera Mini/i);
            },
            Windows: function() {
                return navigator.userAgent.match(/IEMobile/i);
            },
            any: function() {
                return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
            }
        }; // is Mobile

       
		var back_to_top_scroll = function() {
			
			$('#backToTop').on('click', function() {
				$("html, body").animate({ scrollTop: 0 }, 500);
				return false;
			});
			
			$(window).scroll(function() {
				if ( $(this).scrollTop() > 100 ) {
					
					$('#backToTop').addClass('active');
				} else {
				  
					$('#backToTop').removeClass('active');
				}
				
			});
			
		}; // Responsive Menu   
		
         
    // Dom Ready
    $(function() {
		
        back_to_top_scroll();
       
		if( $(".rd-navbar").length){
			$('.rd-navbar').RDNavbar({
				stickUpClone: false,
                stickUpOffset: 220
				
			});
			
		}
		if( $('.owlGallery').length ){
			$(".owlGallery").owlCarousel({
				
				stagePadding: 0,
				loop: true,
				autoplay: true,
				autoplayTimeout: 2000,
				margin: 10,
				nav: false,
				dots: false,
				smartSpeed: 1000,
				responsive: {
					0: {
						items: 1
					},
					600: {
						items: 1
					},
					1000: {
						items: 1
					}
				}
			});
		}
       
    });

})(jQuery);