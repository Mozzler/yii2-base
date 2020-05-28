var mozzlerFormVisibility = (function () {
    var $mozzlerMainFormInput = $(`#form-${mozzlerMainWidgetId} input, #form-${mozzlerMainWidgetId} textarea, #form-${mozzlerMainWidgetId} select`); //  The main form inputs (ignore anything in the nav header/footer
    var $mozzlerMainForm = $(`#form-${mozzlerMainWidgetId}`); //  The main form itself
    function processVisibility() {
        // console.debug("Processing the visibility");
        let serialisedMap = getFormMap();
        for (const fieldName in mozzlerFieldsVisibleWhen) {
            // console.log(`${fieldName}: ${mozzlerFieldsVisibleWhen[fieldName]}`);

            // -- Ignore null entries
            if (!mozzlerFieldsVisibleWhen[fieldName]) {
                console.debug(`Ignoring the null entry for ${fieldName}`); // These aren't going to be output, but just in case
                continue;
            }
            let isVisible = true; // Assume it is in the case of an error

            // -- Check if it should be visible
            try {
                // Run the function defined in the model field's visibleWhen attribute
                // The function should look something similar to 'function (attribute, value, attributesMap) { return "' . self::ANSWER_TYPE_SINGLE_SELECT . '" === attributesMap.answerType; }'
                isVisible = mozzlerFieldsVisibleWhen[fieldName](fieldName, serialisedMap[fieldName], serialisedMap);
            } catch (error) {
                console.error(`processVisibility() Errored when trying to run the function for determining if ${fieldName} should be visible. `, error);
            }

            // -- Now to translate back to the DOM entries
            let $class = $(`#form-${mozzlerMainWidgetId} .form-group.field-${mozzlerMainModelClassName.toLowerCase()}-${fieldName.toLowerCase()}`);
            if (isVisible) {
                $class.removeClass('hidden');
            } else {
                $class.addClass('hidden');
                console.debug(`Setting to hidden: ${fieldName}`, $class);
            }
        }
    }

    function getFormMap() {
        let serialisedArray = $mozzlerMainForm.serializeArray();
        if (!serialisedArray) {
            return [];
        }
        // Example serialisedArray = [
        //     {"name": "_csrf", "value": "chbHjTUndeqCuiHDXxYZajdKqOQPkLYQNfBnJbCigh0fX6vILREHicfxdfssT18ZUgKZtn0nhFhanTNT-9bhVg==" },
        //     {"name": "User[name]", "value": "Testing"},
        //     {"name": "User[email]", "value": "example@mozzler.com.au"},
        // ]

        // We want to convert it into a hash map like:
        // formMap = {
        //     "name": "Testing",
        //     "email": "example@mozzler.com.au"
        // };
        // NB: We can do this easily because we don't have any complex data structures like arrays

        //
        let formMap = {};
        serialisedArray.forEach(function (element, index) {
            // Removes the 'User[' and ending ']' parts, selecting just the main field name
            let name = element.name.replace(/[^\[]+\[([\w]+)]/, '$1');
            formMap[name] = element.value;
        });
        return formMap;
    }

    return {
        processVisibility,
        $mozzlerMainFormInput,
        $mozzlerMainForm,
        getFormMap
    }
})();

// -- Only process this when there's model fields which have a visibleWhen setting
if (mozzlerFieldsVisibleWhen) {

    // Process visibility when a field has been changed
    mozzlerFormVisibility.$mozzlerMainFormInput.on('change selected', function (event) {
        console.log("You changed", $(this));
        mozzlerFormVisibility.processVisibility();

    });

    console.debug("Processing the visibility of the fields: ", mozzlerFieldsVisibleWhen);
    mozzlerFormVisibility.processVisibility(); // Process on page load
}
