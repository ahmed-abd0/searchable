<script>
    const insertHiddenInputsFor = (input) => {

        if (/\[.*\]/gm.test(input.name)) {

            input.insertAdjacentHTML("afterend",
                `
                <div style="display:none;" id="searchable-${input.name.replace(/\[.*\]/gm, "")}" >
                <input class='operator' type="hidden" name="${input.name.replace(/\[.*\]/gm, "")}[operator]" value="${input.dataset.filter ?? '='}" >
                </div>
            `
            );

        } else {

            input.insertAdjacentHTML("afterend",

                `
                    <div style="display:none;" id="searchable-${input.name}" >
                        <input type="hidden" class='value' name="${input.name}[]" value="${input.value}" >
                        <input type="hidden" class='operator' name="${input.name}[operator]" value="${input.dataset.filter ?? '='}" >
                    </div>
                `
            );
        }

    }

    document.addEventListener("submit", (event) => {
    
        const inputs = event.target.querySelectorAll("input,select");
        inputs.forEach(input => {

            const wrapper = document.getElementById(`searchable-${input.name.replace(/\[.*\]/gm, "")}`);

            if (!wrapper) {
                insertHiddenInputsFor(input);
            } else {
                wrapper.querySelector("input.value").value = input.value;
            }

        });

    });
</script>
