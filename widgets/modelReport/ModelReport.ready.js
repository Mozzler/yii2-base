_ModelReport = (function (m) {

        let modelReportWidgetData = m.widgets[$('.model-report-section').attr("id")];

        let refreshStateReportItems = {};
        let messages = [];
        let $refreshIconAll = $('.model-report-all-refresh');
        let $warningIconAll = $('.model-report-all-warning');
        let $messagesAll = $('.model-report-warnings');
        let $modelReportSection = $('.model-report-section'); // The main section
        let $widgetModelFilterForm = $('.widget-model-filter form'); // For saving the reportMenu=open (or closed) state

        const openStateTrue = 'open';
        const openStateFalse = 'closed';

        $(document).on('reportmanager:toggle', function () {
            if ($modelReportSection.hasClass('hidden')) {
                // Show the Reports
                $modelReportSection.removeClass('hidden');
                $(document).trigger('reportmanager:load');
                setReportMenuOpenStatus(true);
            } else {
                // Hide them
                $modelReportSection.addClass('hidden');
                $(document).trigger('reportmanager:unload');
                setReportMenuOpenStatus(false);
            }
        });

        $refreshIconAll.on('click', function () {
            // Refreshing all
            $(document).trigger('reportmanager:load');
        });

        // Show / Hide the messages
        $warningIconAll.on('click', function () {
            if ($messagesAll.hasClass('hidden')) {
                $messagesAll.removeClass('hidden');
            } else {
                $messagesAll.addClass('hidden');
            }
        });

        let activateRefresh = function (reportItemWidgetId) {
            refreshStateReportItems[reportItemWidgetId] = true;
            showRefreshState();
        }
        let deactivateRefresh = function (reportItemWidgetId) {
            refreshStateReportItems[reportItemWidgetId] = false;
            showRefreshState();
        }

        let isRefreshActive = function () {
            for (const reportItemWidgetId in refreshStateReportItems) {
                if (true === refreshStateReportItems[reportItemWidgetId]) {
                    return true;
                }
            }
            return false;
        }

        let showRefreshState = function () {
            // (de)Activate the refresh icon as appropriate
            if (true === isRefreshActive()) {
                $refreshIconAll.addClass('model-report-all-refresh-active');
            } else {
                $refreshIconAll.removeClass('model-report-all-refresh-active');
            }
        }

        /**
         * Really only expected to be used for warning and danger messages
         *
         * @param reportItemWidgetId
         * @param message
         * @param type {string} 'success', 'info', 'warning',  'danger'
         */
        let addMessage = function (reportItemWidgetId, message, type = 'warning') {
            messages.push({reportItemWidgetId, message, type}); // For the moment we only want to know about the warning and danger messages
            let plainTextMessage = $($.parseHTML(message)).text();
            if (-1 !== $.inArray(type, ['warning', 'danger'])) {
                console.error(`${type}: ${reportItemWidgetId} ${plainTextMessage}`);
            } else {
                console.debug(`${type}: ${reportItemWidgetId} ${plainTextMessage}`);
            }
            displayMessages();
        }
        let displayMessages = function () {

            let hasErrors = false;
            let messageHtml = '';
            if (messages.length === 0) {
                return;
            }
            messages.forEach(function (message) {
                if (-1 !== $.inArray(message.type, ['warning', 'danger'])) {
                    hasErrors = true;
                }
                messageHtml += `<div class="alert alert-${message.type}" role="alert" data-widgetid="${message.reportItemWidgetId}">${message.message}</div>\n`;
            });
            $messagesAll.html(messageHtml);

            if (true === hasErrors) {
                $warningIconAll.removeClass('hidden');
                $messagesAll.removeClass('hidden'); // Only show the messages if there's an error
            }
        }


        let getProcessedTime = function () {
            if (performance && performance.now) {
                return performance.now();
            } else {
                return new Date();
            }
        }

        /**
         * Mainly used to work out a debounce
         * @param startTime
         * @returns {number} milliseconds since the startTime
         */
        let returnProcessedTimeDuration = function (startTime) {
            let performanceEndTime = getProcessedTime();

            let duration = 0;
            if (performance && performance.now) {
                // In milliseconds as float
                duration = performanceEndTime - startTime;
            } else {
                // In milliseconds as int
                duration = performanceEndTime.getTime() - startTime.getTime();
            }
            return duration;
        }


        let returnProcessedTimeDurationHumanReadable = function (startTime) {
            let duration = returnProcessedTimeDuration(startTime);
            // console.log("The duration is: ", duration);
            // This is just basic code for knowing how long a request took, not going to deal with minutes, etc..
            if (duration > 1000) {
                return (Math.round(duration / 10) / 100) + 's'; // Only want it to 2 decimal places
            } else {
                return Math.round(duration) + 'ms';
            }
        }

        let getMessages = function () {
            return messages;
        }

        /**
         * This saves the state of the report menu (open or closed)
         * This is mainly so if you use the filter then the report menu will reopen if it was open before
         *
         * @param openState {boolean}
         */
        let setReportMenuOpenStatus = function (openState = true) {

            try {
                let $reportMenuInput = $widgetModelFilterForm.find(`input[name='reportMenu']`);
                let value = openState === true ? openStateTrue : openStateFalse;
                if ($reportMenuInput.length === 0) {
                    // Create the input
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'reportMenu',
                        value: value
                    }).appendTo($widgetModelFilterForm);
                } else {
                    $reportMenuInput.val(value);
                }
                const url = new URL(window.location);
                url.searchParams.set('reportMenu', value);
                history.pushState({reportMenu: value}, `reportMenu${value}`, url);
            } catch (err) {
                console.error('Error trying to save the current reportMenu status', err);
            }
        }


        let colourGradient = modelReportWidgetData.reportItemColours || [
            "rgb(247, 148, 24)",
            "rgb(245, 184, 46)",
            "rgb(171, 210, 250)",
            "rgb(255, 193, 207)",
            "rgb(214, 228, 235)",
            "rgb(213, 255, 217)",
            "rgb(123, 143, 163)",
            "rgb(221, 166, 222)",
            "rgb(238, 168, 149)",
        ];

        // Requires the d3-color and d3-interpolate files to be included
        let colourRange = d3.piecewise(d3.interpolateRgb, colourGradient);

        let getColour = function (colourIndex, numberOfColours) {
            return colourRange((1 / numberOfColours) * colourIndex);
        }


        return {
            activateRefresh,
            deactivateRefresh,
            addMessage,
            getProcessedTime,
            returnProcessedTimeDuration,
            returnProcessedTimeDurationHumanReadable,
            getMessages,
            setReportMenuOpenStatus,
            getColour,
        }
    }
)(m);

try {
    const urlParams = new URLSearchParams(window.location.search || '');
    if ('open' === urlParams.get('reportMenu')) {


        // Inside a setTimeout so the panels have time to be constructed and listen to the trigger
        setTimeout(function () {

            console.log("Opening the reports menu on page load");
            $(document).trigger('reportmanager:toggle');
        }, 50);
    }

} catch (err) {
    console.error('Error checking to see if the report menu should be opened on page load', err);
}