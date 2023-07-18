(function($) {
    function BlockPlugin(element, options) {
        this.element = element;
        this.options = $.extend({}, $.fn.block.defaults, options);
        this.init();
    }

    BlockPlugin.prototype = {
        init: function() {
        },
        show: function() {
            this.hide();

            var overlay = $("<div></div>").css({
                'width':    $(this.element).width(),
                'height':   $(this.element).height(),
                'position': 'absolute',
                'top':       0,
                'left':      0
            }).addClass(this.options.overlayClass);

            $(this.element).append(overlay.fadeIn(this.options.fadeInSpeed));
        },
        hide: function() {
            var overlay = $(this.element).children("." + this.options.overlayClass);

            overlay.fadeOut(this.options.fadeOutSpeed).remove();
        }
    };

    $.fn.block = function(option) {
        var options = typeof(option) == 'object' ? option : null;

        return this.each(function() {
            var plugin = $(this).data('jquery.blockui');

            if (plugin == null) {
                $(this).data('jquery.blockui', (plugin = new BlockPlugin(this, options)));
            }

            if (typeof(option) == 'string') {
                switch (option) {
                    case 'init': plugin.init(); break;
                    case 'show': plugin.show(); break;
                    case 'hide': plugin.hide(); break;
                }
            }
        });
    };

    $.fn.block.defaults = {
        overlayClass: 'overlay',
        fadeInSpeed:  0,
        fadeOutSpeed: 0
    };
}) (jQuery);
