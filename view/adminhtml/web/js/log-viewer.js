/**
 * Keeps the connector log on screen up to date, like a tail -f.
 *
 * Only the bytes appended since the previous poll travel from the server: the
 * component remembers the offset the backend hands back and sends it on the
 * next request. When the backend answers with reset -- first load, daily
 * rotation, truncated file -- the whole buffer is replaced instead of appended.
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    var AUTO_STORAGE_KEY = 'janis-log-viewer-auto',
        INTERVAL_STORAGE_KEY = 'janis-log-viewer-interval',
        BOTTOM_THRESHOLD = 24;

    /**
     * localStorage is not available in every admin context (private browsing,
     * hardened profiles), so every access stays optional.
     *
     * @param {String} key
     * @returns {String|null}
     */
    function readPreference(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    /**
     * @param {String} key
     * @param {String} value
     */
    function writePreference(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (e) {
            // Preferences are a convenience, losing them is not an error.
        }
    }

    return function (config, element) {
        var $root = $(element),
            $output = $root.find('[data-role="output"]'),
            $status = $root.find('[data-role="status"]'),
            $file = $root.find('[data-role="file"]'),
            $auto = $root.find('[data-role="auto"]'),
            $interval = $root.find('[data-role="interval"]'),
            output = $output.get(0),
            lines = [],
            offset = null,
            file = null,
            timer = null,
            pending = null;

        /**
         * @returns {Boolean}
         */
        function isAtBottom() {
            return output.scrollHeight - output.scrollTop - output.clientHeight < BOTTOM_THRESHOLD;
        }

        /**
         * Repaints the buffer, keeping the viewport pinned to the end while the
         * user has not scrolled up to read something older.
         *
         * @param {Boolean} follow
         */
        function paint(follow) {
            output.textContent = lines.join('\n');

            if (follow) {
                output.scrollTop = output.scrollHeight;
            }
        }

        /**
         * @param {String} text
         * @param {Boolean} isError
         */
        function setStatus(text, isError) {
            $status.text(text).toggleClass('_error', !!isError);
        }

        /**
         * @returns {Number}
         */
        function currentInterval() {
            return parseInt($interval.val(), 10) || config.interval;
        }

        /**
         * @param {Object} response
         */
        function consume(response) {
            var follow = isAtBottom(),
                incoming = response.lines || [];

            if (response.reset) {
                lines = incoming;
            } else if (incoming.length) {
                lines = lines.concat(incoming);
            }

            if (response.message && !lines.length) {
                lines = [response.message];
            }

            if (lines.length > config.bufferLines) {
                lines = lines.slice(lines.length - config.bufferLines);
            }

            if (response.reset || incoming.length) {
                paint(follow);
            }

            if (response.file && response.file !== file) {
                file = response.file;
                $file.text(file);
            }

            offset = response.offset;

            setStatus($t('Updated at %1').replace('%1', new Date().toLocaleTimeString()), false);
        }

        /**
         * @param {Boolean} forceReload Start over instead of asking for the new bytes
         */
        function poll(forceReload) {
            var data = {
                lines: config.lines
            };

            if (pending) {
                return;
            }

            if (!forceReload && offset !== null && file) {
                data.offset = offset;
                data.file = file;
            }

            pending = $.ajax({
                url: config.url,
                data: data,
                dataType: 'json',
                global: false,
                cache: false
            }).done(consume).fail(function () {
                setStatus($t('Could not reach the server, retrying...'), true);
            }).always(function () {
                pending = null;
                schedule();
            });
        }

        function schedule() {
            window.clearTimeout(timer);

            if (!$auto.prop('checked')) {
                return;
            }

            timer = window.setTimeout(function () {
                // Polling while the tab is in the background only burns requests.
                if (document.hidden) {
                    schedule();

                    return;
                }

                poll(false);
            }, currentInterval());
        }

        $interval.val(String(readPreference(INTERVAL_STORAGE_KEY) || config.interval));

        if (!$interval.val()) {
            $interval.val(String(config.interval));
        }

        $auto.prop('checked', readPreference(AUTO_STORAGE_KEY) !== '0');

        $auto.on('change', function () {
            writePreference(AUTO_STORAGE_KEY, this.checked ? '1' : '0');

            if (this.checked) {
                poll(false);

                return;
            }

            window.clearTimeout(timer);
            setStatus($t('Auto-refresh paused'), false);
        });

        $interval.on('change', function () {
            writePreference(INTERVAL_STORAGE_KEY, this.value);
            schedule();
        });

        $root.find('[data-role="refresh"]').on('click', function () {
            poll(true);
        });

        $(document).on('visibilitychange', function () {
            if (!document.hidden && $auto.prop('checked')) {
                poll(false);
            }
        });

        poll(true);
    };
});
