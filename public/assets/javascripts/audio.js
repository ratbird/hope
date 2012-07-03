(function (STUDIP) {

    var initialised = false,
        loaded      = false,
        queue       = [],
        load_audioplayer = function () {
            AudioPlayer.setup(STUDIP.URLHelper.getURL('assets/flash/player.swf'), {
                animation        : 'no',
                transparentpagebg: 'yes',
                width            : 300
            });
            loaded = true;

            // Process queue
            var item;
            while (item = queue.shift()) {
                STUDIP.Audio.handle(item);
            }
        },
        initialise = function () {
            if (!initialised) {
                var script = document.createElement('script');
                script.src    = STUDIP.URLHelper.getURL('assets/javascripts/audio-player.js');
                script.onload = load_audioplayer;
                document.getElementsByTagName('head')[0].appendChild(script);
                initialised = true;
            }
            return loaded;
        };

    STUDIP.Audio = {
        handle: function (element) {
            if (!initialise()) {
                queue.push(element);
            } else {
                AudioPlayer.embed(element.id, {
                    soundFile: element.src,
                    titles: element.title,
                    width: element.clientWidth || 300
                });
            }
        }
    };

}(STUDIP));