/**
 * Car Workshop Appointment Management System
 * Vanilla JavaScript - Frontend Interactivity & Live Availability
 */

document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('appointment_date');
    const dateDisplayLabel = document.getElementById('date-display-label');
    const statusIndicator = document.getElementById('availability-status');
    const bookingForm = document.getElementById('appointment-form');

    // ----------------------------------------------------------------------
    // 1. Dynamic Mechanic Availability on Date Change (AJAX Fetch)
    // ----------------------------------------------------------------------
    if (dateInput) {
        dateInput.addEventListener('change', function () {
            const selectedDate = this.value;

            if (!selectedDate) {
                return;
            }

            // Visual loading feedback
            if (statusIndicator) {
                statusIndicator.innerHTML = '🔄 <em>Updating mechanic slots...</em>';
            }

            // Request availability data from index.php
            fetch('index.php?ajax=check_slots&date=' + encodeURIComponent(selectedDate))
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data.success && Array.isArray(data.mechanics)) {
                        updateMechanicCards(data.mechanics, selectedDate);
                        if (statusIndicator) {
                            statusIndicator.innerHTML = '📅 Live slots for: <strong>' + escapeHtml(selectedDate) + '</strong>';
                        }
                    } else {
                        if (statusIndicator) {
                            statusIndicator.innerHTML = '⚠️ <span style="color:red;">Failed to load slots: ' + escapeHtml(data.message || 'Error') + '</span>';
                        }
                    }
                })
                .catch(function (error) {
                    console.error('Availability check failed:', error);
                    if (statusIndicator) {
                        statusIndicator.innerHTML = '⚠️ <span style="color:red;">Error fetching mechanic availability.</span>';
                    }
                });
        });
    }

    /**
     * Update the mechanic radio cards and badges dynamically.
     */
    function updateMechanicCards(mechanicsList, dateString) {
        mechanicsList.forEach(function (mech) {
            const radioItem = document.querySelector('.mechanic-radio-item[data-mechanic-id="' + mech.id + '"]');
            if (!radioItem) {
                return;
            }

            const radioInput = radioItem.querySelector('input[type="radio"]');
            const badge = radioItem.querySelector('.slot-badge');

            const isFull = mech.available_slots <= 0;
            let badgeClass = 'slot-available';
            let slotText = mech.name + ' — ' + mech.available_slots + ' slots available';

            if (mech.available_slots === 1) {
                badgeClass = 'slot-warning';
            } else if (isFull) {
                badgeClass = 'slot-full';
                slotText = mech.name + ' — Fully Booked';
            }

            // Update badge text and style
            if (badge) {
                badge.className = 'slot-badge ' + badgeClass;
                badge.textContent = slotText;
            }

            // Enable or disable the selection
            if (radioInput) {
                if (isFull) {
                    radioInput.disabled = true;
                    if (radioInput.checked) {
                        radioInput.checked = false;
                    }
                    radioItem.classList.add('disabled');
                } else {
                    radioInput.disabled = false;
                    radioItem.classList.remove('disabled');
                }
            }
        });
    }

    // ----------------------------------------------------------------------
    // 2. Client-Side Form Validation
    // ----------------------------------------------------------------------
    if (bookingForm) {
        bookingForm.addEventListener('submit', function (event) {
            let isValid = true;

            // Clear previous errors
            document.querySelectorAll('.error-text').forEach(function (el) {
                el.style.display = 'none';
            });

            // 1. Client Name
            const nameInput = document.getElementById('client_name');
            if (!nameInput || nameInput.value.trim() === '') {
                showError('error-client_name');
                isValid = false;
            }

            // 2. Phone Number (digits only, e.g. 01711234567)
            const phoneInput = document.getElementById('phone');
            const phoneVal = phoneInput ? phoneInput.value.trim() : '';
            const phoneRegex = /^[0-9]{7,15}$/;
            if (!phoneVal || !phoneRegex.test(phoneVal)) {
                showError('error-phone');
                isValid = false;
            }

            // 3. Address
            const addressInput = document.getElementById('address');
            if (!addressInput || addressInput.value.trim() === '') {
                showError('error-address');
                isValid = false;
            }

            // 4. Car License
            const licenseInput = document.getElementById('car_license');
            if (!licenseInput || licenseInput.value.trim() === '') {
                showError('error-car_license');
                isValid = false;
            }

            // 5. Car Engine (numbers only per course instructions)
            const engineInput = document.getElementById('car_engine');
            const engineVal = engineInput ? engineInput.value.trim() : '';
            const numberRegex = /^[0-9]+$/;
            if (!engineVal || !numberRegex.test(engineVal)) {
                showError('error-car_engine');
                isValid = false;
            }

            // 6. Appointment Date (valid & not in past)
            if (dateInput) {
                const selectedVal = dateInput.value;
                const todayVal = dateInput.getAttribute('min');
                if (!selectedVal || (todayVal && selectedVal < todayVal)) {
                    showError('error-appointment_date');
                    isValid = false;
                }
            }

            // 7. Mechanic selection (must pick an active, non-disabled mechanic)
            const selectedMech = document.querySelector('input[name="mechanic_id"]:checked');
            if (!selectedMech || selectedMech.disabled) {
                showError('error-mechanic_id');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
            } else {
                // Prevent accidental duplicate form submission on double-clicking
                const submitBtn = document.getElementById('submit-booking-btn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = '⏳ Processing Appointment...';
                }
            }
        });
    }

    function showError(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.style.display = 'block';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});
