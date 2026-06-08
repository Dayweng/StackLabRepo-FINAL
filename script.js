// ─────────────────────────────────────────────────────────
//  STACKLAB ACADEMY — script.js
//  Shared by: index.html (landing page) + dashboard.php
// ─────────────────────────────────────────────────────────

// #region LANDING PAGE (index.html only)

// Makes the top navbar get a frosted-glass background after scrolling 50px
function initNavbar() {
    var navbar = document.getElementById("navbar");
    if (!navbar) return; // not on the landing page, stop here

    function updateNavTheme() {
        var scrolled = window.scrollY > 50;
        navbar.classList.toggle("scrolled", scrolled);

        // Darken plain nav links when scrolled (they start white on the dark hero).
        // nav-cta-btn has a blue background so its text stays white always.
        navbar.querySelectorAll("a:not(.nav-cta-btn)").forEach(function(a) {
            a.style.color = scrolled ? "#475569" : "";
        });

        var brandName = navbar.querySelector(".brand-name");
        if (brandName) brandName.style.color = scrolled ? "#082B43" : "";

        var brandSub = navbar.querySelector(".font-nasa:not(.brand-name)");
        if (brandSub) brandSub.style.color = scrolled ? "#0B3C5D" : "";
    }

    window.addEventListener("scroll", updateNavTheme);
    updateNavTheme(); // run once on load so it's correct from the start
}

// Reveals elements with class .reveal when they scroll into view
function initScrollReveal() {
    var reveals = document.querySelectorAll(".reveal");
    if (!reveals.length) return;

    function checkReveal() {
        reveals.forEach(function(el) {
            // getBoundingClientRect().top = distance from element to top of screen
            // If it's within 90% of the screen height, show it
            if (el.getBoundingClientRect().top < window.innerHeight * 0.9) {
                el.classList.add("in-view");
            }
        });
    }

    window.addEventListener("scroll", checkReveal);
    checkReveal(); // also check on page load
}

// Puts the current year in the footer element with id="footerYear"
function initFooterYear() {
    var el = document.getElementById("footerYear");
    if (el) el.textContent = new Date().getFullYear();
}

// #endregion

// #region DASHBOARD

// Handles tab switching in the sidebar nav
function initDashboardNav() {
    var navItems = document.querySelectorAll(".nav-item");
    if (!navItems.length) return; // not on dashboard, stop here

    function activateTab(tab) {
        // Toggle the "active" highlight on nav items
        navItems.forEach(function(n) {
            n.classList.toggle("active", n.dataset.tab === tab);
        });

        // Hide all panels, then show the one matching the clicked tab
        document.querySelectorAll(".workspace-panel").forEach(function(p) {
            p.style.display = "none";
        });
        var target = document.getElementById("panel-" + tab);
        if (target) target.style.display = "";
    }

    // Attach click listener to every nav item
    navItems.forEach(function(item) {
        item.addEventListener("click", function() {
            activateTab(item.dataset.tab);
        });
    });

    // If the URL has ?tab=students (etc.), open that tab automatically
    // This happens after a form submit that redirects back here
    var urlParams = new URLSearchParams(window.location.search);
    var urlTab    = urlParams.get("tab");
    if (urlTab) activateTab(urlTab);
}

// Shows a success or error message on the Profile page
// Messages come from the URL: ?success=1 or ?error=wrongpass etc.
function initProfileFlash() {
    var msg = document.getElementById("profileMessage");
    if (!msg) return;

    var urlParams = new URLSearchParams(window.location.search);

    // Map of error codes → readable messages
    var errorMessages = {
        empty:       "Please fill in all fields.",
        mismatch:    "New passwords don't match.",
        short:       "Password must be at least 6 characters.",
        wrongpass:   "Current password is incorrect.",
        wrongdelete: "Incorrect password. Account not deleted.",
        filetype:    "Only JPG, PNG, GIF, or WebP images are allowed.",
        filesize:    "Image must be under 2MB.",
        upload:      "Upload failed. Please try again.",
    };

    // Map of success codes → readable messages
    var successMessages = {
        "1":      "Password updated successfully!",
        "avatar": "Profile picture updated!",
    };

    var errorCode   = urlParams.get("error");
    var successCode = urlParams.get("success");

    if (errorCode && errorMessages[errorCode]) {
        msg.textContent = errorMessages[errorCode];
        msg.className   = "flash-msg flash-msg--error visible";
    } else if (successCode && successMessages[successCode]) {
        msg.textContent = successMessages[successCode];
        msg.className   = "flash-msg flash-msg--success visible";
    }
}

// Wires up the "Delete Account" button to open its confirmation modal
function initDeleteModal() {
    var openBtn   = document.getElementById("deleteAccountBtn");
    var modal     = document.getElementById("deleteModal");
    var cancelBtn = document.getElementById("deleteModalCancel");
    var backdrop  = document.getElementById("deleteModalBackdrop");
    if (!openBtn || !modal) return;

    openBtn.addEventListener("click",   function() { modal.classList.add("open");    });
    cancelBtn.addEventListener("click", function() { modal.classList.remove("open"); });
    backdrop.addEventListener("click",  function() { modal.classList.remove("open"); });
}

// Shows today's date in the dashboard header
function initCurrentDate() {
    var el = document.getElementById("currentDate");
    if (!el) return;
    var now  = new Date();
    var opts = { weekday: "long", day: "numeric", month: "long" };
    el.textContent = "Today is " + now.toLocaleDateString("en-US", opts);
}

// Opens/closes the sidebar on mobile using the hamburger button
function initHamburger() {
    var btn     = document.getElementById("hamburgerBtn");
    var sidebar = document.getElementById("sidebar");
    var overlay = document.getElementById("sidebarOverlay");
    if (!btn || !sidebar) return;

    function openSidebar() {
        sidebar.classList.add("sidebar--open");
        if (overlay) overlay.classList.add("visible");
        btn.classList.add("open");
    }
    function closeSidebar() {
        sidebar.classList.remove("sidebar--open");
        if (overlay) overlay.classList.remove("visible");
        btn.classList.remove("open");
    }

    btn.addEventListener("click", function() {
        if (sidebar.classList.contains("sidebar--open")) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    if (overlay) overlay.addEventListener("click", closeSidebar);

    // Auto-close sidebar after tapping a nav item on mobile
    document.querySelectorAll(".nav-item").forEach(function(item) {
        item.addEventListener("click", function() {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });
}

// #endregion

// #region INIT — run everything once the page is fully loaded

document.addEventListener("DOMContentLoaded", function() {
    initNavbar();
    initScrollReveal();
    initFooterYear();
    initDashboardNav();
    initProfileFlash();
    initDeleteModal();
    initCurrentDate();
    initHamburger();
});

// #endregion
