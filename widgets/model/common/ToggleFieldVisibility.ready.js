class MozzlerFormVisibility {

    $mozzlerMainForm;
    fieldsVisibleWhen; // An array of the fields and visibility info
    modelClassName;

    constructor($mozzlerMainForm, fieldsVisibleWhen, modelClassName) {
        this.$mozzlerMainForm = $mozzlerMainForm;
        this.fieldsVisibleWhen = fieldsVisibleWhen;
        this.modelClassName = modelClassName;
        if (this.$mozzlerMainForm.length === 0) {
            console.error("Empty form, can't process the form visibility of ", $mozzlerMainForm);
            return false;
        }
        this.$mozzlerMainFormInput = this.$mozzlerMainForm.find('input, textarea, select'); //  The main form inputs to worry about

        this.$mozzlerMainFormInput.on('change selected', (event) => {
            console.log("You changed", $(this));
            this.processVisibility();
        });
        this.processVisibility();
    }

    processVisibility() {
        console.debug("Processing the visibility");
        let serialisedMap = this.getFormMap();
        for (const fieldName in this.fieldsVisibleWhen) {
            console.log(`${fieldName}: ${this.fieldsVisibleWhen[fieldName]}`);

            // -- Ignore null entries
            if (!this.fieldsVisibleWhen[fieldName]) {
                console.debug(`Ignoring the null entry for ${fieldName}`); // These aren't going to be output, but just in case
                continue;
            }
            let isVisible = true; // Assume it is in the case of an error

            // -- Check if it should be visible
            try {
                // Run the function defined in the model field's visibleWhen attribute
                // The function should look something similar to 'function (attribute, value, attributesMap) { return "' . self::ANSWER_TYPE_SINGLE_SELECT . '" === attributesMap.answerType; }'
                isVisible = this.fieldsVisibleWhen[fieldName](fieldName, serialisedMap[fieldName], serialisedMap);
            } catch (error) {
                console.error(`processVisibility() Errored when trying to run the function for determining if ${fieldName} should be visible on ${this.modelClassName}. `, error);
            }

            // -- Now to translate back to the DOM entries
            let $class = this.$mozzlerMainForm.find(`.form-group.field-${this.modelClassName.toLowerCase()}-${fieldName.toLowerCase()}`);
            if (isVisible) {
                $class.removeClass('hidden');
            } else {
                $class.addClass('hidden');
                console.debug(`Setting to hidden: ${fieldName}`, $class);
            }
        }
    }

    getFormMap() {
        let serialisedArray = this.$mozzlerMainForm.serializeArray();
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

    //
    //     return {
    //         processVisibility,
    //         $mozzlerMainFormInput,
    //         $mozzlerMainForm,
    //         getFormMap
    //     }
    // })();


}

// Example showing how to use widgets in JS
$('.widget-model-toggle-visibility').each(function () {
    const id = $(this).attr('id');
    const widgetData = m.widgets[id];
    console.log('widget-model-toggle-visibility: ', widgetData);

    const $form = $("#" + widgetData.formId);
    let mozzlerFormVisibility = new MozzlerFormVisibility($form, widgetData.fieldsVisibleWhen, widgetData.modelClassName)

});



