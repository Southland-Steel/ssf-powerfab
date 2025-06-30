// Filter Renderer class
class FilterRenderer {
    createAllFilters(projectData) {
        this.createWPFilter(projectData);
        this.createRouteFilter(projectData);
        this.createBayFilter(projectData);
        this.createCategoryFilter(projectData);
        this.createSequenceFilter(projectData);
    }

    createWPFilter(projectData) {
        const workPackageNumbers = [...new Set(projectData.map(item => item.WorkPackageNumber).filter(Boolean))];
        let wpFilterHtml = '<button class="btn btn-primary me-2 mb-2" data-wp="all" onclick="app.filterManager.setWPFilter(\'all\'); app.filterRenderer.updateWPButton(this)">All Work Packages</button>';

        workPackageNumbers.forEach(wp => {
            const wpItems = projectData.filter(item => item.WorkPackageNumber === wp);
            const isNotReleased = wpItems.some(item => item.ReleasedToFab === 0);
            const isOnHold = wpItems.some(item => item.OnHold === 1);

            let tooltip = '';
            if (isNotReleased && isOnHold) {
                tooltip = 'Work Package not released and on hold';
            } else if (isNotReleased) {
                tooltip = 'Work Package not released';
            } else if (isOnHold) {
                tooltip = 'Work Package on hold';
            }

            const extraClasses = [];
            if (isNotReleased) extraClasses.push('wpnotreleased');
            if (isOnHold) extraClasses.push('wponhold');

            wpFilterHtml += `<button class="btn btn-secondary me-2 mb-2 ${extraClasses.join(' ')}"
                data-wp="${wp}"
                onclick="app.filterManager.setWPFilter('${wp}'); app.filterRenderer.updateWPButton(this)"
                ${tooltip ? `title="${tooltip}"` : ''}>${wp}</button>`;
        });

        $('#wpFilter').html(wpFilterHtml);
    }

    createBayFilter(projectData) {
        const bayNames = [...new Set(projectData.map(item => item.Bay).filter(Boolean))];
        let bayFilterHtml = '<button class="btn btn-primary me-2 mb-2" data-bay="all" onclick="app.filterManager.setBayFilter(\'all\'); app.filterRenderer.updateBayButton(this)">All Bays</button>';
        let hasUndefined = projectData.some(item => !item.Bay);

        bayNames.forEach(bay => {
            bayFilterHtml += `<button class="btn btn-secondary me-2 mb-2" data-bay="${bay}" onclick="app.filterManager.setBayFilter('${bay}'); app.filterRenderer.updateBayButton(this)">${bay}</button>`;
        });

        if (hasUndefined) {
            bayFilterHtml += `<button class="btn btn-warning me-2 mb-2" data-bay="undefined" onclick="app.filterManager.setBayFilter('undefined'); app.filterRenderer.updateBayButton(this)">Undefined</button>`;
        }

        $('#bayFilter').html(bayFilterHtml);
    }

    createRouteFilter(projectData) {
        const routes = [...new Set(projectData.map(item => item.RouteName).filter(Boolean))];
        let filterHtml = '<button class="btn btn-primary me-2 mb-2" data-route="all" onclick="app.filterManager.setRouteFilter(\'all\'); app.filterRenderer.updateRouteButton(this)">All Routes</button>';
        let hasUndefined = projectData.some(item => !item.RouteName);

        routes.forEach(route => {
            filterHtml += `<button class="btn btn-secondary me-2 mb-2" data-route="${route}" onclick="app.filterManager.setRouteFilter('${route}'); app.filterRenderer.updateRouteButton(this)">${route}</button>`;
        });

        if (hasUndefined) {
            filterHtml += `<button class="btn btn-warning me-2 mb-2" data-route="undefined" onclick="app.filterManager.setRouteFilter('undefined'); app.filterRenderer.updateRouteButton(this)">Undefined</button>`;
        }

        $('#routeFilter').html(filterHtml);
    }

    createCategoryFilter(projectData) {
        const categories = [...new Set(projectData.map(item => item.Category).filter(Boolean))];
        let categoryFilterHtml = '<button class="btn btn-primary me-2 mb-2" data-category="all" onclick="app.filterManager.setCategoryFilter(\'all\'); app.filterRenderer.updateCategoryButton(this)">All Asm. Categories</button>';
        let hasUndefined = projectData.some(item => !item.Category);

        categories.forEach(category => {
            categoryFilterHtml += `<button class="btn btn-secondary me-2 mb-2" data-category="${category}" onclick="app.filterManager.setCategoryFilter('${category}'); app.filterRenderer.updateCategoryButton(this)">${category}</button>`;
        });

        if (hasUndefined) {
            categoryFilterHtml += `<button class="btn btn-warning me-2 mb-2" data-category="undefined" onclick="app.filterManager.setCategoryFilter('undefined'); app.filterRenderer.updateCategoryButton(this)">Undefined</button>`;
        }

        $('#categoryFilter').html(categoryFilterHtml);
    }

    createSequenceFilter(projectData) {
        const sequenceLots = [...new Set(projectData.map(item =>
            item.SequenceDescription && item.LotNumber ?
                `${item.SequenceDescription} [${item.LotNumber}]` :
                null
        ).filter(Boolean))];

        let sequenceFilterHtml = '<button class="btn btn-primary me-2 mb-2" data-seqlot="all" onclick="app.filterManager.setSequenceFilter(\'all\'); app.filterRenderer.updateSequenceButton(this)">All Sequences</button>';
        let hasUndefined = projectData.some(item => !item.SequenceDescription || !item.LotNumber);

        sequenceLots.sort().forEach(seqLot => {
            const displaySeqLot = seqLot.replace('[', '<br>[');
            sequenceFilterHtml += `<button class="btn btn-secondary me-2 mb-2" data-seqlot="${seqLot}" onclick="app.filterManager.setSequenceFilter('${seqLot}'); app.filterRenderer.updateSequenceButton(this)">${displaySeqLot}</button>`;
        });

        if (hasUndefined) {
            sequenceFilterHtml += `<button class="btn btn-warning me-2 mb-2" data-seqlot="undefined" onclick="app.filterManager.setSequenceFilter('undefined'); app.filterRenderer.updateSequenceButton(this)">Undefined</button>`;
        }

        $('#sequenceFilter').html(sequenceFilterHtml);
    }

    updateBayButton(button) {
        $('#bayFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');
    }

    updateWPButton(button) {
        $('#wpFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');
    }

    updateRouteButton(button) {
        $('#routeFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');
    }

    updateCategoryButton(button) {
        $('#categoryFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');
    }

    updateSequenceButton(button) {
        $('#sequenceFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');
    }

    disableAllFilters() {
        $('#bayFilter button, #wpFilter button, #routeFilter button, #categoryFilter button, #sequenceFilter button')
            .prop('disabled', true);
    }

    enableAllFilters() {
        $('#bayFilter button, #wpFilter button, #routeFilter button, #categoryFilter button, #sequenceFilter button')
            .prop('disabled', false);
    }
}