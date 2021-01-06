// code to uncheck radio button (collapse an expanded submenu)
window.onload = function(){
  // check to see if menu exists (won't exist for customers or non-logged-in users)
  if (document.getElementById("slide-menu-toggle")) {
    const menuItems = document.querySelectorAll(".accordion INPUT[type='radio']");
    const slideMenuToggle = document.getElementById("slide-menu-toggle");
    let subMenuOpen = null;
    
    menuItems.forEach(function(menuItem){
      menuItem.addEventListener("click", openSlideMenu);
      menuItem.addEventListener("click", collapseSubmenu);
    })

    function openSlideMenu() {
      if (slideMenuToggle.checked) {
        slideMenuToggle.checked = false;
      }
    }

    function collapseSubmenu() {
      // if the clicked submenu is already open or if user clicked slideMenuToggle
      if (subMenuOpen == this || this.id == 'slide-menu-toggle') {
        subMenuOpen ? subMenuOpen.checked = false : null;
        subMenuOpen = null;
      } else {
        subMenuOpen = this; // assign the clicked submenu to the subMenuOpen var
      }
    }

    // close slide-menu and collapse any expanded
    slideMenuToggle.addEventListener("click", collapseSubmenu)
  }
}