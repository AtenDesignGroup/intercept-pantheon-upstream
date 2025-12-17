/**
 * Accessible Menu
 * This file is based on content that is licensed according to the W3C Software License at
 * https://www.w3.org/copyright/software-license/
 */

(function (Drupal, once) {
  /**
   * This class manages the functionality of the main menu in an accessible navigation menu.
   * It is used for keyboard navigation and focus management within the menu.
   *
   * @property {HTMLElement} menuElement - The menu element associated with the menu.
   * @property {MenuButton[]} menuButtons - An array of `MenuButton` instances associated with the menu.
   * @property {MenuLink[]} menuLinks - An array of `MenuLink` instances associated with the menu.
   *
   * The constructor initializes the menu with the following:
   * - Initializes the menu buttons and links within the menu.
   * - Sets the tabindex of the first focusable element to '0' and the rest to '-1'.
   * - Adds an event listener to the body to close all menus when clicking outside of the menu.
   * - Adds an event listener to the menu to close all menus when focused away from the menu.
   */
  class Menu {
    constructor(menuElement) {
      const menu = this;

      this.menuElement = menuElement;
      this.menuElement.Menu = this;

      // Initialize the menu buttons.
      this.menuButtons = once('menuControlButton', '.js-main-menu__tab', menuElement)
        .map((button) => {
          return new MenuButton(button, menu);
        });

      this.menuLinks = once('menuControlLink', 'a', menuElement)
        .map((link) => {
          return new MenuLink(link, menu);
        });

      // Only allow the first focusable element to be focused.
      once('menuIsFocusable', 'a, button', menuElement).forEach((element, index) => {
        element.setAttribute('tabindex', index === 0 ? '0' : '-1');
      });

      // Add event listener to the body to close all menus when clicking outside of the menu.
      once('menuBackgroundClick', document.body).forEach((body) => {
        body.addEventListener('mousedown', Menu.onBackgroundMousedown);
      });

      once('menuFocusOut', '.main-menu', menuElement).forEach((element) => {
        element.addEventListener('focusout', this.onFocusOut.bind(this));
      });
    }

    /**
     * Close all open menus.
     *
     * @returns void
     */
    closeAll() {
      this.menuButtons.forEach((button) => {
        button.closePopup();
      });
    }

    /**
     * Get the first menu item in the menu.
     *
     * @returns HTMLElement
     */
    getFirstMenuItem() {
      return this.menuElement.querySelector(':scope > li');
    }

    /**
     * Get the last menu item in the menu.
     *
     * @returns HTMLElement
     */
    getLastMenuItem() {
      return Array.from(this.menuElement.querySelectorAll(':scope > li')).pop();
    }

    /**
     * Find the next li in a menu.
     *
     * This is used to traverse menu links.
     *
     * @param {Element} menuItem
     *
     * @returns Element
     */
    getNextMenuItem(menuItem) {
      const rootMenuItem = menuItem.closest('li.main-menu');
      const menuItems = [
        rootMenuItem,
        ...Array.from(rootMenuItem.querySelectorAll('li:has(> a, > button)'))
          .filter((item) => item.checkVisibility())
      ];

      const index = menuItems.indexOf(menuItem);

      return index + 1 < menuItems.length
        ? menuItems[index + 1]
        : menuItems[0];
    }

    /**
     * Find the next top level li in a menu.
     *
     * This is used to traverse top level menu links
     * with the left and right arrow keys.
     *
     * @param {Element} menuItem
     *
     * @returns Element
     */
    getNextMenu(menuItem) {
      const rootMenuItem = menuItem.matches('li.main-menu')
        ? menuItem
        : menuItem.closest('li.main-menu');

      const menus = Array.from(this.menuElement.querySelectorAll('li.main-menu'));
      const index = menus.indexOf(rootMenuItem);

      return index + 1 < menus.length
        ? menus[index + 1]
        : menus[0];
    };

    /**
     * Find the previous li in a menu.
     *
     * This is used to traverse menu links.
     *
     * @param {Element} menuItem
     *
     * @returns Element
     */
    getPreviousMenuItem(menuItem) {
      const rootMenuItem = menuItem.closest('li.main-menu');
      const menuItems = [
        rootMenuItem,
        ...Array.from(rootMenuItem.querySelectorAll('li:has(> a, > button)'))
          .filter((item) => item.checkVisibility())
      ];

      const index = menuItems.indexOf(menuItem);

      // Return the last menu item if we're at the beginning.
      return index - 1 >= 0
        ? menuItems[index - 1]
        : menuItems[menuItems.length - 1];
    }

    /**
     * Find the previous top level li in a menu.
     *
     * This is used to traverse top level menu links
     * with the left and right arrow keys.
     *
     * @param {Element} menuItem
     *
     * @returns Element
     */
    getPreviousMenu(menuItem) {
      const rootMenuItem = menuItem.matches('li.main-menu')
        ? menuItem
        : menuItem.closest('li.main-menu');

      const menus = Array.from(this.menuElement.querySelectorAll('li.main-menu'));
      const index = menus.indexOf(rootMenuItem);

      // Return the last menu item if we're at the beginning.
      return index - 1 >= 0
        ? menus[index - 1]
        : menus[menus.length - 1];
    }

    /**
     * Handles the mousedown event on the background.
     *
     * @param {MouseEvent} event - The mousedown event.
     *
     * This method checks if the mousedown event occurred outside the menu container.
     * If it did and the menu is open and the device is not mobile, it sets focus to the button node and closes the popup menu.
     */
    static onBackgroundMousedown(event) {
      const outerMenu = event.target.closest('.js-main-menu, .js-mobile-panel');

      // If there is no outer menu, close all menus
      if (!outerMenu) {
        const buttons = Array.from(document.querySelectorAll('.js-main-menu__tab'));
        buttons.forEach((button) => {
          if (button.MenuButton.isOpen()) {
            button.MenuButton.closePopup();
          }
        });
      }
    }

    /**
     * Close menu when focused away from the menu.
     *
     * @param {FocusEvent} event
     */
    onFocusOut(event) {
      // Close all menus if the target we focused away from is not the
      // a child of the menu item this event is attached to.
      if (!event.currentTarget.contains(event.relatedTarget)) {
        this.closeAll();
      }
    }
  }

  /**
   * `MenuButton` is a class that manages the functionality of top level menu buttons
   * in an accessible navigation menu.
   *
   * @property {HTMLElement} buttonNode - The button element associated with the menu.
   * @property {Menu} parentMenu - The parent `Menu` instance this button belongs to.
   *
   * The constructor initializes the button with the following:
   * - Sets the `aria-expanded` attribute to 'false' to indicate the menu is closed by default.
   * - Attaches event listeners for keydown and click events to handle user interactions.
   *
   * The class provides methods to:
   * - Open and close the associated menu.
   * - Check if the menu is currently open.
   * - Manage focus for accessibility, including focusing on the first or last menu item.
   * - Handle keyboard navigation (e.g., arrow keys, Escape) and click events for menu interaction.
   * - Close all other open menus within the same parent menu for better accessibility
   *   and user experience.
   */
  class MenuButton {
    constructor(buttonNode, menu) {

      this.parentMenu = menu;
      this.buttonNode = buttonNode;
      this.buttonNode.MenuButton = this;
      this.buttonNode.setAttribute('aria-expanded', 'false');

      // Attach event listeners to the main menu button
      this.buttonNode.addEventListener(
        'keydown',
        this.onButtonKeydown.bind(this)
      );

      this.buttonNode.addEventListener(
        'click',
        this.onButtonClick.bind(this)
      );
    }

    /**
     * Returns the menu element associated with the button.
     *
     * @returns {HTMLElement} - The menu element associated with the button.
     */
    getMenu() {
      return this.buttonNode.nextElementSibling
        .querySelector('ul')
        .Menu;
    }

    /**
     * Returns the parent menu element associated with the button.
     *
     * @returns {HTMLElement} - The parent menu element associated with the button.
     */
    getParentMenu() {
      return this.buttonNode.closest('ul.menu');
    };

    /**
     * Returns the menu list item associated with the button.
     *
     * @returns {HTMLElement} - The menu item associated with the button.
     */
    getMenuItem() {
      return this.buttonNode.closest('li.main-menu');
    }

    /**
     * Focuses on the first menu item of the nested list related to the given element.
     *
     * @param {HTMLElement} element - The element related to the nested list.
     *
     * This method finds the nested list related to the given element by using the `data-menu-controls` attribute.
     * If the nested list exists, it finds the first menu item in the list and focuses on it.
     * If the first menu item is not a button or link, it recursively calls itself to focus on the first menu item within the nested menu.
     */
    focusFirstItem(element) {
      const nestedList = document.getElementById(
        element.getAttribute('data-menu-controls')
      );

      if (nestedList) {
        const firstItem = nestedList.querySelector('.menu__link');
        // If the first item is not a button, it is a default menu so move to the first menu item within.
        if (firstItem.tagName !== 'BUTTON' && firstItem.tagName !== 'A') {
          this.focusFirstItem(firstItem);
        } else {
          firstItem.focus();
        }
      }
    }

    /**
     * Handles keydown events on the menu button.
     *
     * @param {Event} event - The keydown event.
     *
     * This method checks the key pressed during the event and performs actions based on the key:
     * - 'Up' or 'ArrowUp': Calls the `handleUpArrow` method with the button node.
     * - 'Down' or 'ArrowDown': If the next sibling of the button node has a 'data-depth' attribute of '1', opens the popup, focuses on the first menu item, and prevents the default action. Otherwise, calls the `handleDownArrow` method with the button node and prevents the default action.
     * - 'Left' or 'ArrowLeft': If the next sibling of the button node does not have a 'data-depth' attribute of '1', closes the popup and focuses on the button node. Otherwise, calls the `handleLeftArrow` method with the button node.
     * - 'Right' or 'ArrowRight': If the next sibling of the button node does not have a 'data-depth' attribute of '1', opens the popup and focuses on the first menu item. Otherwise, calls the `handleRightArrow` method with the button node.
     * - 'Esc' or 'Escape': Closes the popup and prevents the default action.
     *
     * If the 'ctrl', 'alt', or 'meta' key is pressed during the event, the method returns without doing anything.
     */
    onButtonKeydown(event) {
      const key = event.key;
      if (event.ctrlKey || event.altKey || event.metaKey) {
        return;
      }

      switch (key) {
        case 'Up':
        case 'ArrowUp':
          this.openPopup();
          this.getMenu()
            .getLastMenuItem()
              ?.querySelector('a, button')
              ?.focus();
          event.preventDefault();

          break;

        case 'Down':
        case 'ArrowDown':
          this.openPopup();
          this.getMenu()
            .getFirstMenuItem()
              ?.querySelector('a, button')
              ?.focus();
          event.preventDefault();

          break;

        case 'Left':
        case 'ArrowLeft':
          this.closePopup();
          this.parentMenu.getPreviousMenu(this.getMenuItem())
            ?.querySelector('button')
            ?.focus();

          break;

        case 'Right':
        case 'ArrowRight':
          this.closePopup();
          this.parentMenu.getNextMenu(this.getMenuItem())
            ?.querySelector('button')
            ?.focus();

          break;

        case 'Esc':
        case 'Escape':
          this.closePopup();
          event.preventDefault();
          break;
      }
    }

    /**
     * Handles click events on the menu button.
     *
     * @param {Event} event - The click event.
     *
     * This method checks if the menu is open:
     * - If the menu is open, it calls the `closePopup` method to close the menu.
     * - If the menu is not open, it calls the `openPopup` method to open the menu.
     *
     * After handling the menu, it stops the propagation of the event and prevents the default action.
     */
    onButtonClick(event) {
      if (this.isOpen()) {
        this.closePopup();
      } else {
        this.openPopup();
        // Only close other buttons if not on mobile
        if (!Drupal.behaviors.menuControl.mobileMediaQuery.matches) {
          this.closeAll();
        }
      }

      event.stopPropagation();
      event.preventDefault();
    }

    /**
     * Checks if the menu is open.
     *
     * @returns {boolean} - Returns true if the menu is open, false otherwise.
     *
     * This method checks the 'aria-expanded' attribute of the button node.
     * If the attribute is 'true', the method returns true, indicating that the menu is open.
     * If the attribute is not 'true', the method returns false, indicating that the menu is not open.
     */
    isOpen() {
      return this.buttonNode.getAttribute('aria-expanded') === 'true';
    }

    /**
     * Opens the popup menu.
     *
     * This method sets the 'aria-expanded' attribute of the button node to 'true',
     * indicating that the associated popup menu is open.
     */
    openPopup() {
      this.buttonNode.setAttribute('aria-expanded', 'true');
    }

    /**
     * Closes the popup menu.
     *
     * This method sets the 'aria-expanded' attribute of the button node to 'false',
     * indicating that the associated popup menu is closed.
     */
    closePopup() {
      this.buttonNode.setAttribute('aria-expanded', 'false');
    }

    /**
     * Closes all expanded buttons within the menu, with specific behavior based on the menu's depth.
     * - If the menu is at the top level (depth 0), it closes all top-level buttons except for `this.buttonNode`.
     * - For menus not at the top level, it ensures that only one button can be open at a time by closing all other buttons except for `this.buttonNode`.
     * This method is part of the menu management functionality, allowing for better accessibility and user experience by managing the expanded state of menu buttons.
     */
    closeAll() {

      //  Only allow one button to be open at a time inside a menu
      Array.from(this.parentMenu.menuElement.querySelectorAll('button[aria-expanded="true"]'))
        .filter((button) => button !== this.buttonNode)
        .forEach((button) => {
          button.setAttribute('aria-expanded', 'false');
        });
    }
  }

  /**
   * `MenuLink` is a class that manages the functionality of menu links in an accessible navigation menu.
   * It is used for keyboard navigation and focus management within the menu.
   *
   * @property {HTMLElement} linkElement - The link element associated with the menu.
   * @property {Menu} parentMenu - The parent `Menu` instance this link belongs to.
   * @property {Menu} rootMenu - The root `Menu` instance this link belongs to.
   *
   * The constructor initializes the link with the following:
   * - Attaches an event listener for keydown events to handle user interactions.
   */
  class MenuLink {
    constructor(linkElement, menu) {
      this.linkElement = linkElement;
      this.parentMenu = menu;

      this.rootMenu = this.getRootMenu();

      this.linkElement.addEventListener('keydown', this.onLinkKeydown.bind(this));
    }

    /**
     * Returns the root `Menu` instance associated with the link.
     *
     * @returns {Menu} - The root `Menu` instance associated with the link.
     */
    getRootMenu() {
      return this.linkElement.closest('.js-main-menu').Menu;
    }

    /**
     * Handles keydown events on the menu button.
     *
     * @param {Event} event - The keydown event.
     *
     * This method checks the key pressed during the event and performs actions based on the key:
     * - 'Up' or 'ArrowUp': Calls the `handleUpArrow` method with the button node.
     * - 'Down' or 'ArrowDown': If the next sibling of the button node has a 'data-depth' attribute of '1', opens the popup, focuses on the first menu item, and prevents the default action. Otherwise, calls the `handleDownArrow` method with the button node and prevents the default action.
     * - 'Left' or 'ArrowLeft': If the next sibling of the button node does not have a 'data-depth' attribute of '1', closes the popup and focuses on the button node. Otherwise, calls the `handleLeftArrow` method with the button node.
     * - 'Right' or 'ArrowRight': If the next sibling of the button node does not have a 'data-depth' attribute of '1', opens the popup and focuses on the first menu item. Otherwise, calls the `handleRightArrow` method with the button node.
     * - 'Esc' or 'Escape': Closes the popup and prevents the default action.
     *
     * If the 'ctrl', 'alt', or 'meta' key is pressed during the event, the method returns without doing anything.
     */
    onLinkKeydown(event) {
      const key = event.key;
      if (event.ctrlKey || event.altKey || event.metaKey) {
        return;
      }

      switch (key) {
        case 'Up':
        case 'ArrowUp':
          this.parentMenu.getPreviousMenuItem(this.getMenuItem())
            ?.querySelector('a, button')
            ?.focus();

          event.preventDefault();

          break;

        case 'Down':
        case 'ArrowDown':
          this.parentMenu.getNextMenuItem(this.getMenuItem())
            ?.querySelector('a, button')
            ?.focus();

          event.preventDefault();

          break;

        case 'Left':
        case 'ArrowLeft':
          this.rootMenu.closeAll();
          const prevFocusable = this.rootMenu.getPreviousMenu(this.linkElement.closest('li.main-menu'))
            ?.querySelector('a, button');

          if (!prevFocusable) {
            return;
          }

          prevFocusable.focus();

          if (prevFocusable.MenuButton) {
            prevFocusable.MenuButton.closeAll();
          }

          break;

        case 'Right':
        case 'ArrowRight':
          this.rootMenu.closeAll();
          let nextFocusable = this.rootMenu.getNextMenu(this.linkElement.closest('li.main-menu'))
            ?.querySelector('a, button')
            ?.focus();

          if (!nextFocusable) {
            return;
          }

          nextFocusable.focus();

          break;

        case 'Esc':
        case 'Escape':
          const menuButton = this.linkElement.closest('li.main-menu')
            ?.querySelector('button')
            .MenuButton

          if (menuButton) {
            menuButton.closePopup();
            menuButton.buttonNode.focus();
          }
          event.preventDefault();
          break;
      }
    }

    /**
     * Get the parent list item elment.
     *
     * @returns HTMLElement
     */
    getMenuItem() {
      return this.linkElement.closest('li');
    }

  }

  /**
   * menuControl Drupal behavior.
   *
   * This behavior initializes the main menu and its buttons and links.
   */
  Drupal.behaviors.menuControl = {
    attach: function (context) {
      once('menuControl', '.js-main-menu, .js-main-menu ul', context).forEach((menuContainer) => {
        let mobileBreakpoint = 768;

        if (menuContainer.hasAttribute('data-breakpoint')) {
          mobileBreakpoint = menuContainer
            .getAttribute('data-breakpoint')
            .replace('#', '');
        }

        // Mobile Media Query
        Drupal.behaviors.menuControl.mobileMediaQuery = window.matchMedia(
          '(max-width: ' + mobileBreakpoint + 'px)'
        );

        /**
         * Initializes the menus within a given container.
         *
         * @param {HTMLElement} menuContainer - The container within which to initialize menus.
         *
         * This function selects all button elements within the menuContainer and
         * initializes a new MenuButton instance for each.
         * It then selects all elements with the class 'menu__item' within the
         * menuContainer. For each 'menu__item' that does not contain a button,
         * it initializes a new MenuLinks instance.
         */
        function initializeMenus(menuContainer) {
          new Menu(menuContainer);
        }

        // Call the function with the menuContainer as argument
        initializeMenus(menuContainer);
      });
    },
  };
})(Drupal, once);