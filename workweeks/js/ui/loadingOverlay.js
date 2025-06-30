// Loading Overlay class
class LoadingOverlay {
    show(message) {
        $('<div id="loading-overlay">')
            .append(`<div class="loading-spinner">${message}</div>`)
            .appendTo('body');
    }

    updateMessage(message) {
        $('.loading-spinner').text(message);
    }

    hide() {
        $('#loading-overlay').remove();
    }
}