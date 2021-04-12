$(".model-report-toggle-button").on('click', function () {
    // The majority of the work is done by the _ModelReport
    $(document).trigger('reportmanager:toggle'); // Toggle the show/hide
});