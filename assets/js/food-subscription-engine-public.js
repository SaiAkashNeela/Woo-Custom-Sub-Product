(function( $ ) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here,
     * $ examples are used throughout the file.
     *
     * This file is loaded in `includes/class-fse-public.php` via the `enqueue_scripts` method.
     */

    $(document).ready(function() {
        var $body = $('body');
        var $subscriptionModal = $('#fse-subscription-modal');
        var $alertModal = $('#fse-alert-modal');
        var $alertMessage = $('#fse-alert-message');

        // --- Modal Open/Close --- 
        function openModal($modal) {
            $modal.fadeIn();
            $body.addClass('fse-modal-open');
        }

        function closeModal($modal) {
            $modal.fadeOut();
            $body.removeClass('fse-modal-open');
        }

        // Close main modal
        $subscriptionModal.on('click', '.fse-modal-close', function() {
            closeModal($subscriptionModal);
        });

        // Close alert modal
        $alertModal.on('click', '.fse-modal-close, .fse-alert-close', function() {
            closeModal($alertModal);
        });

        // Close modal if clicked outside content
        $(window).on('click', function(event) {
            if ($(event.target).is($subscriptionModal)) {
                closeModal($subscriptionModal);
            }
            if ($(event.target).is($alertModal)) {
                closeModal($alertModal);
            }
        });
        
        // --- Alert Modal Helper ---
        function showAlert(message) {
            $alertMessage.html(message);
            openModal($alertModal);
        }

        // --- Subscribe Button Click --- 
        $body.on('click', '.fse-subscribe-button', function(e) {
            e.preventDefault();
            var $button = $(this);
            var productId = $button.data('product-id');
            var $productForm = $button.closest('form.cart');
            var isVariableProduct = $productForm.find('input[name="variation_id"]').length > 0;
            var variationId = isVariableProduct ? $productForm.find('input[name="variation_id"]').val() : null;

            if (isVariableProduct && (!variationId || variationId === '0' || variationId === '')) {
                showAlert(fse_params.i18n.select_option_before_subscribe);
                return;
            }
            
            // TODO: Populate product title in modal header
            // $('#fse-modal-product-title').text('Subscribe to ' + ... ); 
            openModal($subscriptionModal);
            // TODO: Load calendar views (weekly/monthly)
        });

        // --- Tab Switching --- 
        $subscriptionModal.on('click', '.fse-tab-link', function() {
            var tabId = $(this).data('tab');
            $('.fse-tab-link').removeClass('active');
            $(this).addClass('active');
            $('.fse-tab-content').removeClass('active');
            $('#fse-' + tabId + '-tab').addClass('active');
        });

        var currentProductPrice = 0; // Initialize to 0
        var selectedDates = [];
        var currentProductId = null;
        var currentVariationId = null;
        var $weeklyCalendarContainer = $('#fse-weekly-tab');
        var $monthlyCalendarContainer = $('#fse-monthly-tab');
        var $selectedDatesSummary = $('#fse-selected-dates-summary');
        var $calculatedPrice = $('#fse-calculated-price');

        // --- Subscribe Button Click (Modified to fetch price) --- 
        $body.on('click', '.fse-subscribe-button', function(e) {
            e.preventDefault();
            var $button = $(this);
            currentProductId = $button.data('product-id');
            var $productForm = $button.closest('form.cart');
            var isVariableProduct = $productForm.find('input[name="variation_id"]').length > 0;
            currentVariationId = isVariableProduct ? $productForm.find('input[name="variation_id"]').val() : null;

            if (isVariableProduct && (!currentVariationId || currentVariationId === '0' || currentVariationId === '')) {
                showAlert(fse_params.i18n.select_option_before_subscribe);
                return;
            }

            // Fetch product price
            $.ajax({
                url: fse_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'fse_get_product_price',
                    product_id: currentProductId,
                    variation_id: currentVariationId,
                    nonce: fse_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        currentProductPrice = parseFloat(response.data.price);
                        // TODO: Populate product title in modal header using product data if available
                        // $('#fse-modal-product-title').text('Subscribe to ' + response.data.name_if_available ); 
                        openModal($subscriptionModal);
                        selectedDates = []; // Reset selected dates
                        generateWeeklyCalendar();
                        generateMonthlyCalendar(); // Initial month
                        updateSummaryAndPrice();
                    } else {
                        showAlert(response.data.message || 'Error fetching product price.');
                    }
                },
                error: function() {
                    showAlert('Error fetching product price.');
                }
            });
        });

        // --- Calendar Generation ---
        function generateWeeklyCalendar() {
            let today = new Date();
            let currentDay = today.getDay(); // 0 (Sun) to 6 (Sat)
            let startDate = new Date(today);
            startDate.setDate(today.getDate() - currentDay); // Set to Sunday of current week

            let html = '<div class="fse-calendar-week">';
            for (let i = 0; i < 7; i++) {
                let dayDate = new Date(startDate);
                dayDate.setDate(startDate.getDate() + i);
                let dateStr = dayDate.toISOString().split('T')[0];
                let dayName = dayDate.toLocaleDateString(undefined, { weekday: 'short' });
                let dayOfMonth = dayDate.getDate();
                let isPast = dayDate < new Date(new Date().setHours(0,0,0,0));
                let selectedClass = selectedDates.includes(dateStr) ? 'selected' : '';

                html += `<button class="fse-date-selector fse-weekly-date ${selectedClass}" data-date="${dateStr}" ${isPast ? 'disabled' : ''}>`;
                html += `<span class="fse-day-name">${dayName}</span>`;
                html += `<span class="fse-day-number">${dayOfMonth}</span>`;
                html += `</button>`;
            }
            html += '</div>';
            $weeklyCalendarContainer.html(html);
        }

        function generateMonthlyCalendar(year, month) {
            let now = new Date();
            let currentYear = year || now.getFullYear();
            let currentMonth = month === undefined ? now.getMonth() : month; // 0-11

            let firstDay = new Date(currentYear, currentMonth, 1);
            let lastDay = new Date(currentYear, currentMonth + 1, 0);

            let monthName = firstDay.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

            let html = '<div class="fse-calendar-month-header">';
            html += `<button class="fse-month-nav prev" data-year="${currentMonth === 0 ? currentYear - 1 : currentYear}" data-month="${currentMonth === 0 ? 11 : currentMonth - 1}" ${ (currentYear === now.getFullYear() && currentMonth === now.getMonth()) ? 'disabled': ''}>&lt;</button>`;
            html += `<span>${monthName}</span>`;
            html += `<button class="fse-month-nav next" data-year="${currentMonth === 11 ? currentYear + 1 : currentYear}" data-month="${currentMonth === 11 ? 0 : currentMonth + 1}">&gt;</button>`;
            html += '</div>';
            html += '<div class="fse-calendar-days-header">';
            const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; // Adjust to locale if needed
            daysOfWeek.forEach(day => html += `<span>${day}</span>`);
            html += '</div>';
            html += '<div class="fse-calendar-grid">';

            // Add empty cells for days before the first of the month
            for (let i = 0; i < firstDay.getDay(); i++) {
                html += '<div class="fse-empty-cell"></div>';
            }

            for (let day = 1; day <= lastDay.getDate(); day++) {
                let dayDate = new Date(currentYear, currentMonth, day);
                let dateStr = dayDate.toISOString().split('T')[0];
                let isPast = dayDate < new Date(new Date().setHours(0,0,0,0));
                let selectedClass = selectedDates.includes(dateStr) ? 'selected' : '';
                html += `<button class="fse-date-selector fse-monthly-date ${selectedClass}" data-date="${dateStr}" ${isPast ? 'disabled' : ''}>${day}</button>`;
            }
            html += '</div>';
            $monthlyCalendarContainer.html(html);
        }

        $monthlyCalendarContainer.on('click', '.fse-month-nav', function() {
            let year = $(this).data('year');
            let month = $(this).data('month');
            generateMonthlyCalendar(year, month);
        });

        // --- Date Selection --- 
        $body.on('click', '.fse-date-selector', function() {
            var $dateButton = $(this);
            var dateStr = $dateButton.data('date');

            if ($dateButton.hasClass('selected')) {
                selectedDates = selectedDates.filter(d => d !== dateStr);
                $dateButton.removeClass('selected');
                // Also update selection on the other calendar if the date exists
                $(`.fse-date-selector[data-date="${dateStr}"]`).removeClass('selected');
            } else {
                selectedDates.push(dateStr);
                $dateButton.addClass('selected');
                $(`.fse-date-selector[data-date="${dateStr}"]`).addClass('selected');
            }
            selectedDates.sort(); // Keep dates sorted
            updateSummaryAndPrice();
        });

        // --- Update Summary and Price ---
        function updateSummaryAndPrice() {
            if (selectedDates.length === 0) {
                $selectedDatesSummary.html(`<p>${fse_params.i18n.no_dates_selected}</p>`);
                $calculatedPrice.text('--');
                return;
            }

            console.log('FSE Debug: updateSummaryAndPrice called.');
            console.log('FSE Debug: fse_params.i18n.currency_symbol =', (fse_params && fse_params.i18n) ? fse_params.i18n.currency_symbol : 'fse_params or fse_params.i18n not found');
            
            let rawCurrencySymbol = '$'; // Default to $
            if (fse_params && fse_params.i18n && 
                fse_params.i18n.currency_symbol && 
                typeof fse_params.i18n.currency_symbol === 'string' && 
                fse_params.i18n.currency_symbol.trim() !== '' && 
                fse_params.i18n.currency_symbol.toLowerCase() !== 'undefined') {
                rawCurrencySymbol = fse_params.i18n.currency_symbol;
            }
            // Decode HTML entities from currency symbol
            let currencySymbol = $('<textarea />').html(rawCurrencySymbol).text();
            console.log('FSE Debug: Determined currencySymbol (decoded) =', currencySymbol);

            let totalPrice = currentProductPrice * selectedDates.length;
            console.log('FSE Debug: currentProductPrice =', currentProductPrice, ', selectedDates.length =', selectedDates.length, ', calculated totalPrice =', totalPrice);
            
            let summaryHtml = '<ul>';
            selectedDates.forEach(dateStr => {
                let dateObj = new Date(dateStr + 'T00:00:00'); // Ensure correct date parsing
                let formattedDate = dateObj.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
                summaryHtml += `<li>${formattedDate}</li>`;
            });
            summaryHtml += '</ul>';
            $selectedDatesSummary.html(summaryHtml);

            if (isNaN(totalPrice) || typeof currentProductPrice !== 'number' || isNaN(currentProductPrice)) {
                console.error('FSE Error: totalPrice is NaN or currentProductPrice is invalid. currentProductPrice:', currentProductPrice, 'Type:', typeof currentProductPrice);
                let errorText = 'Error'; // Default error text
                // You could add localized error strings to fse_params.i18n if needed, e.g.:
                // if (fse_params && fse_params.i18n && fse_params.i18n.price_calculation_error) {
                //     errorText = fse_params.i18n.price_calculation_error;
                // }
                $calculatedPrice.text(currencySymbol + errorText); 
            } else {
                $calculatedPrice.text(currencySymbol + totalPrice.toFixed(2));
            }
            console.log('FSE Debug: Final calculatedPrice text =', $calculatedPrice.text());
        }

        // --- Confirm Subscription --- 
        $('#fse-confirm-subscription-button').on('click', function() {
            console.log('FSE Debug: Confirm Subscription button clicked.');
            if (selectedDates.length === 0) {
                showAlert('Please select at least one delivery date.'); // TODO: i18n
                return;
            }

            // Ensure currentProductId is set. It should be set when the modal is opened.
            if (!currentProductId) {
                showAlert('Product ID not found. Please close the modal and try again.'); // TODO: i18n
                return;
            }
            console.log('FSE Debug: currentProductId =', currentProductId, 'currentVariationId =', currentVariationId);

            // Attempt to find the form associated with the current product ID.
            var $productForm = $('form.cart input[name="add-to-cart"][value="' + currentProductId + '"]').closest('form.cart'); 
            console.log('FSE Debug: Attempt 1: $productForm based on input[name="add-to-cart"][value="' + currentProductId + '"] - Found:', $productForm.length);

            if (!$productForm.length) { // Fallback for variable products or different structures
                if(currentVariationId) {
                     $productForm = $('form.cart input[name="variation_id"][value="' + currentVariationId + '"]').closest('form.cart');
                     console.log('FSE Debug: Attempt 2: $productForm based on input[name="variation_id"][value="' + currentVariationId + '"] - Found:', $productForm.length);
                }
                if (!$productForm.length) { // General fallback if specific product/variation form not found
                    $productForm = $('form.cart.variations_form').first(); // Try specific variations form first
                    console.log('FSE Debug: Attempt 3: $productForm based on form.cart.variations_form - Found:', $productForm.length);
                    if (!$productForm.length) {
                        $productForm = $('form.cart').first(); // Most general cart form
                        console.log('FSE Debug: Attempt 4: $productForm based on form.cart - Found:', $productForm.length);
                    }
                }
            }

            if (!$productForm.length) {
                showAlert('Could not identify the product form. Please try adding to cart normally.'); // TODO: i18n
                console.error('FSE Error: Product form not found after all attempts.');
                return;
            }
            console.log('FSE Debug: Final $productForm selected:', $productForm);
            
            // Remove any existing FSE fields to prevent duplication if user re-opens modal
            $productForm.find('.fse-custom-data-field').remove();
            console.log('FSE Debug: Removed existing .fse-custom-data-field if any.');

            // Add hidden fields for subscription data
            $productForm.append('<input type="hidden" name="fse_selected_dates" class="fse-custom-data-field" value="' + JSON.stringify(selectedDates) + '">');
            $productForm.append('<input type="hidden" name="fse_is_subscription" class="fse-custom-data-field" value="true">');
            console.log('FSE Debug: Appended hidden fields: fse_selected_dates, fse_is_subscription.');
            // The total price will be recalculated server-side based on these dates and product price

            // Find the original add to cart button and click it programmatically
            var $addToCartButton = $productForm.find('[type="submit"].single_add_to_cart_button, .single_add_to_cart_button, button[name="add-to-cart"], input[name="add-to-cart"]').first(); // Broader selector
            console.log('FSE Debug: Attempting to find Add to Cart button with selector: [type="submit"].single_add_to_cart_button, .single_add_to_cart_button, button[name="add-to-cart"], input[name="add-to-cart"]');
            console.log('FSE Debug: $addToCartButton found:', $addToCartButton.length, $addToCartButton);

            if ($addToCartButton.length) {
                    // Create a hidden input to store selected dates
                    let datesInput = $('<input>')
                        .attr('type', 'hidden')
                        .attr('name', 'fse_selected_dates')
                        .val(selectedDates.join(','));
                    
                    console.log('FSE Debug: Selected dates for submission: ', selectedDates.join(','));

                    // Append the hidden input to the product form
                    $productForm.append(datesInput);
                    console.log('FSE Debug: Appended fse_selected_dates input to the form.');

                    // Programmatically click the original 'Add to Cart' button
                    $addToCartButton.click();
                    console.log('FSE Debug: Clicked original Add to Cart button.');

                    // Optionally, close the modal after a short delay
                    // And remove the temporary input to avoid issues if the modal is reopened without a page refresh
                    setTimeout(function() {
                        closeModal();
                        datesInput.remove(); 
                        console.log('FSE Debug: Closed modal and removed temporary dates input.');
                    }, 1000); // Adjust delay as needed
            } else {
                showAlert('Could not find Add to Cart button.'); // TODO: i18n
                console.error('FSE Error: Add to Cart button not found in the identified form.');
            }
        });

        console.log('Food Subscription Engine Public JS Loaded. Params:', fse_params);
    });

})( jQuery );