// Formatter utility class
class Formatter {
    static formatNumber(value) {
        return parseFloat(value).toFixed(2);
    }

    static formatNumberWithCommas(number) {
        if (isNaN(number) || number === null || number === undefined) return "0";
        return Number(parseFloat(number).toFixed(0)).toLocaleString();
    }

    static getStatusClass(completed, total) {
        if (completed === 0 && total === 0) {
            return 'status-complete';
        } else if (completed === 0) {
            return 'status-notstarted';
        } else if (completed === total) {
            return 'status-complete';
        } else {
            return 'status-partial';
        }
    }

    static formatCollapsibleJson(obj, level = 0) {
        if (obj === null) return '<span class="json-null">null</span>';
        if (typeof obj !== 'object') {
            if (typeof obj === 'string') return `<span class="json-string">"${obj}"</span>`;
            if (typeof obj === 'boolean') return `<span class="json-boolean">${obj}</span>`;
            if (typeof obj === 'number') return `<span class="json-number">${obj}</span>`;
            return obj;
        }

        const isArray = Array.isArray(obj);
        const items = Object.entries(obj);
        const length = items.length;

        if (length === 0) return isArray ? '[]' : '{}';

        const closingBracket = isArray ? ']' : '}';
        const collapsibleClass = level > 0 ? 'collapsed' : '';

        let result = `<div class="expandable-container ${collapsibleClass}">`;
        result += `<span class="collapse-icon" onclick="app.jsonViewer.toggleCollapse(this)">▼</span>`;
        result += isArray ? '[' : '{';
        result += `<span class="array-length">${length} item${length > 1 ? 's' : ''}</span>`;
        result += '<div class="collapsible-content json-indent">';

        items.forEach(([key, value], index) => {
            result += '<div>';
            if (!isArray) {
                result += `<span class="json-key">"${key}"</span>: `;
            }
            result += this.formatCollapsibleJson(value, level + 1);
            if (index < length - 1) result += ',';
            result += '</div>';
        });

        result += '</div>' + closingBracket + '</div>';
        return result;
    }
}