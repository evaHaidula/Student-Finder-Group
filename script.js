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
    if (input.dataset.check === "nust-email" && !value.endsWith("@nust.na")) {
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
        event.preventDefault();               // stop the page from reloading
        var allOk = true;

        form.querySelectorAll("input, select, textarea").forEach(function (input) {
            if (!checkInput(input)) {
                allOk = false;
            }
        });

        if (!allOk) {
            return;                           // stay on the page and show errors
        }

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

// ---------- 3. BROWSE PAGE: FILTER + SELECTED GROUP PANEL ----------
var filterForm = document.getElementById("filterForm");

if (filterForm) {
    var cards = document.querySelectorAll(".group-card");

    // Show only the cards that match the filters
    function applyFilters() {
        var module = document.getElementById("fModule").value.trim().toLowerCase();
        var day = document.getElementById("fDay").value;
        var format = document.getElementById("fFormat").value;
        var shown = 0;

        cards.forEach(function (card) {
            var matches = true;
            if (module !== "" && card.dataset.module.toLowerCase().indexOf(module) === -1) matches = false;
            if (day !== "" && card.dataset.day !== day) matches = false;
            if (format !== "" && card.dataset.format !== format) matches = false;

            card.style.display = matches ? "block" : "none";
            if (matches) shown++;
        });

        document.getElementById("resultCount").textContent = shown + " RESULTS · BEST MATCH FIRST";
    }

    filterForm.addEventListener("submit", function (event) {
        event.preventDefault();
        applyFilters();
    });

    document.getElementById("clearFilters").addEventListener("click", function () {
        filterForm.reset();
        applyFilters();
    });

    // Clicking a card fills the "Selected group" panel
    cards.forEach(function (card) {
        card.addEventListener("click", function () {
            cards.forEach(function (c) { c.classList.remove("selected"); });
            card.classList.add("selected");

            document.getElementById("selName").textContent = card.dataset.name;
            document.getElementById("selMatch").textContent = card.dataset.match + "% MATCH";
            document.getElementById("selDesc").textContent = card.dataset.desc;
            document.getElementById("selWhen").textContent = card.dataset.when;
            document.getElementById("selWhere").textContent = card.dataset.where;
            document.getElementById("selMembers").textContent = card.dataset.members;
            document.getElementById("selGoal").textContent = "Goal: " + card.dataset.goal;
        });
    });
}

// ---------- 4. GROUP DETAILS PAGE: REQUEST TO JOIN ----------
var joinButtons = document.querySelectorAll(".join-button");

joinButtons.forEach(function (button) {
    button.addEventListener("click", function () {
        joinButtons.forEach(function (b) {
            b.textContent = "Request sent";
            b.disabled = true;
        });
        var note = document.getElementById("joinNote");
        if (note) {
            note.textContent = "Your request was sent. The host usually replies within 24 hours.";
        }
    });
});

var reportButton = document.getElementById("reportButton");
if (reportButton) {
    reportButton.addEventListener("click", function () {
        var reason = prompt("Why are you reporting this group?");
        if (reason) {
            alert("Thank you. A moderator will review your report.");
        }
    });
}

// ---------- 5. ADMIN PAGE: TABS AND ROW BUTTONS ----------
var tabs = document.querySelectorAll(".tab");

tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
        tabs.forEach(function (t) { t.classList.remove("active"); });
        document.querySelectorAll(".tab-panel").forEach(function (p) { p.classList.remove("show"); });

        tab.classList.add("active");
        document.getElementById(tab.dataset.panel).classList.add("show");
    });
});

// Update the numbers at the top of the admin page
function updateCounts() {
    var pending = document.querySelectorAll("#pendingTable tbody tr").length;
    var reports = document.querySelectorAll("#reportsTable tbody tr").length;
    var groups = document.querySelectorAll("#groupsTable tbody tr").length;
    var modules = document.querySelectorAll("#modulesTable tbody tr").length;

    var set = function (id, value) {
        var element = document.getElementById(id);
        if (element) element.textContent = value;
    };
    set("countPending", pending);
    set("countReports", reports);
    set("countGroups", groups);
    set("countModules", modules);
    set("pendingTitle", "Pending approvals · " + pending);
}

// One click listener handles every Approve / Reject / Delete / Resolve button,
// even for rows that are added later.
document.addEventListener("click", function (event) {
    var button = event.target.closest("[data-action]");
    if (!button) return;

    var row = button.closest("tr");
    var action = button.dataset.action;

    if (action === "delete" && !confirm("Delete this item?")) return;

    if (action === "approve") {
        // Move the approved group into the "All groups" table
        var groupsBody = document.querySelector("#groupsTable tbody");
        if (groupsBody) {
            var copy = row.cloneNode(true);
            copy.children[1].textContent = "Approved";
            copy.children[2].innerHTML = '<button class="btn btn-red btn-small" data-action="delete">Delete</button>';
            groupsBody.appendChild(copy);
        }
    }
    row.remove();
    updateCounts();
});

// Add a module
var moduleForm = document.getElementById("moduleForm");
if (moduleForm) {
    moduleForm.addEventListener("submit", function (event) {
        event.preventDefault();
        var code = document.getElementById("moduleCode");
        var name = document.getElementById("moduleName");

        var ok1 = checkInput(code);
        var ok2 = checkInput(name);
        if (!ok1 || !ok2) return;

        var row = document.createElement("tr");
        row.innerHTML = "<td><strong></strong></td><td></td>" +
            '<td><button class="btn btn-red btn-small" data-action="delete">Delete</button></td>';
        row.children[0].firstChild.textContent = code.value.trim().toUpperCase();
        row.children[1].textContent = name.value.trim();

        document.querySelector("#modulesTable tbody").appendChild(row);
        moduleForm.reset();
        updateCounts();
    });
}
