$('.model-report-item-table').each(function () {
        // Get the unique ID for this widget
        const widgetId = $(this).attr("id");

        // Get the data for this widget
        const widget = m.widgets[widgetId];

        // -- The Table
        let $table = $('#' + widget.tableId);
        let $widget = $('#' + widgetId);

        let searchParams = new URL(document.location).search;
        let endpoint = `${widget.apiEndpoint}${searchParams ? searchParams + '&' : '?'}reportItem=${widget.reportItemName}&model=${widget.modelName}`;

        let lastLoadedTime = null;
        let debounceTime = widget.debounceTimeMs || 6000; // By detault don't reload if you've already done so in the last 6s

        // -- If you need more colours than are already defined
        let getColour = function (colourIndex, numberOfColours) {
            // If you specify a custom getColour method then use that
            if (widget.getColour) {
                return widget.getColour(colourIndex, numberOfColours)
            } else {
                return _ModelReport.getColour(colourIndex, numberOfColours);
            }
        }
        const convertDataGetColour = function (chartDataAndConfig) {
            for (let i in (chartDataAndConfig)) {
                const typeofField = typeof chartDataAndConfig[i];
                if ('string' === typeofField && chartDataAndConfig[i].indexOf('getColour(') === 0) {
                    // Convert a getColour(colourIndex, numberOfColours) method into a local call
                    chartDataAndConfig[i] = eval(chartDataAndConfig[i]);
                }
                if ('object' === typeofField) {
                    chartDataAndConfig[i] = convertDataGetColour(chartDataAndConfig[i]);
                }
            }
            return chartDataAndConfig;
        }

        const loadData = function () {
            if (lastLoadedTime && _ModelReport.returnProcessedTimeDuration(lastLoadedTime) < debounceTime) {
                // console.debug(`Debounced, you've already loaded ${widget.title || widget.reportItemName} within the last ${_ModelReport.returnProcessedTimeDurationHumanReadable(lastLoadedTime)} please wait until it's been ${debounceTime / 1000}s`);
                return false;
            }
            lastLoadedTime = _ModelReport.getProcessedTime(); // Prevent any new loads whilst this one is processing
            $table.removeClass('report-update-flash report-item-clickable');

            _ModelReport.activateRefresh(widgetId);
            let startTime = _ModelReport.getProcessedTime();
            $.getJSON(endpoint, function (panelDataAndConfig) {

                _ModelReport.deactivateRefresh(widgetId);
                // console.log("AJAX request got the panel data: ", panelDataAndConfig);
                if (panelDataAndConfig && panelDataAndConfig.data) {
                    $table.html(convertDataGetColour(panelDataAndConfig.data)).addClass('report-update-flash');
                    $widget.addClass('report-update-flash');
                    $widget.find('.report-item-title-table').addClass('report-update-flash');
                    _ModelReport.addMessage(widgetId, `Loaded up the Table <strong>${widget.title || widget.reportItemName}</strong> in ${_ModelReport.returnProcessedTimeDurationHumanReadable(startTime)}`, 'success');
                    lastLoadedTime = _ModelReport.getProcessedTime();
                    setTimeout(function () {
                        $table.addClass('report-item-clickable').removeClass('report-update-flash');
                        $widget.removeClass('report-update-flash');
                        $widget.find('.report-item-title-table').removeClass('report-update-flash');
                    }, debounceTime);

                } else {
                    lastLoadedTime = null;
                    $table.html('N/A');
                    _ModelReport.addMessage(widgetId, `Invalid Response from server when trying to process <strong>${widget.title || widget.reportItemName}</strong>`);
                }
            }).fail(function (err) {
                _ModelReport.deactivateRefresh(widgetId);
                lastLoadedTime = null;
                if (err && err.responseJSON && err.responseJSON.message) {

                    _ModelReport.addMessage(widgetId, `Error whilst trying to load the Panel <strong>${widget.title || widget.reportItemName}</strong> ${err.responseJSON.message}`);
                } else {
                    _ModelReport.addMessage(widgetId, `Error whilst trying to load the Panel <strong>${widget.title || widget.reportItemName}</strong> Report Item ${widget.reportItemName}`);
                }
                console.log("Panel AJAX request errored", err, {endpoint});
                $table.html('N/A');
            });

        }


        $table.on('click', function () {
            loadData();
        });


        $(document).on('reportmanager:load', function (event) {
            // Initial load or refreshing all
            loadData();
        });
    }
);


