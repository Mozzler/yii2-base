$(".model-report-item-chart").each(function () {
    // Get the unique ID for this widget
    const widgetId = $(this).attr("id");

    // Get the data for this widget
    const widget = m.widgets[widgetId];

    // -- If you need more colours than are already defined
    const getColour = function (colourIndex, numberOfColours) {
        // If you specify a custom getColour method then use that
        if (widget.getColour) {
            return widget.getColour(colourIndex, numberOfColours)
        } else {
            return _ModelReport.getColour(colourIndex, numberOfColours);
        }
    }

    // -- The Chart... Without data
    let ctx = document.getElementById(widget.canvasId).getContext('2d');
    const $chartCanvas = $('#' + widget.canvasId);
    let chart = null;
    let lastLoadedTime = null;
    let debounceTime = widget.debounceTimeMs || 6000; // By detault don't reload if you've already done so in the last 6s


    const convertDataGetColour = function (chartDataAndConfig) {
        for (let i in (chartDataAndConfig)) {
            const typeofField = typeof chartDataAndConfig[i];
            if ('string' === typeofField  && chartDataAndConfig[i].indexOf('getColour(') === 0) {
                // Convert a getColour(colourIndex, numberOfColours) method into a local call
                chartDataAndConfig[i] = eval(chartDataAndConfig[i]);
            }
            if ( 'object'  === typeofField) {
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
        let startTime = _ModelReport.getProcessedTime();
        $chartCanvas.removeClass('report-item-clickable');
        lastLoadedTime = startTime;
        if (null === chart) {
            chart = new Chart(ctx, widget.reportItem);
        }
        _ModelReport.activateRefresh(widgetId);
        // const endpoint = `${widget.apiEndpoint}?reportItem=${widget.reportItemName}&model=${widget.modelName}`;
        let searchParams = new URL(document.location).search;
        const endpoint = `${widget.apiEndpoint}${searchParams ? searchParams + '&' : '?'}reportItem=${widget.reportItemName}&model=${widget.modelName}`;
        $.getJSON(endpoint, function (chartDataAndConfig) {

            _ModelReport.deactivateRefresh(widgetId);
            // console.log("AJAX request got the chart and data: ", chartDataAndConfig);
            if (chartDataAndConfig) {
                chart.data = convertDataGetColour(chartDataAndConfig.data);
                chart.update();
                lastLoadedTime = _ModelReport.getProcessedTime();
                _ModelReport.addMessage(widgetId, `Loaded up The Chart <strong>${widget.title || widget.reportItemName}</strong> in ${_ModelReport.returnProcessedTimeDurationHumanReadable(startTime)}`, 'success');

                setTimeout(function () {
                    $chartCanvas.addClass('report-item-clickable');
                }, debounceTime);
                // chart = new Chart(ctx, chartDataAndConfig);
            } else {
                lastLoadedTime = null;
                _ModelReport.addMessage(widgetId, `Error whilst trying to load the data for the Chart Report Item <strong>${widget.title || widget.reportItemName}</strong>`, 'warning');
            }
        }).fail(function (err) {
            lastLoadedTime = null;
            _ModelReport.deactivateRefresh(widgetId);
            // console.log("Chart AJAX request errored", err, {endpoint});
            if (err && err.responseJSON && err.responseJSON.message) {
                _ModelReport.addMessage(widgetId, `Error loading Chart <strong>${widget.title || widget.reportItemName}</strong>: ${err.responseJSON.message}`, 'danger');
            } else {
                _ModelReport.addMessage(widgetId, `Error whilst trying to load the data for the Chart Report Item <strong>${widget.title || widget.reportItemName}</strong>`, 'warning');
            }
        });
    }

    $chartCanvas.on('click', function () {
        loadData();
    })

    $(document).on('reportmanager:load', function (event) {
        // Initial load or refreshing all
        loadData();
    });

});


