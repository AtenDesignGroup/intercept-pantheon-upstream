/**
 * Handles context switching between mobile and desktop headers.
 */
(function ($) {
  const CONTEXT_DESKTOP = 'desktop';
  const CONTEXT_MOBILE = 'mobile';
  let deviceContext = CONTEXT_DESKTOP;
  let menuContent;
  let searchContent;
  let accountContent;

  /**
   * Moves content from mobile header to desktop header.
   */
  function setDesktopContext() {
    if (deviceContext === CONTEXT_DESKTOP) {
      return;
    }
    deviceContext = CONTEXT_DESKTOP;
    searchContent.prependTo('#header-desktop__search-panel');
    accountContent.prependTo('#account-menu__panel--desktop');
    menuContent.prependTo('.header-desktop__primary .region--primary-menu__content');
  }

  /**
   * Moves content from desktop header to mobile header.
   */
  function setMobileContext() {
    if (deviceContext === CONTEXT_MOBILE) {
      return;
    }
    deviceContext = CONTEXT_MOBILE;
    searchContent.prependTo('#mobile-panel--search');
    accountContent.prependTo('#mobile-panel--account');
    menuContent.prependTo('#mobile-panel--menu');
  }

  /**
   * Adds a media query listener to move content between
   * Mobile and Desktop headers.
   */
  function setupContentSwitching() {
    // Create a condition that targets viewports at least 768px wide
    const mediaQuery = window.matchMedia('(min-width: 992px)');
    // Since the content of the mobile menu differs from the desktop menu.
    // We need to track which items we actually want to move.
    menuContent = $('.header-desktop__primary .region--primary-menu__content > .main-menu');
    searchContent = $('#header-desktop__search-panel > *');
    accountContent = $('#account-menu__panel--desktop > *');

    function handleContextChange(e) {
      // Check if the media query is true
      if (e.matches) {
        // Then log the following message to the console
        setDesktopContext();
      }
      else {
        setMobileContext();
      }
    }

    // Register event listener
    mediaQuery.addListener(handleContextChange);

    // Initial check
    handleContextChange(mediaQuery);
  };

  $(document).ready(setupContentSwitching);
})(jQuery);
