/*Sidebar Toggle */

/*User Icon to open Sidebar*/
const userIcon = document.getElementById("user-icon");
const menuContainer = document.getElementById("menu-container");
const blackSpace = document.getElementById("black-space");
const sideBarClose = document.getElementById("close-menu");
const userMenu = document.getElementById("user-menu");

const registerLink = document.getElementById("register");
const logInLink = document.getElementById("login-link");
const registerForm = document.getElementById("register-form");
const loginForm = document.getElementById("login");
const navMenu = document.querySelector(".nav-menu");

const menuBar = document.getElementById("menu-bar");
const support = document.getElementById("support");
const supportContent = document.getElementById("support-content");
const supportChevron = document.getElementById("support-chevron");

menuBar.addEventListener("click", () => {
    menuContainer.style.pointerEvents = "auto";
    userMenu.style.transform = "translateX(0)";
    userMenu.style.animation = "slideIn 1s ease 1 normal forwards";
    navMenu.style.display = "flex";
    userMenu.classList.add("compact");
    userMenu.classList.remove("normal");
});

userIcon.addEventListener("click", () => {
    menuContainer.style.pointerEvents = "auto";
    userMenu.style.transform = "translateX(0)";
    userMenu.style.animation = "slideIn 1s ease 1 normal forwards";
    loginForm.style.display = "flex";
    userMenu.classList.remove("compact");
    userMenu.classList.add("normal");
});

blackSpace.addEventListener("click", () => {
    menuContainer.style.pointerEvents = "none";
    userMenu.style.transform = "translateX(100%)";
    userMenu.style.animation = "slideOut 1s ease 1 normal forwards";
    loginForm.style.display = "none";
    registerForm.style.display = "none";
    navMenu.style.display = "none";
});

sideBarClose.addEventListener("click", () => {
    menuContainer.style.pointerEvents = "none";
    userMenu.style.transform = "translateX(100%)";
    userMenu.style.animation = "slideOut 1s ease 1 normal forwards";
    loginForm.style.display = "none";
    registerForm.style.display = "none";
    userMenu.querySelector(".nav-menu").style.display = "none";
});

/*Login to Register Toggle*/

registerLink.addEventListener("click", () => {
    loginForm.style.display = "none";
    registerForm.style.display = "flex";
});

/*Register to Login toggle */
logInLink.addEventListener("click", () => {
    registerForm.style.display = "none";
    loginForm.style.display = "flex";
});

/*Navbar Offset on Scroll*/
const navbar = document.querySelector('.navbar');
const navTop = navbar.offsetTop;

window.addEventListener('scroll', () => {
    if (window.scrollY > navTop) {
        navbar.classList.add('glass');
        console.log("sroll done")
    } else {
        navbar.classList.remove('glass');
        console.log("not scrolling")
    }
});


/*Navbar Support Dropdown Toggle*/
support.addEventListener("click", () => {
    if (supportContent.style.display === "flex") {
        // play closing animation
        supportContent.style.animation = "slideDown 0.5s ease forwards";

        // delay hiding until animation ends
        setTimeout(() => {
            supportContent.style.display = "none";
        }, 500); // matches your animation duration
    } else {
        // show + play opening animation
        supportContent.style.display = "flex";
        supportContent.style.animation = "slideUp 0.5s ease forwards";
    }
});

support.addEventListener("click", () => {
    if (supportChevron.style.transform === "rotate(180deg)") {
        supportChevron.style.transform = "rotate(0deg)";
        supportChevron.style.transition = "transform 0.3s ease";
    } else {
        supportChevron.style.transform = "rotate(180deg)";
    }
});

/*Sidebar Toggle End*/

/* Category Dropdown Menu */
const dropdown = document.querySelector(".dropdown");
const categoryDropdown = document.querySelector(".category-dropdown");
const categoryContainer = document.querySelector(".category-container");

let isOpen = false;

function openMenu() {
    isOpen = true;
    categoryDropdown.classList.add("show");
}

function closeMenu() {
    isOpen = false;
    categoryDropdown.classList.remove("show");
}

dropdown.addEventListener("mouseenter", openMenu);

categoryDropdown.addEventListener("mouseenter", () => {
    if (isOpen) openMenu();
});

categoryContainer.addEventListener("mouseenter", () => {
    if (isOpen) openMenu();
});

dropdown.addEventListener("mouseleave", () => {
    setTimeout(() => {
        if (
            !categoryDropdown.matches(":hover") &&
            !categoryContainer.matches(":hover")
        ) {
            closeMenu();
        }
    }, 100); // delay prevents flicker when moving from dropdown to menu
});

categoryDropdown.addEventListener("mouseleave", () => {
    setTimeout(() => {
        if (!dropdown.matches(":hover") && !categoryContainer.matches(":hover")) {
            closeMenu();
        }
    }, 100);
});

categoryContainer.addEventListener("mouseleave", () => {
    setTimeout(() => {
        if (!dropdown.matches(":hover") && !categoryDropdown.matches(":hover")) {
            closeMenu();
        }
    }, 100);
});

/* End of Category Dropdown Menu */

