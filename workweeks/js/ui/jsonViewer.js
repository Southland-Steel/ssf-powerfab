// JSON Viewer class
class JsonViewer {
    show(pciseqId) {
        app.modalManager.showJsonModal(pciseqId);
    }

    toggleCollapse(icon) {
        const container = icon.parentElement;
        container.classList.toggle('collapsed');
        icon.textContent = container.classList.contains('collapsed') ? '►' : '▼';
    }

    expandAll() {
        const containers = document.querySelectorAll('.expandable-container');
        containers.forEach(container => {
            container.classList.remove('collapsed');
            container.querySelector('.collapse-icon').textContent = '▼';
        });
    }

    collapseAll() {
        const containers = document.querySelectorAll('.expandable-container');
        containers.forEach(container => {
            if (!container.parentElement.id || container.parentElement.id !== 'jsonContent') {
                container.classList.add('collapsed');
                container.querySelector('.collapse-icon').textContent = '►';
            }
        });
    }

    copyToClipboard() {
        const jsonContent = document.getElementById('jsonContent');
        const text = jsonContent.textContent;

        try {
            const parsedJson = JSON.parse(text);
            const formattedJson = JSON.stringify(parsedJson, null, 2);

            navigator.clipboard.writeText(formattedJson).then(() => {
                alert('JSON copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy text: ', err);
                // Fallback method
                const textArea = document.createElement('textarea');
                textArea.value = formattedJson;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('JSON copied to clipboard!');
            });
        } catch (e) {
            // If parsing fails, copy the raw text
            navigator.clipboard.writeText(text).then(() => {
                alert('Content copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
    }
}