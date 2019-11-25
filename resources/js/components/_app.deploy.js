/** global: App **/
var App = App || {};

App.Deploy = {

    defaults: {
        trigger: '#dropdown-trigger-deploy',
        content_selector: '#dropdown-deploy .dropdown-content',
        project: null,
    },
    options: {},

    init: function (options) {
        this.options = $.extend({}, this.defaults, options);
        this.bindUI();
    },
    bindUI: function () {
        var that = this;
        $(this.options.trigger).on('click', function (e) {
            e.preventDefault();
            that._load(this);
        });
    },
    unBindUI: function () {
    },

    _load: function (el) {
        var url = '/prepare-deploy/' + this.options.project;
        $(this.options.content_selector).load(url);
    }
}
