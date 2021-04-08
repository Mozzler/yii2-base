$('.model-report-item-panel').each(function () {
        // Get the unique ID for this widget
        const widgetId = $(this).attr("id");

        // Get the data for this widget
        const widget = m.widgets[widgetId];

        // -- The Panel
        let $panel = $('#' + widget.panelId);

        let searchParams = new URL(document.location).search;
        let endpoint = `${widget.apiEndpoint}${searchParams ? searchParams + '&' : '?'}reportItem=${widget.reportItemName}&model=${widget.modelName}`;

        let lastLoadedTime = null;
        let debounceTime = widget.debounceTimeMs || 6000; // By detault don't reload if you've already done so in the last 6s

        const loadData = function () {
            if (lastLoadedTime && _ModelReport.returnProcessedTimeDuration(lastLoadedTime) < debounceTime) {
                // console.debug(`Debounced, you've already loaded ${widget.title || widget.reportItemName} within the last ${_ModelReport.returnProcessedTimeDurationHumanReadable(lastLoadedTime)} please wait until it's been ${debounceTime / 1000}s`);
                return false;
            }
            lastLoadedTime = _ModelReport.getProcessedTime(); // Prevent any new loads whilst this one is processing
            $panel.removeClass('report-update-flash report-item-clickable');

            _ModelReport.activateRefresh(widgetId);
            let startTime = _ModelReport.getProcessedTime();
            $.getJSON(endpoint, function (panelDataAndConfig) {

                _ModelReport.deactivateRefresh(widgetId);
                // console.log("AJAX request got the panel data: ", panelDataAndConfig);
                if (panelDataAndConfig && panelDataAndConfig.data || 0 === panelDataAndConfig.data) {
                    let html = `${widget.reportItem.pre || ''}${panelDataAndConfig.data}${widget.reportItem.post || ''}`;
                    $panel.html(html).addClass('report-update-flash');
                    _ModelReport.addMessage(widgetId, `Loaded up the Panel <strong>${widget.title || widget.reportItemName}</strong> in ${_ModelReport.returnProcessedTimeDurationHumanReadable(startTime)}`, 'success');
                    lastLoadedTime = _ModelReport.getProcessedTime();
                    setTimeout(function () {
                        $panel.addClass('report-item-clickable');
                    }, debounceTime);

                } else {
                    lastLoadedTime = null;
                    $panel.html('N/A');
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
                $panel.html('N/A');
            });

        }


        $panel.on('click', function () {
            loadData();
        });


        $(document).on('reportmanager:load', function (event) {
            // Initial load or refreshing all
            loadData();
        });
    }
);


