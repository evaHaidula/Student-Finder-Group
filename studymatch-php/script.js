// StudyMatch - JavaScript used by all pages.
// Each section below does one job. Code only runs if the page has the matching element.

// ---------- 1. MOBILE MENU ----------
var menuButton = document.getElementById("menuButton");
var mainNav = document.getElementById("mainNav");

if (menuButton) {
    menuButton.addEventListener("click", function () {
        mainNav.classList.toggle("open");   // adds or removes the class "open"
    });
}

// ---------- 2. FORM VALIDATION ----------
// Shows a red message under a field
function showError(input, message) {
    var field = input.parentElement;
    var error = field.querySelector(".error");
    if (!error) {
        error = document.createElement("p");
        error.className = "error";
        field.appendChild(error);
    }
    error.textContent = message;
    input.classList.add("invalid");
}

// Removes the red message
function clearError(input) {
    var error = input.parentElement.querySelector(".error");
    if (error) {
        error.textContent = "";
    }
    input.classList.remove("invalid");
}

// Checks one input. Returns true if it is OK.
function checkInput(input) {
    var value = input.value.trim();
    clearError(input);

    if (input.required && value === "") {
        showError(input, "This field is required.");
        return false;
    }
    // Accepts name@nust.na and name@students.nust.na (PHP's is_nust_email() uses the same rule)
    if (input.dataset.check === "nust-email" && !/@([a-z0-9-]+\.)?nust\.na$/i.test(value)) {
        showError(input, "Use your NUST email (name@nust.na).");
        return false;
    }
    if (input.dataset.check === "password" && value.length < 8) {
        showError(input, "Password must be at least 8 characters.");
        return false;
    }
    if (input.dataset.check === "student-number" && !/^[0-9]{9}$/.test(value)) {
        showError(input, "Student number must be 9 digits.");
        return false;
    }
    if (input.dataset.check === "message" && value.length < 10) {
        showError(input, "Please write at least 10 characters.");
        return false;
    }
    return true;
}

// Every form with class "validate" is checked when it is submitted
var forms = document.querySelectorAll("form.validate");

forms.forEach(function (form) {
    form.addEventListener("submit", function (event) {
        var allOk = true;

        form.querySelectorAll("input, select, textarea").forEach(function (input) {
            if (!checkInput(input)) {
                allOk = false;
            }
        });

        if (!allOk) {
            event.preventDefault();           // stay on the page and show errors
            return;
        }

        // Forms connected to PHP have action="something.php": when the checks pass,
        // let the browser send the form to the server as normal. PHP checks everything again.
        if (form.getAttribute("action")) {
            return;
        }

        // Forms WITHOUT an action are still the old demo forms (no server yet).
        // Remove this part once every form has been connected to PHP.
        event.preventDefault();

        // The form has data-success="..." (message) and/or data-next="..." (page to open)
        if (form.dataset.success) {
            var box = form.querySelector(".message-ok");
            if (!box) {
                box = document.createElement("p");
                box.className = "message-ok";
                form.prepend(box);
            }
            box.textContent = form.dataset.success;
            form.reset();
        }
        if (form.dataset.next) {
            window.location.href = form.dataset.next;
        }
    });
});

// ---------- 3. ADMIN PAGE: TABS ----------
// Only switches which panel is visible. Approve / reject / delete are real PHP forms now.
var tabs = document.querySelectorAll(".tab");

tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
        tabs.forEach(function (t) { t.classList.remove("active"); });
        document.querySelectorAll(".tab-panel").forEach(function (p) { p.classList.remove("show"); });

        tab.classList.add("active");
        document.getElementById(tab.dataset.panel).classList.add("show");
    });
});
