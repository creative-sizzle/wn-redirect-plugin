window.redirectTestLab = class TestLab {
    constructor() {
        this.testerShouldStop = false;
    }

    /**
     * @param {int} offset
     * @param {int} total
     * @param {HTMLElement} button
     */
    execute(offset, total, button) {
        if (this.testerShouldStop) {
            this.done();
            this.testerShouldStop = false;

            return;
        }

        $.request('onTest', {
            data: {
                offset: offset
            },
            success: (data) => {
                if (data.result === '' || typeof data.result === 'undefined') {
                    this.done();
                    this.updateStatusBar(total, total);
                    return;
                }

                const result = document.createElement('div');
                result.innerHTML = data.result;

                document.getElementById('testerResults').prepend(result.firstChild);

                this.updateStatusBar(total, offset);

                if (offset + 1 !== total) {
                    this.execute(offset + 1, total, button);
                }
            },
            error: () => {
                if (offset + 1 !== total) {
                    this.execute(offset + 1, total, button);
                }
            }
        });
    }

    done() {
        document.getElementById('testButton').disabled = false;

        const loader = document.getElementById('loader');
        loader.classList.remove('loading');

        setTimeout(() => {
            loader.classList.add('hidden');
        }, 500);
    }

    /**
     * @param {HTMLElement} button
     */
    start(button) {
        this.updateStatusBar(0);

        document.getElementById('testerResults').innerText = '';

        button.disabled = true;

        const loader = document.getElementById('loader');
        loader.classList.remove('hidden');
        loader.classList.add('loading');

        this.execute(0, document.getElementById('redirectCount').value, button);
    }

    stop() {
        this.testerShouldStop = true;
    }

    /**
     * @param {int} total
     * @param {int} offset
     */
    updateStatusBar(total, offset = 0) {
        let width = 0;

        if (total > 0) {
            width = Math.ceil(100 / total * offset);
        }

        const progress = document.getElementById('progress');
        progress.innerText = `${width}% complete (${offset} of ${total})`;

        const progressBar = document.getElementById('progressBar');
        progressBar.setAttribute('aria-valuenow', width.toString());
        progressBar.style.width = width + '%';

        if (width === 0) {
            progress.innerText = progress.dataset.initial;
        }
    }
}
