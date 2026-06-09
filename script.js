//─────────────────────────────────────────────────────────
//STACKLAB ACADEMY — script.js
//─────────────────────────────────────────────────────────

//#region LANDING PAGE (index.html only)

function initNavbar() {
    var navbar = document.getElementById("navbar");
    if (!navbar) return;

    function updateNavTheme() {
        var scrolled = window.scrollY > 50;
        navbar.classList.toggle("scrolled", scrolled);

        navbar.querySelectorAll("a:not(.nav-cta-btn)").forEach(function(a) {
            a.style.color = scrolled ? "#475569" : "";
        });

        var brandName = navbar.querySelector(".brand-name");
        if (brandName) brandName.style.color = scrolled ? "#082B43" : "";

        var brandSub = navbar.querySelector(".font-nasa:not(.brand-name)");
        if (brandSub) brandSub.style.color = scrolled ? "#0B3C5D" : "";
    }

    window.addEventListener("scroll", updateNavTheme);
    updateNavTheme();
}

function initScrollReveal() {
    var reveals = document.querySelectorAll(".reveal");
    if (!reveals.length) return;

    function checkReveal() {
        reveals.forEach(function(el) {
            if (el.getBoundingClientRect().top < window.innerHeight * 0.9) {
                el.classList.add("in-view");
            }
        });
    }

    window.addEventListener("scroll", checkReveal);
    checkReveal();
}

function initFooterYear() {
    var el = document.getElementById("footerYear");
    if (el) el.textContent = new Date().getFullYear();
}

//#endregion

//#region DASHBOARD

function initDashboardNav() {
    var navItems = document.querySelectorAll(".nav-item");
    if (!navItems.length) return;

    function activateTab(tab) {
        navItems.forEach(function(n) {
            n.classList.toggle("active", n.dataset.tab === tab);
        });

        document.querySelectorAll(".workspace-panel").forEach(function(p) {
            p.style.display = "none";
        });
        var target = document.getElementById("panel-" + tab);
        if (target) target.style.display = "";
    }

    navItems.forEach(function(item) {
        item.addEventListener("click", function() {
            activateTab(item.dataset.tab);
        });
    });

    var urlParams = new URLSearchParams(window.location.search);
    var urlTab    = urlParams.get("tab");
    if (urlTab) activateTab(urlTab);
}

function initProfileFlash() {
    var msg = document.getElementById("profileMessage");
    if (!msg) return;

    var urlParams = new URLSearchParams(window.location.search);

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

function initCurrentDate() {
    var el = document.getElementById("currentDate");
    if (!el) return;
    var now  = new Date();
    var opts = { weekday: "long", day: "numeric", month: "long" };
    el.textContent = "Today is " + now.toLocaleDateString("en-US", opts);
}

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

    document.querySelectorAll(".nav-item").forEach(function(item) {
        item.addEventListener("click", function() {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });
}

//#endregion

//#region INIT — run everything once the page is fully loaded

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

//#endregion
