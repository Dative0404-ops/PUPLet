// LET Reviewer — public site scripts
// This single file powers the interactive parts of the LET Reviewer site. It is
// loaded on every page, so each setup function first checks for the elements it
// needs and returns early when the current page does not include them. That is
// why one shared script can safely serve the marketing pages, the student area
// and the admin panel without throwing errors on pages that lack those widgets.

// Run the setup code only after the browser has parsed the full HTML document.
// Waiting for DOMContentLoaded guarantees the elements we query below exist.
document.addEventListener('DOMContentLoaded', function () {
    // Prepare the small-screen hamburger menu in the site header.
    setupNavToggle();
    // Keep the specialization dropdown filtered by the selected exam type.
    setupTrackSelects();
    // Start the countdown that times a quiz attempt.
    setupQuizCountdown();
    // Enable the bookmark buttons that save a question over AJAX.
    setupBookmarkButtons();
});

// Shows/hides the mobile navigation menu (the hamburger button in the header).
function setupNavToggle() {
    // The hamburger button that opens and closes the menu on small screens.
    var toggle = document.querySelector('.nav-toggle');
    // The navigation list that gets shown or hidden.
    var nav = document.querySelector('.site-nav');

    // Guard: if this page has no toggle button or no nav, there is nothing to do.
    if (!toggle || !nav) {
        // Stop early so we never call a method on a missing element.
        return;
    }

    // Listen for taps/clicks on the hamburger button.
    toggle.addEventListener('click', function () {
        // Toggle the "is-open" class; the returned boolean tells us the new state.
        var isOpen = nav.classList.toggle('is-open');
        // Reflect the open/closed state to assistive technology for accessibility.
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
}

// Keeps the specialization options in sync with the selected exam type.
function setupTrackSelects() {
    // The "exam type" <select> (e.g. Elementary or Secondary).
    var examType = document.getElementById('exam_type');
    // The dependent "specialization" <select> whose options we filter.
    var specialization = document.getElementById('specialization');

    // Guard: only pages that render both dropdowns need this behaviour.
    if (!examType || !specialization) {
        return;
    }

    // Collect every specialization <option> that declares a data-exam-type.
    var options = Array.prototype.slice.call(specialization.querySelectorAll('option[data-exam-type]'));
    // Collect every <optgroup> that declares a data-exam-type.
    var groups = Array.prototype.slice.call(specialization.querySelectorAll('optgroup[data-exam-type]'));

    // Reusable function that applies the current exam type to the specialization list.
    var sync = function () {
        // The exam type currently chosen by the user.
        var selectedExamType = examType.value;
        // Tracks whether the currently selected specialization is still allowed.
        var stillValid = false;

        // Walk over every specialization option and show/hide it.
        options.forEach(function (option) {
            // An option matches when no exam type is chosen, or its tag agrees.
            var matches = !selectedExamType || option.dataset.examType === selectedExamType;

            // Hide options that do not belong to the chosen exam type.
            option.hidden = !matches;
            // Disable hidden options so they cannot be submitted by mistake.
            option.disabled = !matches;

            // If the already-picked option still matches, keep it selected.
            if (matches && option.selected) {
                stillValid = true;
            }
        });

        // Apply the same logic at the <optgroup> level for grouped options.
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

// Counts down a timed attempt and submits it automatically at zero.
// The server also enforces the deadline, so closing the tab cannot extend it.
function setupQuizCountdown() {
    // The wrapper element holding the countdown; absent on most pages.
    var countdown = document.getElementById('quiz-countdown');
    // The element that shows the "MM:SS" text.
    var value = document.getElementById('countdown-value');

    // Guard: only run on the quiz attempt page that renders the countdown.
    if (!countdown || !value) {
        return;
    }

    // The form that must be submitted automatically when time runs out.
    var form = document.getElementById('attempt-form');
    // Seconds left, read from a data attribute the server rendered.
    var remaining = parseInt(countdown.dataset.remainingSeconds, 10);

    // Guard: if the server gave no usable number, do not start a timer.
    if (isNaN(remaining)) {
        return;
    }

    // Repaints the on-screen clock from the current "remaining" value.
    var render = function () {
        // Never display a negative number once the deadline has passed.
        var safe = Math.max(0, remaining);
        // Whole minutes left (integer division by 60).
        var minutes = Math.floor(safe / 60);
        // Leftover seconds after the minutes are taken out.
        var seconds = safe % 60;

        // Build "MM:SS", zero-padding single digits so the width stays stable.
        value.textContent = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        // Add the "is-low" class (red clock) during the final minute as a warning.
        countdown.classList.toggle('is-low', safe <= 60);
    };

    // Draw the clock immediately so it is correct before the first tick.
    render();

    // Tick once per second (1000 ms).
    var timer = window.setInterval(function () {
        // Decrease the counter by one second.
        remaining -= 1;
        // Redraw the clock.
        render();

        // When time is up, stop ticking and hand in the attempt.
        if (remaining <= 0) {
            // Stop the interval so it does not keep firing.
            window.clearInterval(timer);

            // Submit the attempt form server-side if it exists on this page.
            if (form) {
                form.submit();
            }
        }
    }, 1000);
}

// Saves or removes a bookmark without leaving the quiz.
function setupBookmarkButtons() {
    // Every bookmark button that carries its target URL in a data attribute.
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.bookmark-btn[data-bookmark-url]'));
    // The CSRF token Laravel requires on any state-changing POST request.
    var token = document.querySelector('input[name="_token"]');

    // Guard: skip pages with no bookmark buttons or no CSRF token available.
    if (!buttons.length || !token) {
        return;
    }

    // Attach the click handler to each bookmark button individually.
    buttons.forEach(function (button) {
        // Handle a click on this specific button.
        button.addEventListener('click', function () {
            // Disable the button while the request is in flight to stop double clicks.
            button.disabled = true;

            // Send the save/unsave request to the URL stored on the button.
            fetch(button.dataset.bookmarkUrl, {
                // POST because this changes server state.
                method: 'POST',
                // Request headers Laravel checks for the AJAX + CSRF flow.
                headers: {
                    // Prove the request came from our own page (CSRF protection).
                    'X-CSRF-TOKEN': token.value,
                    // Mark the request as AJAX so Laravel returns the right response.
                    'X-Requested-With': 'XMLHttpRequest'
                },
                // Include the token in the body as well for older middleware.
                body: new URLSearchParams({ _token: token.value }),
                // Send the session cookie so the user stays authenticated.
                credentials: 'same-origin'
            }).then(function (response) {
                // Treat any non-2xx status as a failure.
                if (!response.ok) {
                    // Throw so control jumps to the .catch fallback below.
                    throw new Error('Bookmark request failed');
                }

                // The new state is the opposite of what the button currently shows.
                var saved = !button.classList.contains('is-saved');

                // Flip the "is-saved" class to restyle the button.
                button.classList.toggle('is-saved', saved);
                // Update aria-pressed so screen readers announce the new state.
                button.setAttribute('aria-pressed', saved ? 'true' : 'false');
                // Swap the visible label between "Saved" and "Save for later".
                button.textContent = saved ? 'Saved' : 'Save for later';
            }).catch(function () {
                // Fallback: if AJAX failed (offline, blocked), submit a real form.
                var form = document.createElement('form');

                // Use a standard POST submission.
                form.method = 'POST';
                // Post to the same endpoint the button targets.
                form.action = button.dataset.bookmarkUrl;
                // Embed the CSRF token as the hidden field Laravel expects.
                form.innerHTML = '<input type="hidden" name="_token" value="' + token.value + '">';

                // Attach the temporary form to the page so it can be submitted.
                document.body.appendChild(form);
                // Trigger a normal page navigation that always works.
                form.submit();
            }).finally(function () {
                // Re-enable the button whether the request succeeded or failed.
                button.disabled = false;
            });
        });
    });
}
