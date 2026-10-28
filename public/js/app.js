// LET Reviewer — public site scripts.
// This one file runs on every page, so each setup function below first checks
// for the elements it needs and stops early when the page has none of them.
// That is why a single script can serve the public pages, the student area and
// the admin panel without errors on pages that lack those widgets.

// Wait until the page has loaded, so the elements we look for below exist.
document.addEventListener('DOMContentLoaded', function () {
    // Set up the mobile menu in the site header.
    setupNavToggle();
    // Keep the specialization dropdown filtered by the selected exam type.
    setupTrackSelects();
    // Start the countdown that times a quiz attempt.
    setupQuizCountdown();
    // Enable the bookmark buttons that save a question over AJAX.
    setupBookmarkButtons();
});

// Opens and closes the mobile menu (the hamburger button in the header).
function setupNavToggle() {
    // The hamburger button.
    var toggle = document.querySelector('.nav-toggle');
    // The menu that is shown or hidden.
    var nav = document.querySelector('.site-nav');

    // Skip pages that have no menu button or no menu.
    if (!toggle || !nav) {
        return;
    }

    // React to a click on the button.
    toggle.addEventListener('click', function () {
        // Add or remove the "is-open" class; the result is the new state.
        var isOpen = nav.classList.toggle('is-open');
        // Tell screen readers whether the menu is now open.
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
}

// Narrows the specialization choices to the selected exam type.
function setupTrackSelects() {
    // The "exam type" <select> (Elementary or Secondary).
    var examType = document.getElementById('exam_type');
    // The "specialization" <select> whose options we filter.
    var specialization = document.getElementById('specialization');

    // Only pages with both dropdowns need this.
    if (!examType || !specialization) {
        return;
    }

    // The <option> elements that carry a data-exam-type.
    var options = Array.prototype.slice.call(specialization.querySelectorAll('option[data-exam-type]'));
    // The <optgroup> elements that carry a data-exam-type.
    var groups = Array.prototype.slice.call(specialization.querySelectorAll('optgroup[data-exam-type]'));

    // Applies the current exam type to the specialization list.
    var sync = function () {
        // The exam type chosen right now.
        var selectedExamType = examType.value;
        // Tracks whether the chosen specialization is still allowed.
        var stillValid = false;

        // Show or hide each specialization option.
        options.forEach(function (option) {
            // A match means no exam type is chosen, or the tags agree.
            var matches = !selectedExamType || option.dataset.examType === selectedExamType;

            // Hide options that do not belong to the chosen exam type.
            option.hidden = !matches;
            // Disable hidden options so they cannot be submitted by mistake.
            option.disabled = !matches;

            // If the already-chosen option still matches, keep it selected.
            if (matches && option.selected) {
                stillValid = true;
            }
        });

        // Do the same hiding at the <optgroup> level.
        groups.forEach(function (group) {
            // Hide a group when an exam type is chosen and it does not match.
            group.hidden = Boolean(selectedExamType) && group.dataset.examType !== selectedExamType;
            // Keep the group's disabled state in step with its visibility.
            group.disabled = group.hidden;
        });

        // If the previous choice is no longer valid, reset it to the placeholder.
        if (!stillValid) {
            specialization.value = '';
        }
    };

    // Re-run the filter every time the exam type changes.
    examType.addEventListener('change', sync);
    // Also run once on load so pre-filled values are filtered correctly.
    sync();
}

// Counts down a timed attempt and submits it at zero.
// The server also enforces the deadline, so closing the tab cannot extend it.
function setupQuizCountdown() {
    // The countdown wrapper; missing on most pages.
    var countdown = document.getElementById('quiz-countdown');
    // The element that shows the "MM:SS" text.
    var value = document.getElementById('countdown-value');

    // Only the quiz attempt page has this countdown.
    if (!countdown || !value) {
        return;
    }

    // The form that is submitted when time runs out.
    var form = document.getElementById('attempt-form');
    // Seconds left, read from a data attribute the server rendered.
    var remaining = parseInt(countdown.dataset.remainingSeconds, 10);

    // Do not start a timer if the value is missing.
    if (isNaN(remaining)) {
        return;
    }

    // Draws the current time left on screen.
    var render = function () {
        // Never show a negative number once the deadline has passed.
        var safe = Math.max(0, remaining);
        // Whole minutes left (remainder after dividing by 60).
        var minutes = Math.floor(safe / 60);
        // Leftover seconds after the minutes are taken out.
        var seconds = safe % 60;

        // Build "MM:SS", padding single digits so the width stays stable.
        value.textContent = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        // Add the "is-low" class (red clock) in the last minute as a warning.
        countdown.classList.toggle('is-low', safe <= 60);
    };

    // Draw the clock at once so it is correct before the first tick.
    render();

    // Tick once per second (1000 ms).
    var timer = window.setInterval(function () {
        // Take one second off the counter.
        remaining -= 1;
        // Redraw the clock.
        render();

        // When time is up, stop ticking and hand in the attempt.
        if (remaining <= 0) {
            window.clearInterval(timer);

            // Submit the attempt form if this page has one.
            if (form) {
                form.submit();
            }
        }
    }, 1000);
}

// Saves or removes a bookmark without leaving the quiz.
function setupBookmarkButtons() {
    // Every bookmark button that carries its URL in a data attribute.
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.bookmark-btn[data-bookmark-url]'));
    // The CSRF token Laravel needs on any request that changes data.
    var token = document.querySelector('input[name="_token"]');

    // Skip pages with no bookmark buttons or no token.
    if (!buttons.length || !token) {
        return;
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            // Disable the button while the request runs, to stop double clicks.
            button.disabled = true;

            // Send the save/unsave request to the URL stored on the button.
            fetch(button.dataset.bookmarkUrl, {
                // POST because this changes data on the server.
                method: 'POST',
                // Headers Laravel checks for the AJAX + CSRF flow.
                headers: {
                    // Prove the request came from our own page (CSRF protection).
                    'X-CSRF-TOKEN': token.value,
                    // Mark the request as AJAX so Laravel returns the right response.
                    'X-Requested-With': 'XMLHttpRequest'
                },
                // Include the token in the body as well for older middleware.
                body: new URLSearchParams({ _token: token.value }),
                // Send the session cookie so the user stays logged in.
                credentials: 'same-origin'
            }).then(function (response) {
                // Treat any non-2xx status as a failure.
                if (!response.ok) {
                    // Throw so control jumps to the .catch fallback below.
                    throw new Error('Bookmark request failed');
                }

                // The new state is the opposite of what the button shows now.
                var saved = !button.classList.contains('is-saved');

                // Flip the "is-saved" class to restyle the button.
                button.classList.toggle('is-saved', saved);
                // Update aria-pressed so screen readers announce the new state.
                button.setAttribute('aria-pressed', saved ? 'true' : 'false');
                // Swap the label between "Saved" and "Save for later".
                button.textContent = saved ? 'Saved' : 'Save for later';
            }).catch(function () {
                // Fallback: if AJAX failed (offline, blocked), submit a real form.
                var form = document.createElement('form');

                form.method = 'POST';
                // Post to the same URL the button targets.
                form.action = button.dataset.bookmarkUrl;
                // Put the CSRF token in the hidden field Laravel expects.
                form.innerHTML = '<input type="hidden" name="_token" value="' + token.value + '">';

                // Attach the temporary form to the page so it can be submitted.
                document.body.appendChild(form);
                // A normal form submit always works.
                form.submit();
            }).finally(function () {
                // Re-enable the button whether the request succeeded or failed.
                button.disabled = false;
            });
        });
    });
}
