(function() {
    tinymce.create('tinymce.plugins.ars', {
        init : function(ed, url) {
            ed.addButton('ars', {
                title : 'AdSense Rev Share',
                image : url + '/../images/button.png',
                onclick : function() {

                    ed.windowManager.open( {
                        title: 'Insert AdSense banner',
                        body: [{
                            type: 'listbox',
                            name: 'size',
                            label: 'Banner size',
                            'values': [
                                {text: 'Medium rectangle (300 x 250)', value: '300x250'},
                                {text: 'Large rectangle (336 x 280)', value: '336x280'},
                                {text: 'Leaderboard (728 x 90)', value: '728x90'},
                                {text: 'Wide skyscraper (160 x 600)', value: '160x600'},
                                {text: 'Banner (468 x 60)', value: '468x60'},
                                {text: 'Square (250 x 250)', value: '250x250'},
                                {text: 'Small square (200 x 200)', value: '200x200'},
                                {text: 'Small rectangle (180 x 150)', value: '180x150'}
                            ]
                        }],
                        onsubmit: function( e ) {
                            ed.insertContent( '[ars size="' + e.data.size + '"]');
                        }
                    });
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
        getInfo : function() {
            return {
                longname : "AdSense Revenue Share",
                author : 'Alex Mukho',
                infourl : 'http://cozywp.com',
                version : "1.0"
            };
        }
    });
    tinymce.PluginManager.add('ars', tinymce.plugins.ars);
})();