/*Sub-Category Dropdown Menu*/
/*Dropdown Margin alignment */
const catItems = document.querySelectorAll(
    ".category-dropdown > .dropdown-container",
);
const total = catItems.length;
const mid = Math.floor(total / 2);

catItems.forEach((item, i) => {
    if (i < mid) item.dataset.pos = "left";
    else if (i > mid) item.dataset.pos = "right";
    else item.dataset.pos = "center";
});

/*Dropdown toggle*/
const subMenus = document.querySelectorAll(".dropdown-container");

subMenus.forEach((menu) => {
    const trigger = menu.querySelector(".dropdown");
    const content = menu.querySelector(".dropdown-content");

    if (!trigger || !content) return;

    let open = false;

    function openSub() {
        open = true;
        content.classList.add("show");
    }

    function closeSub() {
        open = false;
        content.classList.remove("show");
    }

    trigger.addEventListener("mouseenter", openSub);

    menu.addEventListener("mouseleave", () => {
        setTimeout(() => {
            if (!menu.matches(":hover")) closeSub();
        }, 120);
    });

    content.addEventListener("mouseenter", openSub);
});
/*Sub-Category Dropdown Menu End*/

/* ===============================
        Search Bar Functionality
   =============================== */

// Loop through ALL search bars (navbar + menu)
document
    .querySelectorAll(".search-bar-container")
    .forEach((searchContainer) => {
        // Core elements
        const searchInput = searchContainer.querySelector("input");
        const resetBtn = searchContainer.querySelector(".resetbtn");
        const resultsBox = searchContainer.querySelector(".search-bar-content");

        // Safety check (in case markup changes)
        if (!searchInput) return;

        // Loading elements
        const loadingScreen = searchContainer.querySelector("#loading-screen");
        const loadingVideo = loadingScreen
            ? loadingScreen.querySelector("video")
            : null;

        /* -------------------------------
            Update Search State
           ------------------------------- */
        function updateState() {
            const query = searchInput.value.trim();

            if (query !== "") {
                // Show result container
                searchContainer.classList.add("show-results");

                // Show loading screen
                if (loadingScreen) {
                    loadingScreen.style.display = "flex";

                    // Restart loading animation
                    if (loadingVideo) {
                        loadingVideo.currentTime = 0;
                        loadingVideo.play();
                    }
                }

                // Hide results while loading
                const result = searchContainer.querySelector(".search-result");
                const viewAll = searchContainer.querySelector(".view-all");
                const hr = searchContainer.querySelector("hr");

                if (result) result.style.display = "none";
                if (viewAll) viewAll.style.display = "none";
                if (hr) hr.style.display = "none";

                // Simulate backend delay
                setTimeout(() => {
                    // Hide loading
                    if (loadingScreen) loadingScreen.style.display = "none";
                    if (loadingVideo) loadingVideo.pause();

                    // Show results
                    if (result) result.style.display = "grid";
                    if (viewAll) viewAll.style.display = "flex";
                    if (hr) hr.style.display = "block";
                }, 2500);
            } else {
                // No input → hide everything
                searchContainer.classList.remove("show-results");
            }
        }

        /* -------------------------------
           Input Events
           ------------------------------- */

        // When input is focused
        searchInput.addEventListener("focus", () => {
            searchContainer.classList.add("active");
            updateState();
        });

        // When user types
        searchInput.addEventListener("input", updateState);

        /* -------------------------------
           Reset Button (X)
           ------------------------------- */
        if (resetBtn) {
            resetBtn.addEventListener("click", () => {
                searchInput.value = "";
                searchContainer.classList.remove("active", "show-results");

                // Hide loading if visible
                if (loadingScreen) {
                    loadingScreen.style.display = "none";
                    if (loadingVideo) loadingVideo.pause();
                }
            });
        }

        /* -------------------------------
           Click Outside to Close
           ------------------------------- */
        document.addEventListener("click", (e) => {
            if (!searchContainer.contains(e.target)) {
                searchContainer.classList.remove("active", "show-results");

                if (loadingScreen) {
                    loadingScreen.style.display = "none";
                    if (loadingVideo) loadingVideo.pause();
                }
            }
        });
    });

/* End of Search Bar Functionality */

/*Password Eye toggle */
const passwords = document.querySelectorAll(".password");
const toggles = document.querySelectorAll(".togglePassword");

toggles.forEach((toggle, index) => {
    const eye = toggle.querySelector(".fa-eye");
    const eyeSlash = toggle.querySelector(".fa-eye-slash");
    const input = passwords[index];

    toggle.addEventListener("click", () => {
        if (input.type === "password") {
            input.type = "text";
            eye.style.display = "none";
            eyeSlash.style.display = "inline-block";
        } else {
            input.type = "password";
            eye.style.display = "inline-block";
            eyeSlash.style.display = "none";
        }
    });
});

/* End of Password Eye toggle */

/* Number Format */

document.querySelectorAll('.price-format').forEach(price => {
    const number = parseInt(price.textContent.replace(/,/g, ''), 10);
    price.textContent = number.toLocaleString('en-US');
});
