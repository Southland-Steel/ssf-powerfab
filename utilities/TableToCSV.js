class TableToCSV {
    constructor(tableElement, options = {}) {
        this.table = typeof tableElement === 'string'
            ? document.querySelector(tableElement)
            : tableElement;

        this.options = {
            filename: options.filename || 'table_export.csv',
            separator: options.separator || ',',
            includeHeaders: options.includeHeaders !== false,
            lineEnding: options.lineEnding || '\n',
            quoteFields: options.quoteFields !== false
        };
    }

    // Extract headers from thead th elements
    getHeaders() {
        const headers = [];
        const headerRow = this.table.querySelector('thead tr');

        if (headerRow) {
            const thElements = headerRow.querySelectorAll('th');
            thElements.forEach(th => {
                headers.push(this.cleanText(th.textContent));
            });
        }

        // If no thead, try to get headers from first row
        if (headers.length === 0) {
            const firstRow = this.table.querySelector('tr');
            if (firstRow) {
                const cells = firstRow.querySelectorAll('th, td');
                cells.forEach(cell => {
                    headers.push(this.cleanText(cell.textContent));
                });
            }
        }

        return headers;
    }

    // Extract data from tbody td elements
    getData() {
        const data = [];
        const tbody = this.table.querySelector('tbody') || this.table;
        const rows = tbody.querySelectorAll('tr');

        rows.forEach((row, index) => {
            // Skip header row if no tbody
            if (!this.table.querySelector('tbody') && index === 0) {
                const hasHeaders = row.querySelector('th');
                if (hasHeaders) return;
            }

            const rowData = [];
            const cells = row.querySelectorAll('td');

            cells.forEach(cell => {
                rowData.push(this.cleanText(cell.textContent));
            });

            if (rowData.length > 0) {
                data.push(rowData);
            }
        });

        return data;
    }

    // Clean text content
    cleanText(text) {
        return text.trim().replace(/\s+/g, ' ');
    }

    // Format field for CSV
    formatField(field) {
        if (!this.options.quoteFields) {
            return field;
        }

        // Check if field needs quotes
        const needsQuotes = field.includes(this.options.separator) ||
            field.includes('"') ||
            field.includes('\n') ||
            field.includes('\r');

        if (needsQuotes) {
            // Escape quotes by doubling them
            field = field.replace(/"/g, '""');
            return `"${field}"`;
        }

        return field;
    }

    // Convert to CSV format
    toCSV() {
        let csv = '';

        // Add headers if requested
        if (this.options.includeHeaders) {
            const headers = this.getHeaders();
            if (headers.length > 0) {
                csv += headers.map(h => this.formatField(h)).join(this.options.separator);
                csv += this.options.lineEnding;
            }
        }

        // Add data
        const data = this.getData();
        data.forEach(row => {
            csv += row.map(field => this.formatField(field)).join(this.options.separator);
            csv += this.options.lineEnding;
        });

        return csv;
    }

    // Download CSV file
    download() {
        const csv = this.toCSV();
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });

        // Create download link
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.setAttribute('href', url);
        link.setAttribute('download', this.options.filename);
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Clean up
        URL.revokeObjectURL(url);
    }

    // Get CSV as string
    getCSVString() {
        return this.toCSV();
    }

    // Get as data URI
    getDataURI() {
        const csv = this.toCSV();
        return 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    }
}