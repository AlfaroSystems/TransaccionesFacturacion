<script>
    let createGeographicFilter, editGeographicFilter;

    function setupGeographicFilters(prefix) {
        const deptSelect = document.getElementById(`${prefix}department_id`);
        const muniSelect = document.getElementById(`${prefix}municipality_id`);
        const distSelect = document.getElementById(`${prefix}district_id`);

        if (!deptSelect || !muniSelect || !distSelect) {
            return null;
        }

        const allMunis = Array.from(muniSelect.options).filter((option) => option.value !== '');
        const allDists = Array.from(distSelect.options).filter((option) => option.value !== '');

        function filterMunicipalities() {
            const deptId = deptSelect.value;
            muniSelect.innerHTML = '<option value="">Seleccione municipio</option>';
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            allMunis
                .filter((option) => option.dataset.parent === deptId)
                .forEach((option) => muniSelect.appendChild(option.cloneNode(true)));
        }

        function filterDistricts() {
            const muniId = muniSelect.value;
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            allDists
                .filter((option) => option.dataset.parent === muniId)
                .forEach((option) => distSelect.appendChild(option.cloneNode(true)));
        }

        deptSelect.addEventListener('change', filterMunicipalities);
        muniSelect.addEventListener('change', filterDistricts);

        return {
            setValues: (deptId, muniId, distId) => {
                deptSelect.value = deptId || '';
                filterMunicipalities();
                muniSelect.value = muniId || '';
                filterDistricts();
                distSelect.value = distId || '';
            },
        };
    }

    function openEditCompanyModal(buttonOrActionUrl, companyData = null) {
        const isButton = buttonOrActionUrl instanceof HTMLElement;
        const actionUrl = isButton ? buttonOrActionUrl.dataset.action : buttonOrActionUrl;
        const company = isButton ? JSON.parse(buttonOrActionUrl.dataset.company) : companyData;
        const modal = document.getElementById('edit-company-modal');

        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = company.id;
        document.getElementById('edit-name').value = company.name;
        document.getElementById('edit-commercial_name').value = company.commercial_name || '';
        document.getElementById('edit-nit').value = company.nit || '';
        document.getElementById('edit-nrc').value = company.nrc || '';
        document.getElementById('edit-commercial_line_1').value = company.commercial_line_1 || '';
        document.getElementById('edit-commercial_line_2').value = company.commercial_line_2 || '';
        document.getElementById('edit-commercial_line_3').value = company.commercial_line_3 || '';
        document.getElementById('edit-web_site').value = company.web_site || '';
        document.getElementById('edit-phone').value = company.phone || '';
        document.getElementById('edit-email').value = company.email || '';
        document.getElementById('edit-address').value = company.address || '';
        document.getElementById('edit-is_active').checked = Boolean(company.is_active);

        if (editGeographicFilter) {
            editGeographicFilter.setValues(
                company.department_id,
                company.municipality_id,
                company.district_id
            );
        }
        openModal('edit-company-modal');
    }

    document.addEventListener('DOMContentLoaded', () => {
        createGeographicFilter = setupGeographicFilters('create_');
        editGeographicFilter = setupGeographicFilters('edit_');
        const page = document.getElementById('companies-page');
        if (!page || !page.dataset.modalState) {
            return;
        }

        const modalState = JSON.parse(page.dataset.modalState);
        if (!modalState.hasErrors) {
            return;
        }

        if (modalState.modalType === 'create') {
            openModal('create-company-modal');
            if (createGeographicFilter) {
                createGeographicFilter.setValues(
                    modalState.department_id,
                    modalState.municipality_id,
                    modalState.district_id
                );
            }
            return;
        }

        if (modalState.modalType === 'edit' && modalState.id) {
            const actionUrl = page.dataset.updateUrlTemplate.replace(':id', modalState.id);
            openEditCompanyModal(actionUrl, modalState);
        }
    });
</script>