(() => {
    const form = document.getElementById('settings-form');
    const apply = document.getElementById('apply');
    if (!form || !apply) return;

    const fields = [...form.querySelectorAll('input:not([type="hidden"]):not(:disabled), textarea:not(:disabled)')];

    function updateApply() {
        apply.hidden = !fields.some(field => field.type === 'checkbox'
            ? field.checked !== field.defaultChecked
            : field.value !== field.defaultValue);
    }

    form.addEventListener('input', updateApply);
    form.addEventListener('change', updateApply);
    // Wait until the browser has restored the controls to their default values.
    form.addEventListener('reset', () => setTimeout(updateApply, 0));
    window.addEventListener('pageshow', updateApply);
    updateApply();
})();
