(function($) {
    function DrawerPlugin(element, options) {
        this.element = element;
        this.options = $.extend({}, $.fn.drawer.defaults, options);
        this.init();
    }

    DrawerPlugin.prototype = {
        init: function() {
            this.content = $(this.element).find(this.options.content);
            this.handle  = $(this.element).find(this.options.handle);

            var self = this;

            $(this.handle).on('click', function(event) {
                self.toggle();
                event.preventDefault();
            });
        },
        open: function() {
            return this.content.slideDown(this.options.openSpeed)
                       .removeClass(this.options.closedClass)
                       .addClass(this.options.openClass);
        },
        close: function() {
            return this.content.slideUp(this.options.closeSpeed)
                       .removeClass(this.options.openClass)
                       .addClass(this.options.closedClass);
        },
        toggle: function() {
            return !this.content.is(':visible') ? this.open() : this.close();
        }
    };

    $.fn.drawer = function(option) {
        var options = typeof(option) == 'object' ? option : null;

        return this.each(function() {
            var plugin = $(this).data('jquery.drawer');

            if (plugin == null) {
                $(this).data('jquery.drawer', (plugin = new DrawerPlugin(this, options)));
            }

            if (typeof(option) == 'string') {
                switch (option) {
                    case 'init':   return plugin.init();
                    case 'open':   return plugin.open();
                    case 'close':  return plugin.close();
                    case 'toggle': return plugin.toggle();
                }
            }
        });
    };

    $.fn.drawer.defaults = {
        openSpeed:  700,
        closeSpeed: 700,
        openClass:  'open',
        closedClass: 'closed',
        content: '> .drawer-content',
        handle:  '> .drawer-handle'
    };
}) (jQuery);
