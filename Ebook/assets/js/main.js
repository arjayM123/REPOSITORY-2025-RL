document.addEventListener("DOMContentLoaded", function () {
    const dropdowns = document.querySelectorAll(".dropdown");

    dropdowns.forEach(dropdown => {
        const toggleBtn = dropdown.querySelector("a"); // Select the main dropdown link
        const menu = dropdown.querySelector(".dropdown-menu");

        toggleBtn.addEventListener("click", function (event) {
            event.preventDefault(); // Prevent the default anchor behavior

            // Close all other dropdowns before opening this one
            document.querySelectorAll(".dropdown-menu").forEach(otherMenu => {
                if (otherMenu !== menu) {
                    otherMenu.classList.remove("show");
                }
            });

            // Toggle the visibility of the dropdown menu
            menu.classList.toggle("show");
        });
    });

    // Close dropdown if clicked outside
    document.addEventListener("click", function (event) {
        if (!event.target.closest(".dropdown")) {
            document.querySelectorAll(".dropdown-menu").forEach(menu => {
                menu.classList.remove("show");
            });
        }
    });
});
