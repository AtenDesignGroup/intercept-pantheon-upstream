(function ($) {
  $(document).ready(function () {
    $('.slideshow .field--name-field-media-slideshow').slick({
      dots: false,
      infinite: true,
      speed: 600,
      fade: true,
      slidesToShow: 1,
      prevArrow: '<button class="slideshow__button slideshow__button--prev"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 17 17"><polygon fill="#FFFFFF" fill-rule="evenodd" points="38.65 520.13 30.31 528.14 28.21 525.77 32.71 521.81 22 521.81 22 518.33 32.71 518.33 28.21 514.37 30.31 512 38.65 520.01" transform="rotate(-180 19.325 264.07)"/></svg></button>',
      nextArrow: '<button class="slideshow__button slideshow__button--next"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 17 17"><polygon fill="#FFFFFF" fill-rule="evenodd" points="803.65 520.13 795.31 528.14 793.21 525.77 797.71 521.81 787 521.81 787 518.33 797.71 518.33 793.21 514.37 795.31 512 803.65 520.01" transform="translate(-787 -512)"/></svg></button>'
    });

    $('.slideshow .field--name-field-media-slideshow').show();
  });
}(jQuery));
