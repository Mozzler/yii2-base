$(".model-report-toggle-button").on('click', function () {
    $(document).trigger('reportmanager:toggle'); // Toggle the show/hide
    // The majority of the work is done by the _ModelReport
});