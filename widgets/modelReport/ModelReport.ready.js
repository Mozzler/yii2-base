_ModelReport = (function () {

    let refreshStateReportItems = {};
    let messages = [];
    let $refreshIconAll = $('.model-report-all-refresh');
    let $warningIconAll = $('.model-report-all-warning');
    let $messagesAll = $('.model-report-warnings');
    let $modelReportSection = $('.model-report-section'); // The main section


    $(document).on('reportmanager:toggle', function () {
        if ($modelReportSection.hasClass('hidden')) {
            // Show the Reports
            $modelReportSection.removeClass('hidden');
            $(document).trigger('reportmanager:load');
        } else {
            // Hide them
            $modelReportSection.addClass('hidden');
            $(document).trigger('reportmanager:unload');
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


    return {
        activateRefresh,
        deactivateRefresh,
        addMessage,
        getProcessedTime,
        returnProcessedTimeDuration,
        returnProcessedTimeDurationHumanReadable,
        getMessages
    }
})();