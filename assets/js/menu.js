document.addEventListener('DOMContentLoaded', function () {
    var mobileMenu = document.getElementById('ast-hf-mobile-menu');

    if (!mobileMenu) {
        return;
    }

    var toggleButtons = mobileMenu.querySelectorAll('.ast-menu-toggle');

    toggleButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var menuItem = button.closest('li');
            if (!menuItem) {
                return;
            }

            var subMenu = menuItem.querySelector(':scope > .sub-menu');
            if (!subMenu) {
                return;
            }

            var isOpen = menuItem.classList.contains('menu-open');

            if (isOpen) {
                menuItem.classList.remove('menu-open', 'ast-submenu-expanded');
                button.setAttribute('aria-expanded', 'false');
                subMenu.style.display = 'none';
            } else {
                menuItem.classList.add('menu-open', 'ast-submenu-expanded');
                button.setAttribute('aria-expanded', 'true');
                subMenu.style.display = 'block';
            }
        });
    });

    /* прибираємо кліки по дубльованих toggle всередині посилання */
    var fakeToggles = mobileMenu.querySelectorAll('.menu-link .dropdown-menu-toggle, .menu-link .ast-header-navigation-arrow');

    fakeToggles.forEach(function (fakeToggle) {
        fakeToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var menuItem = fakeToggle.closest('li');
            if (!menuItem) {
                return;
            }

            var realButton = menuItem.querySelector(':scope > .ast-menu-toggle');
            if (realButton) {
                realButton.click();
            }
        });
    });
});