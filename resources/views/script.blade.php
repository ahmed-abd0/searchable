<script>
    const insertHiddenInputsFor = (input) => {

        if (/\[.*\]/gm.test(input.name)) {

            input.insertAdjacentHTML("afterend",
                `
                <div style="display:none;" id="searchable-${input.name.replace(/\[.*\]/gm, "")}" >
                    <input class='searchable-operator' type="hidden" name="${input.name.replace(/\[.*\]/gm, "")}[operator]" value="${input.dataset.filter ?? '='}" />
                </div>
            `
            );

        } else {

            input.insertAdjacentHTML("afterend",

                `
                    <div style="display:none;" id="searchable-${input.name}" >
                        <input type="hidden" class='searchable-value' name="${input.name}[]" value="${input.value}" />
                        <input type="hidden" class='searchable-operator' name="${input.name}[operator]" value="${input.dataset.filter ?? '='}" />
                    </div>
                `
            );
        }

    }

    const updateFilterValue = (wrapper, value) => {
        if (valueInput = wrapper.querySelector("input.searchable-value")) {
            valueInput.value = value;
        }
    }

    document.addEventListener("submit", (event) => {


        if (event.target.classList.contains("filter")) {

            const inputs = event.target.querySelectorAll(
                "input:not(.searchable-value,.searchable-operator),select"
            );

            inputs.forEach(input => {

                const wrapper = document.getElementById(
                    `searchable-${input.name.replace(/\[.*\]/gm, "")}`
                );

                if ((input.type === "checkbox" || input.type === "radio") && !input.checked) {
                    wrapper?.remove();
                    return;
                }

                wrapper ? updateFilterValue(wrapper, input.value) : insertHiddenInputsFor(input);
            });

        }

    });
</script>
